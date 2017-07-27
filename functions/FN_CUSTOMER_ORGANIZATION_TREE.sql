drop function commissions.fn_customer_organization_tree;
create function commissions.fn_customer_organization_tree 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/12/2017
*
* @describe     adds columns to the customer_organization function
*
* @param		integer pn_type_flag
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_levels
* @param		integer pn_direction
* @param		varchar [ls_locale]
*
* @returns 		table
*				integer parent_id
*				bigint count_sub
*				integer customer_id
*				nvarchar customer_name
*				integer level_id
*				integer member_type
*				decimal lrp_volume
*				decimal pv
*				decimal ov
*				integer rank
*				integer rankt_title
*				integer has_downline
* @example      select * from commissions.customer_history_rank(1001)
-------------------------------------------------------*/
	(
	pn_type_flag			integer
	, pn_customer_id		integer
	, pn_period_id 			integer
	, pn_levels				integer
	, pn_direction			integer
	, ls_locale 			varchar(20) default 'en-US')
	returns table(
		parent_id			integer
		, count_sub 		bigint
		, customer_id		integer
		, customer_name		nvarchar(900)
		, level_id			integer
		, member_type		integer
		, lrp_volume		decimal(18,2)
		, pv				decimal(18,2)
		, ov				decimal(18,2)
		, rank				integer
		, rank_title		integer
		, has_downline		integer)
	language sqlscript
	sql security invoker
	default schema commissions
as
BEGIN
	declare ln_batch_id integer;
	if gl_period_isopen(:pn_period_id) = 1 then
		return 
		select f.customer_root_id parent_id
			, f.count_sub
			, f.customer_id
			, c.customer_name
			, f.level_id
			, c.type_id member_type
			, c.vol_2 lrp_volume
			, f.pv
			, f.ov
			, f.rank_id rank
			, f.rank_title rank_title
			, case when f.count_sub > 0 then 1 else 0 end as has_downline
		from fn_customer_organization(:pn_customer_id, :pn_period_id, :pn_direction, :pn_type_flag, :pn_levels) f
			inner join customer c on
				c.customer_id = f.customer_id;
	else
		ln_batch_id = gl_period_viewable(:pn_period_id);
		return 
		select f.customer_root_id parent_id
			, f.count_sub
			, f.customer_id
			, c.customer_name
			, f.level_id
			, c.type_id member_type
			, c.vol_2 lrp_volume
			, f.pv
			, f.ov
			, f.rank_id rank
			, f.rank_title rank_title
			, case when f.count_sub > 0 then 1 else 0 end as has_downline
		from fn_customer_organization(:pn_customer_id, :pn_period_id, :pn_direction, :pn_type_flag, :pn_levels) f
			inner join customer_history c on
				c.customer_id = f.customer_id
				and c.period_id = :pn_period_id
				and c.batch_id = :ln_batch_id;
	end if;
END;