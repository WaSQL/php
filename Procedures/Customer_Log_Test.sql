drop procedure Commissions.Customer_Log_Test;
create procedure Commissions.Customer_Log_Test(
					  pn_Customer_id 		integer
					, pn_Period_id 			integer
					, pn_Direction_id 		integer
					, pn_Type_id			integer
					, pn_Levels				integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	pt_Org = 
		select * 
		from Commissions.Organization(:pn_Customer_id, :pn_Period_id, :pn_Direction_id, :pn_Type_id, :pn_Levels);

end;
