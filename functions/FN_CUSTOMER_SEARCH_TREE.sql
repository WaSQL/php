drop function commissions.fn_customer_search_tree;
create function commissions.fn_customer_search_tree
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			7/10/2017
*
* @describe     Returns a particular node in a parent's downline, along with the upline
*				with siblings at each level.

*
* @param		integer pn_customer_id
* @param		integer pn_parent_customer_id
* @param		integer pn_search_customer_id
* @param		integer pn_organization_type
*				Type 0 = sponsor
*				type 1 = enroller
* @param		varchar [ls_locale]
*
* @returns 		table
*				integer root_customer_id
*				integer organization_type
*				integer has_downline
*				integer customer_id
*				nvarchar customer_name
*				integer level
*				varchar member_type
*				decimal pv_lrp_template
*				decimal pv
*				decimal ov
*				varchar rank
*				varchar title_rank
*
* @example      select * from commissions.fn_customer_search_tree(14, 1001, 646063, 0)
-------------------------------------------------------*/
	(
		pn_period_id				integer
		, pn_parent_customer_id		integer
		, pn_search_customer_id		integer
		, pn_organization_type		integer
		, ls_locale					varchar(20) default 'en-US')
	returns table (
		root_customer_id			integer
		, organization_type			integer
		, has_downline				integer
		, customer_id				integer
		, customer_name				nvarchar(900)
		, level						integer
		, type_id					integer
		, status_id					integer
		, type_description			nvarchar(20)
		, status_description		nvarchar(50)
		, pv_lrp_template			decimal(18,2)
		, pv						decimal(18,2)  
		, ov						decimal(18,2)
		, rank_id					integer
		, title_rank_id				integer
		, rank_description			varchar(50)
		, title_rank_description	varchar(50))
	language sqlscript
	default schema commissions
as
begin
	declare ln_batch_id integer = gl_period_viewable(:pn_period_id);
	declare ln_top_level integer;
	upline = select * from fn_customer_upline(:pn_search_customer_id, :pn_organization_type, :pn_period_id);
	
	select max(level_id) 
	into ln_top_level
	from :upline
	where customer_id = :pn_parent_customer_id;
	
	return 
	select :pn_parent_customer_id root_customer_id
		, :pn_organization_type organization_type
		, (select map(count(*), 0, 0, 1) from customer where sponsor_id = c.customer_id) as has_downline
		, c.customer_id
		, c.customer_name
		, u.level_id as level
		, c.type_id 
		, c.status_id 
		, typ.description as type_description
		, stat.description as status_description
		, c.pv_lrp_template
		, c.pv
		, c.ov as ov
		, c.rank_id
		, c.rank_high_id title_rank_id
		, rnk.description as rank_description
		, trnk.description as title_rank_description
	from :upline u
		inner join gl_customer(:pn_period_id, :ln_batch_id) c
			on map(:pn_organization_type, 0, c.sponsor_id, c.enroller_id) = map(:pn_organization_type, 0, u.sponsor_id, u.enroller_id)
		left join customer_type typ
			on typ.type_id = c.type_id
		left join rank rnk
			on rnk.rank_id = c.rank_id
		left join rank trnk
			on trnk.rank_id = c.rank_high_id
		left join customer_status stat
			on stat.status_id = c.status_id
	where (u.level_id < :ln_top_level) or (u.level_id = :ln_top_level and c.customer_id = :pn_parent_customer_id)
	order by level desc;
end;