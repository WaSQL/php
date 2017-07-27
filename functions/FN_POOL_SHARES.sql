DROP FUNCTION COMMISSIONS.FN_POOL_SHARES;
create function commissions.FN_POOL_SHARES
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/17/2017
*
* @describe     returns the shares of the specified pool and customer
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer pool_id
*				varchar pool_name
*				integer shares
*
* @example      select * from fn_qual_pool_shares(1001, 13)
-------------------------------------------------------*/
	(
		pn_customer_id 	integer
		, pn_period_id 	integer
		, locale 		varchar(20) default 'en-US'
	)
	returns table (
		POOL_ID 	integer
		, POOL_NAME varchar(50)
		, SHARES 	integer
	)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_batch_id integer;
	select ifnull(max(b.batch_id), 0)
	into ln_batch_id
	from period p
		left join period_batch b
			on b.period_id = p.period_id
				and b.viewable = 1
	where p.period_id = :pn_period_id;
	
	pool_shares = 
		select 5 as pool
			, shares + shares_extra as shares
		from earning_05
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 6 as pool
			, shares + shares_extra as shares
		from earning_06
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 7 as pool
			, shares + shares_extra as shares
		from earning_07
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 8 as pool
			, shares + shares_extra as shares
		from earning_08
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 9 as pool
			, shares + shares_extra as shares
		from earning_09
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 10 as pool
			, shares + shares_extra as shares
		from earning_10
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id
		union
		select 11 as pool
			, shares + shares_extra as shares
		from earning_11
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id;
	
	return
	select p.earning_id pool_id
		, p.description pool_name
		, s.shares
	from earning p, :pool_shares s
	where s.pool = p.earning_id;
END;