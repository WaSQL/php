drop function commissions.fn_customer_frontline;
create function commissions.fn_customer_frontline
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/19/2017
*
* @describe     returns a customer's front line along with a count of their frontline
*
* @param        integer pn_customer_id customer ID whose frontline is to be returned
* @param        integer pn_period_id pay period
* @param        integer pn_root_id the ID of the root customer
* @param        integer [pn_type] 0 = sponsor tree, 1 = enroller tree
* @param        varchar(20) [locale]
*
* @returns table 
*	customer_id				integer
*	customer_name 			nvarchar(900)
*	rank_id					integer
*	rank_description		varchar(50)
*	title_rank_id			integer
*	title_rank_description	varchar(50)
*	pv						decimal(18,2)
*	ov						decimal(18,2)
*	count					integer
*	has_downline 			integer
*	type_id					integer
*	status_id				integer
*	type_display			nvarchar(20)
*	status_display 			nvarchar(20)
*	level 					integer
*	parent_id				integer
*	root_id					integer
*
* @example      select * from commissions.customer_type_update(1,2)
-------------------------------------------------------*/
	(
	pn_customer_id 		integer
	, pn_period_id		integer
	, pn_root_id		integer
	, pn_type			integer default 0
	, locale			varchar(20) default 'en-US')
	returns table (
		customer_id					integer
		, customer_name 			nvarchar(900)
		, rank_id					integer
		, rank_description			varchar(50)
		, title_rank_id				integer
		, title_rank_description	varchar(50)
		, pv						decimal(18,2)
		, ov						decimal(18,2)
		, pv_lrp_template			decimal(18,2)
		, count						integer
		, has_downline 				integer
		, type_id					integer
		, status_id					integer
		, type_display				nvarchar(20)
		, status_display 			nvarchar(20)
		, level 					integer
		, parent_id					integer
		, root_id					integer
	)
	language sqlscript
	sql security invoker
	default schema commissions

as
begin
	declare ln_batch_id integer= gl_period_viewable(:pn_period_id);

	declare ln_root_level integer;
	declare ln_customer_level integer;
	declare ln_level integer;
	select max(case when customer_id = :pn_root_id then level_id else 0 end) 
		, max(case when customer_id = :pn_root_id then level_id else 0 end) 
	into ln_root_level
		, ln_customer_level
	from fn_customer_upline(:pn_customer_id, 1, :pn_period_id);
	ln_level = ln_root_level;
	
	if (gl_period_isopen(:pn_period_id) = 1) then
		return
		select c.customer_id
			, c.customer_name
			, c.rank_id
			, (select description from rank where rank_id = c.rank_id) as rank_description
			, c.rank_high_id title_rank_id
			, (select description from rank where rank_id = c.rank_high_id) as title_rank_description
			, c.vol_1 pv
			, c.vol_13 ov
			, c.vol_3 pv_lrp_template
			, count(s.customer_id) count
			, case when count(s.customer_id) > 0 then 1 else 0 end as has_downline
			, c.type_id
			, c.status_id
			, (select description from customer_type where type_id = c.type_id) type_display
			, (select description from customer_status where status_id = c.status_id) status_display
			, :ln_level level
			, :pn_customer_id parent_id
			, :pn_root_id	  root_id
		from customer c
			left join customer s
				on case ifnull(pn_type, 0) when 0 then s.sponsor_id else s.enroller_id end = c.customer_id
		where case ifnull(pn_type, 0) when 0 then c.sponsor_id else c.enroller_id end = :pn_customer_id
		group by c.customer_id
			, c.customer_name
			, c.rank_id
			, c.rank_high_id 
			, c.vol_1
			, c.vol_13
			, c.vol_3
			, c.type_id
			, c.status_id
		order by customer_id;
	else
		return
		select c.customer_id
			, c.customer_name
			, c.rank_id
			, (select description from rank where rank_id = c.rank_id) as rank_description
			, c.rank_high_id title_rank_id
			, (select description from rank where rank_id = c.rank_high_id) as title_rank_description
			, c.vol_1 pv
			, c.vol_13 ov
			, c.vol_3 pv_lrp_template
			, count(s.customer_id) count
			, case when count(s.customer_id) > 0 then 1 else 0 end as has_downline
			, c.type_id
			, c.status_id
			, (select description from customer_type where type_id = c.type_id) type_display
			, (select description from customer_status where status_id = c.status_id) status_display
			, :ln_level level
			, :pn_customer_id parent_id
			, :pn_root_id	  root_id
		from customer_history c
			left join customer_history s
				on case ifnull(pn_type, 0) when 0 then s.sponsor_id else s.enroller_id end = c.customer_id
				and s.period_id = c.period_id
				and s.batch_id = c.batch_id
		where case ifnull(pn_type, 0) when 0 then c.sponsor_id else c.enroller_id end = :pn_customer_id
			and c.period_id = :pn_period_id
			and c.batch_id = :ln_batch_id
		group by c.customer_id
			, c.customer_name
			, c.rank_id
			, c.rank_high_id 
			, c.vol_1
			, c.vol_13
			, c.vol_3
			, c.type_id
			, c.status_id
		order by customer_id;
	end if;
end;