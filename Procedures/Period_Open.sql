drop procedure Commissions.Period_Open;
create procedure Commissions.Period_Open(pn_Period_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_id		integer;
	declare ln_Period_type_id	integer;
	declare ln_Beg_Date			date;
	declare ln_End_Date			date;
	declare ln_Start_Date		date;
	declare ln_Realtime_trans	integer;
	declare ln_Realtime_rank	integer;
	declare ln_Recur_Date_Code	integer;
	declare ln_Recur_Date_Value	integer;
	
	select period_id.nextval, p.period_type_id, p.beg_date, p.end_date, t.realtime_trans, t.realtime_rank, t.recurring_date_code, t.recurring_date_value
	into ln_Period_id, ln_Period_type_id, ln_Beg_Date, ln_End_Date, ln_Realtime_trans, ln_Realtime_rank, ln_Recur_Date_Code, ln_Recur_Date_Value
	from Period p, period_template t
	where p.period_type_id = t.period_type_id
	and p.period_id = :pn_Period_id;
			
	if :ln_Recur_Date_Code = 1 then
		ln_Start_Date = ADD_MONTHS(NEXT_DAY(LAST_DAY(:ln_Beg_Date)),-1);
		ln_Beg_Date = ADD_MONTHS(:ln_Start_Date,:ln_Recur_Date_Value);
		ln_End_Date = LAST_DAY(:ln_Beg_Date);
	end if;
	
	if :ln_Recur_Date_Code = 2 then
		ln_Beg_Date = ADD_DAYS(:ln_Beg_Date,:ln_Recur_Date_Value);
		ln_End_Date = ADD_DAYS(:ln_End_Date,:ln_Recur_Date_Value);
	end if;
	
	insert into Period
	(period_id, period_type_id, realtime_trans, realtime_rank, beg_date, end_date)
	values
	(:ln_Period_id, :ln_Period_type_id, :ln_Realtime_trans, :ln_Realtime_rank, :ln_Beg_Date, :ln_End_Date);
	
	commit;
end;
