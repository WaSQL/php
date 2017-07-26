-- DEPRICATED ----------------------------------------------------------------
drop function commissions.FN_RETAIL_PREFERRED_QUALIFIED;
create function commissions.FN_RETAIL_PREFERRED_QUALIFIED(	
	pn_customer_id 		integer
	, pn_period_id 		integer)
	returns table (QUALIFIED_FLAG integer)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
/*--------------------------------------------------------------
by Del Stirling
returns the qualifications for the retail bonus
--------------------------------------------------------------*/
as
BEGIN
	declare ln_retval integer;
	declare ld_period_date date;
	declare ln_batch_id integer;
	select max(closed_date)
		, max(batch_id)
	into ld_period_date
		, ln_batch_id
	from period p, period_batch b
	where b.period_id = p.period_id
		and b.viewable = 1
		and p.period_id = :pn_period_id;
	
	if ld_period_date is null then
		select max(case when s.has_earnings = 1 and t.has_downline = 1 then 1 else 0 end)
		into ln_retval
		from customer c
			inner join customer_type t on t.type_id = c.type_id
			inner join customer_status s  on s.status_id = c.status_id
		where c.customer_id = :pn_customer_id;
	else
		select max(case when s.has_earnings = 1 and t.has_downline = 1 then 1 else 0 end)
		into ln_retval
		from customer_history c
			inner join customer_type t on t.type_id = c.type_id
			inner join customer_status s  on s.status_id = c.status_id
		where c.customer_id = :pn_customer_id
			and c.period_id = :pn_period_id
			and c.batch_id = :ln_batch_id;
	end if;
	return select :ln_retval qualified_flag from dummy;
END;
