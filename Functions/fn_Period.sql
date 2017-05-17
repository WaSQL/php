drop function commissions.fn_Period;
create function commissions.fn_Period(pn_period_type integer)
	returns table (PERIOD_ID integer, DISPLAY_NAME varchar(5000), BEG_DATE date, END_DATE date, CLOSED_DATE timestamp, LOCKED_DATE timestamp, FINAL_DATE timestamp, EDIT_FLAG integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
as 
BEGIN
	return
	select per.period_id PERIOD_ID
		, per.period_id || ') ' || case when period_type_id = 1 then to_char(beg_date, 'yyyy-Mon') else extract(year from beg_date) || ' Wk ' || week(beg_date) end as DISPLAY_NAME
		, per.BEG_DATE
		, per.END_DATE
		, per.CLOSED_DATE
		, per.LOCKED_DATE
		, per.FINAL_DATE
		, case when per.locked_date is null and per.final_date is null then 1 else 0 end as EDIT_FLAG
	from period per
	where per.period_type_id = :pn_period_type
		and per.beg_date between case when per.period_type_id = 1 then add_years(current_date, -2) else add_months(current_date, '-6') end and current_date
	order by per.beg_date desc;
END;