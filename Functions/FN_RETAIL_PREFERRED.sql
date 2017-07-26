-- DEPRICATED ----------------------------------------------------------------
drop function commissions.FN_RETAIL_PREFERRED;
create function commissions.FN_RETAIL_PREFERRED 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/23/2017
*
* @describe     Gets counts and volume of retail transactions
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer transaction_count
*				decimal transaction_pv
*				decimal transaction_cv
*
* @example      select * from commissions.fn_retail_preferred(1393, 12)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		TRANSACTION_COUNT 	integer
		, TRANSACTION_PV 	decimal (18,2)
		, TRANSACTION_CV 	decimal (18,2))
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ld_period_date date;
	declare ln_batch_id integer;
	select max(p.closed_date)
		, max(b.batch_id)
	from period p, period_batch b
	where b.period_id = p.period_id
		and b.viewable = 1
		and p.period_id = :pn_period_id;
	
	if ld_period_date is null then
		return 
		select ifnull(count(*), 0) transaction_count
			, ifnull(sum(value_2), 0) transaction_pv
			, ifnull(sum(value_4), 0) transaction_cv
		from transaction
		where customer_id in (select customer_id from customer where sponsor_id = :pn_customer_id)
			and period_id = :pn_period_id
			and type_id = 3;
	else
		return 
		select ifnull(count(*), 0) transaction_count
			, ifnull(sum(value_2), 0) transaction_pv
			, ifnull(sum(value_4), 0) transaction_cv
		from transaction
		where customer_id in (
				select customer_id
				from customer_history
				where sponsor_id = :pn_customer_id 
					and period_id = :pn_period_id 
					and batch_id = :ln_batch_id)
			and period_id = :pn_period_id
			and type_id = 3;
	end if;
END;
