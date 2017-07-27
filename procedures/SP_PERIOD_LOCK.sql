DROP PROCEDURE SP_PERIOD_LOCK;
create procedure Commissions.sp_Period_Lock(pn_Period_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Locked	integer;
	
	select map(locked_date, null, 0, 1)
	into ln_Locked
	from period
	where period_id = :pn_Period_id;
	
	if :ln_Locked = 1 then
		update period
		set locked_date = null
		where period_id = :pn_Period_id;
	else
		update period
		set locked_date = current_timestamp
		where period_id = :pn_Period_id;
	end if;
	
	commit;
end;