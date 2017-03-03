drop procedure Commissions.Customer_Log_Test;
create procedure Commissions.Customer_Log_Test(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare pt_Result 		table (Validate	integer);
	
	call Commissions.Customer_Validate(:pn_Customer_id, :pn_Sponsor_id, :pn_Enroller_id, :pt_Result);

end;