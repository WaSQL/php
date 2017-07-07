drop function commissions.FN_RETAIL_PREFERRED;
create function commissions.FN_RETAIL_PREFERRED (
	pn_customer_id 			integer
	, pn_period_id 			integer)
	returns table (
		TRANSACTION_COUNT 	integer
		, TRANSACTION_PV 	decimal (18,2)
		, TRANSACTION_CV 	decimal (18,2))
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
/*---------------------------------------------------
by Del Stirling
Gets counts and volume of retail transactions
----------------------------------------------------*/
as
BEGIN
	declare ln_batch_id	integer = gl_period_viewable(:pn_period_id);
	
	return
	select ifnull(count(*), 0) transaction_count
			, ifnull(sum(pv), 0) transaction_pv
			, ifnull(sum(cv), 0) transaction_cv
	from gl_volume_retail_detail(:pn_period_id, :ln_batch_id)
	where customer_id = :pn_customer_id;
	
	/*
	
	declare ld_period_date date;
	declare ln_batch_id integer;
	select max(closed_date)
		, max(batch_id)
	into ld_period_date
		, ln_batch_id
	from period p, period_batch b
	where b.period_id = p.period_id	
		and b.viewable = 1;
		
	if ld_period_date is null then
		return 
		select ifnull(count(*), 0) transaction_count
			, ifnull(sum(value_2), 0) transaction_pv
			, ifnull(sum(value_4), 0) transaction_cv
		from transaction
		where customer_id in (select customer_id from customer where sponsor_id = :pn_customer_id)
			and period_id = :pn_period_id
			and transaction_type_id = 3;
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
			and transaction_type_id = 3;
	end if;
	*/
	
END;
