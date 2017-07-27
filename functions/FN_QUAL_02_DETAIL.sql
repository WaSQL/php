DROP FUNCTION COMMISSIONS.FN_QUAL_02_DETAIL;
create function commissions.fn_qual_02_detail
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/14/2017
*
* @describe     returns a Po3 striking distance report for the first three levels of a customer's downline
*
* @param		integer pn_parent_id
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_levels
* @param		varchar [locale]
*
* @returns 		table
* @example      select * from commissions.fn_qual_02_detail(1001, 1004, 14, 2)
-------------------------------------------------------*/
	(
		pn_parent_id 		integer
		, pn_customer_id	integer
		, pn_period_id 		integer
		, pn_levels			integer
		, locale			varchar(20) default 'en-US')
	returns table (
		parent_id			integer
		, customer_id		integer
		, customer_name		nvarchar(900)
		, Po3_level			integer
		, qual_lrp_count	integer
		, lrp_pv			decimal(18,2)
		, tv				decimal(18,2))
	language sqlscript
	sql security invoker
	default schema commissions
as
begin
	return
	select
		:pn_parent_id parent_id
		, c.customer_id
		, c.customer_name
		, e.lvl_id po3_level
		, (select count(*) from customer_history where sponsor_id = c.customer_id and period_id = c.period_id and batch_id = c.batch_id and vol_2 >= 100) qual_lrp_count
		, c.vol_2 lrp_pv
		, c.vol_14 tv
	from hierarchy (
			source (
				select customer_id node_id
					, sponsor_id parent_id
					, customer_id, sponsor_id
					, customer_name 
					, vol_2
					, vol_14
					, period_id
					, batch_id
				from commissions.customer_history 
				where period_id = :pn_period_id 
					and batch_id = gl_period_viewable(:pn_period_id)
				)
			start where customer_id = :pn_customer_id
			depth 3) c
		inner join earning_02 e
			on e.customer_id = c.customer_id
			and e.period_id = c.period_id
			and e.batch_id = c.batch_id;
end;