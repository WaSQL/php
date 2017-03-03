drop function Commissions.Validate_Org;
create function Commissions.Validate_Org(
					  pn_Customer_id 	integer)
returns result_id integer
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Validate		integer = 0;
	declare ln_Count		integer;
	
	select count(*)
	into ln_Count
	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, enroller_id
		             from customer c
		             order by customer_id)
    		Start where sponsor_id = :pn_Customer_id)
    where enroller_id not in (
    	select node_id
    	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id
		             from customer c
		             order by customer_id)
    		Start where customer_id = :pn_Customer_id));
			    	
	result_id = :ln_Validate;
	
end;