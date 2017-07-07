drop function Commissions.gl_Validate_Enroller_Org;
create function Commissions.gl_Validate_Enroller_Org(
					  pn_Customer_id 	integer)
returns table (
			 Customer_id 	integer
			,Customer		varchar(50)
			,Sponsor_id		integer
			,Sponsor		varchar(50)
			,Enroller_id	integer
			,Enroller		varchar(50)
			,Level_id		integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	return
	select 
		 customer_id		as Customer_id
		,'Name'				as Customer
		,sponsor_id			as Sponsor_id
		,'Sponsor Name'		as Sponsor
		,enroller_id		as Enroller_id
		,'Enroller Name'	as Enroller
		,hierarchy_level	as Level_id
	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, sponsor_id, enroller_id
		             from customer c
		             order by customer_id)
    		Start where sponsor_id = :pn_Customer_id)
    where enroller_id not in (
    	select node_id
    	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id
		             from customer c
		             order by customer_id)
    		Start where customer_id = :pn_Customer_id))
    order by hierarchy_level, customer_id;
	
end;
