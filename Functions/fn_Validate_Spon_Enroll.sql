drop function Commissions.fn_Validate_Spon_Enroll;
create function Commissions.fn_Validate_Spon_Enroll(
					  pn_Sponsor_id 	integer
					, pn_Enroller_id 	integer)
returns result_id integer
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
   	--READS SQL DATA
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
