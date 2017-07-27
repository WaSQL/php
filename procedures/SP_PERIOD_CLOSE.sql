drop procedure Commissions.sp_Period_Close;
create procedure Commissions.sp_Period_Close(pn_Period_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update period
	set closed_date = current_timestamp
	where period_id = :pn_Period_id;
	
	commit;
end;
