drop function Commissions.fn_Customer_Flags;
CREATE function Commissions.fn_Customer_Flags(
						pn_Period_id	integer default 0)
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
	declare ld_Beg_date		date;
	declare ld_End_date		date;
	
	select beg_date, end_date
	into ld_Beg_date, ld_End_date
	from period
	where period_id = 
		case when ifnull(:pn_Period_id,0) = 0 then
			(select period_id
			 from period
			 where period_type_id = 1
			 and closed_date is null)
		else
			:pn_Period_id
		end;

	return
	select 
		  customer_flag_id
		, customer_id
		, flag_type_id
		, flag_value
		, beg_date
		, end_date
	from customer_flag
	where ifnull(beg_date,:ld_Beg_date) <= :ld_Beg_date
	and ifnull(end_date,:ld_End_date) >= :ld_End_date;
	
end;
