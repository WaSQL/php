drop procedure Commissions.Customer_Validate;
create procedure Commissions.Customer_Validate(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer
					, out pt_Result 		table (Validate	integer))
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions 
	READS SQL DATA
AS

begin
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
			pt_Result = select 0 as validate from dummy;
		end;
		
	pt_Result = select fn_Customer_Validate(:pn_Customer_id, :pn_Sponsor_id, :pn_Enroller_id) as validate from dummy;

end;
