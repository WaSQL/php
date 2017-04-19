drop function Commissions.fn_Customer_Flags;
CREATE function Commissions.fn_Customer_Flags()
returns table (
			 customer_flag_id 	integer
			,customer_id		integer 
			,flag_type_id		integer 
			,flag_value			nvarchar(100)
			,beg_date			date
			,end_date			date)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		10-Apr-2017

Purpose:	Returns a resultset of all active customer flags

-------------------------------------------------------------------------------- */

begin

	return
	select 
		  customer_flag_id
		, customer_id
		, flag_type_id
		, flag_value
		, beg_date
		, end_date
	from customer_flag
	where ifnull(beg_date,current_date) <= current_date
	and ifnull(end_date,current_date) >= current_date;
	
end;
