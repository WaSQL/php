drop procedure Commissions.Commission_Clear;
create procedure Commissions.Commission_Clear
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	call period_batch_clear(0, 0);
	call customer_clear();
	call customer_qual_leg_clear();
	
end;
