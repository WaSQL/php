DROP FUNCTION COMMISSIONS.FN_EARNING_02;
create function commissions.FN_EARNING_02 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/26/2017
*
* @describe     gets the levels of payout for the power of 3 commissions for the customer's frontline
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer cnt_lrp_100
*				integer cnt_50_earners
*				integer cnt_250_earners
*
* @example      select * from commissions.fn_earning_02(1001, 14)
-------------------------------------------------------*/
	(
	pn_customer_id 		integer
	, pn_period_id 		integer
	, ls_locale			varchar(20) default 'en-US')
	returns table (
		CNT_LRP_100 		integer
		, CNT_50_EARNERS 	integer
		, CNT_250_EARNERS 	integer
	)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
as
BEGIN
	if gl_period_isopen(:pn_period_id) = 1 then 
		return
		select count(*) as cnt_lrp_100
			, null as cnt_50_earners
			, null as cnt_250_earners
		from customer c 
		where vol_4 >= 100;
	else
		return
		select sum(case when p.paid_lvl_id > 1 or p.lvl_id >= 1 then 1 else 0 end) as cnt_lrp_100
			, sum(case when p.paid_lvl_id > 1 or p.lvl_id > 1 then 1 else 0 end) as cnt_50_earners
			, sum(case when p.paid_lvl_id > 1 or p.lvl_id > 2 then 1 else 0 end) as cnt_250_earners
		from customer_history c
			left join earning_02 p on
				p.customer_id = c.customer_id
				and p.period_id = c.period_id
				and p.batch_id = c.batch_id
		where c.sponsor_id = :pn_customer_id
			and c.period_id = :pn_period_id
			and c.batch_id = gl_period_viewable(:pn_period_id)
			and c.vol_4 >= 100;
	end if;
END;