drop function Commissions.gl_Validate_Enroller_Org;
create function Commissions.gl_Validate_Enroller_Org
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of any customer in the given org where the enroller is not within the org
*
* @param		integer	pn_Customer_id 		Customer id
*
* @return		table
*					integer		Customer_id
*					varchar		Customer
*					integer		Sponsor_id
*					varchar		Sponsor
*					integer		Enroller_id
*					varchar		Enroller
*					integer		Level_id
*
* @example		select * from gl_Validate_Enroller_Org(1001);
-------------------------------------------------------*/
(pn_Customer_id 	integer)
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
