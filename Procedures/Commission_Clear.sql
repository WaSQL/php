drop procedure Commissions.Commission_Clear;
create procedure Commissions.Commission_Clear()
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	call customer_clear();
	call customer_qual_leg_clear();
	
end;
