DROP FUNCTION COMMISSIONS.FN_QUAL_02;
create function commissions.fn_qual_02
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/14/2017
*
* @describe     shows the power of 3 paid level count
* 				for use in striking distance
* 
* 				Po3 levels:
* 				1: 100 pv lrp order
* 				2: $50 dollar commission
* 				3: $250 dollar + commission
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer po3_level
*				integer count
*
* @example      select * from commissions.fn_qual_02(1001, 14)
-------------------------------------------------------*/
	(
			pn_customer_id 		integer
			, pn_period_id 		integer
			, locale 			varchar(20) default 'en-US')
	returns table (
		Po3_level	integer
		, count		integer)
	language sqlscript
	default schema commissions
as
begin
	declare ln_batch_id integer = gl_period_viewable(:pn_period_id);
	--joining in a function table so that it will return a row for the level even if there are no records for it.
	tmp = 	select 1 as lvl, 0 as cnt from dummy
			union
			select 2 as lvl, 0 as cnt from dummy
			union
			select 3 as lvl, 0 as cnt from dummy;
	return
	select t.lvl Po3_level
		, ifnull(e.cnt, t.cnt) as count
	from :tmp t left join (
				select case when paid_lvl_id > 1 then 3 when lvl_id > 3 then 3 else lvl_id end lvl
					, count(*) cnt
				from commissions.earning_02
				where period_id = :pn_period_id
					and batch_id = :ln_batch_id
					and sponsor_id = :pn_customer_id
				group by case when paid_lvl_id > 1 then 3 when lvl_id > 3 then 3 else lvl_id end
				) e
			on e.lvl = t.lvl
	order by Po3_level;
end;