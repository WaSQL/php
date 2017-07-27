drop function Commissions.gl_Validate_Spon_Enroll;
create function Commissions.gl_Validate_Spon_Enroll
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a Boolean value indicating the enroller is in the sponsor's upline
*				1 - True
*				0 - False
*
* @param		integer	pn_Sponsor_id 		Sponsor id
* @param		integer	pn_Enroller_id 		Enroller id
*
* @return		Boolean	Value
*
* @example		select gl_Validate_Spon_Enroll(1004, 1001) from dummy;
-------------------------------------------------------*/
(pn_Sponsor_id 		integer
,pn_Enroller_id 	integer)
returns result_id integer
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Validate		integer = 0;
	declare ln_Count		integer;
	declare ln_Customer_id	integer = :pn_Sponsor_id;
	
	loop
		if :ln_Customer_id = :pn_Enroller_id then
			ln_Validate = 1;
			break;
		end if;
		
		select count(*)
		into ln_Count
		from customer
		where customer_id = :ln_Customer_id;
		
		if :ln_Count = 0 then
			break;
		end if;
			
		select sponsor_id
		into ln_Customer_id
		from customer
		where customer_id = :ln_Customer_id;
			
	end loop;
			    	
	result_id = :ln_Validate;
	
end;
