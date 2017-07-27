drop function commissions.FN_PERIOD;
create function commissions.FN_PERIOD
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/11/2017
*
* @describe     returns a list of periods
*
* @param		integer pn_period_type
* @param		varchar locale
*
* @returns 		table
*				integer period_id
*				varchar display_name
*				date beg_date
*				date end_date
*				timestamp closed_date
*				timestamp locked_date
*				timestamp final_date
*				integer edit_flag
*
* @example      select * from commissions.FN_PERIOD(1)
-------------------------------------------------------*/
	(
		pn_period_type 		integer
		,locale				varchar(20) default 'en-US')
	returns table (
		PERIOD_ID 			integer
		, DISPLAY_NAME 		varchar(5000)
		, BEG_DATE 			date
		, END_DATE			date
		, CLOSED_DATE 		timestamp
		, LOCKED_DATE 		timestamp
		, FINAL_DATE 		timestamp
		, EDIT_FLAG 		integer)
	LANGUAGE SQLSCRIPT
	sql security invoker
   	DEFAULT SCHEMA Commissions
as 
BEGIN
	return
	select per.period_id PERIOD_ID
		, per.period_id || ') ' || 
			case period_type_id
				when 1 then 
					to_char(beg_date, 'yyyy-Mon') 
				when 2 then
					extract(year from beg_date) || ' Wk ' || week(beg_date)
				when 3 then
					to_char(beg_date, 'yyyy')
				else 
					to_char(beg_date, 'yyyy-Mon') 
			end as DISPLAY_NAME
		, per.BEG_DATE
		, per.END_DATE
		, per.CLOSED_DATE
		, per.LOCKED_DATE
		, per.FINAL_DATE
		, case when per.locked_date is null and per.final_date is null then 1 else 0 end as EDIT_FLAG
	from period per
	where per.period_type_id = :pn_period_type
	and per.beg_date >=
		case per.period_type_id
			when 1 then
				add_years(current_date, -2)
			when 2 then
				add_months(current_date, -12)
			else
				add_years(current_date, -1)
			end
	order by per.beg_date desc;
END;
