create function commissions.FN_PERIOD_OPEN(
	pn_period_id integer
	, pn_period_type_id integer
	)
	returns table (PERIOD_ID integer, PERIOD_TYPE nvarchar(34), PERIOD_START_DATE date, PERIOD_END_DATE date)
as
BEGIN
	return 
	select per.period_id PERIOD_ID
		, per.period_type_id || ' - ' || typ.description PERIOD_TYPE
		, per.beg_date PERIOD_START_DATE
		, per.end_date PERIOD_END_DATE
	from commissions.period per
		, commissions.period_type typ
	where typ.period_type_id = per.period_type_id
		and per.closed_date is not null
		and per.final_date is null
		and per.period_id = :pn_period_id
	order by beg_date
		, end_date;
END;