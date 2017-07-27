DROP FUNCTION COMMISSIONS.FN_EARNING_13_DETAIL;
create function commissions.fn_earning_13_detail
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/7/2017
*
* @describe     returns details of the fast start commissions
*				currently a dummy placeholder function
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer customer_id
*				nvarchar customer_name
*				integer qualified
*				decimal pv
*				decimal cv
*				integer level
*				integer paid_level
*				integer percent_paid
*				decimal commission_amount
*				decimal conversion_rat
*				decimal converted_commission_amount
*
* @example      select * from commissions.customer_history_rank(1001)
-------------------------------------------------------*/
	(
	pn_customer_id 		integer
	, pn_period_id 		integer
	, locale 			varchar(20) default 'en-US')
	returns table (
		customer_id						integer
		, customer_name 				nvarchar(900)
		, qualified 					integer
		, pv							decimal(18,8)
		, cv							decimal(18,8)
		, level							integer
		, paid_level					integer
		, percent_paid					integer
		, commission_amount 			decimal(18,8)
		, conversion_rate				decimal(18,8)
		, converted_commission_amount	decimal(18,8))
	language sqlscript
	sql security invoker
	default schema commissions
as
begin
	return 
	select null as customer_id
		, null as customer_name
		, null as qualified
		, null as pv
		, null as cv
		, null as level
		, null as paid_level
		, null as percent_paid
		, null as commission_amount
		, null as conversion_rate
		, null as converted_commission_amount
	from dummy 
	where 1=0;
end;