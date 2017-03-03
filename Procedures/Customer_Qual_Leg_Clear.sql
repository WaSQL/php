drop procedure Commissions.Customer_Qual_Leg_Clear;
create procedure Commissions.Customer_Qual_Leg_Clear
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	delete
	from customer_qual_leg;
	  
	commit;
	
end;
