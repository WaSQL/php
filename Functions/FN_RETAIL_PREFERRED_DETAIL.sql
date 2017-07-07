drop function commissions.FN_RETAIL_PREFERRED_DETAIL;
create function commissions.FN_RETAIL_PREFERRED_DETAIL(
	pn_customer_id 				integer
	, pn_period_id 				integer
	)
	returns table (
		CUSTOMER_ID 			integer
		, CUSTOMER_NAME 		nvarchar(900)
		, CUSTOMER_TYPE 		nvarchar(20)
		, TRANSACTION_ID 		integer
		, TRANSACTION_AMOUNT 	decimal(18,2)
		, TRANSACTION_PV 		decimal(18,2)
		, TRANSACTION_CV 		decimal(18,2)
	)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
/*----------------------------------------------
by Del Stirling
returns the transactions associated with the preferred customer bonus
--------------------------------------------------------------------*/
as
BEGIN
	declare ln_batch_id	integer = gl_period_viewable(:pn_period_id);
	
	return
	select    transaction_customer_id	as customer_id
			, transaction_customer_name	as customer_name
			, transaction_customer_type	as customer_type
			, transaction_id
			, sales_amt 				as transaction_amount
			, pv transaction_pv
			, cv transaction_cv
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
		select c.customer_id
			, c.customer_name
			, typ.description customer_type
			, t.transaction_id
			, t.value_1 transaction_amount
			, t.value_2 transaction_pv
			, t.value_4 transaction_cv
		from customer c
			inner join transaction t on t.customer_id = c.customer_id
			inner join customer_type typ on typ.type_id = c.type_id
		where c.sponsor_id = :pn_customer_id
			and t.period_id = :pn_period_id
			and t.transaction_type_id = 3
			and c.type_id in (select type_id from customer_type where has_retail = 1)
		order by c.customer_id
			, t.transaction_id;
	else
		return
		select c.customer_id
			, c.customer_name
			, typ.description customer_type
			, t.transaction_id
			, t.value_1 transaction_amount
			, t.value_2 transaction_pv
			, t.value_4 transaction_cv
		from customer_history c
			inner join transaction t on t.customer_id = c.customer_id
			inner join customer_type typ on typ.type_id = c.type_id
		where c.sponsor_id = :pn_customer_id
			and t.period_id = :pn_period_id
			and t.transaction_type_id = 3
			and c.type_id in (select type_id from customer_type where has_retail = 1)
			and c.period_id = :pn_period_id
			and c.batch_id = :ln_batch_id
		order by c.customer_id
			, t.transaction_id;
	end if;
	*/
	
END;
