drop function Commissions.fn_Customer_Flag;
CREATE function Commissions.fn_Customer_Flag(
								 pn_Customer_id		integer
								,pn_Period_id		integer
								,pn_See_All			integer default 0)
returns table (
			 customer_flag_id 	integer
			,customer_id		integer 
			,flag_type_id		integer 
			,flag_type			nvarchar(50)
			,flag_value			nvarchar(100)
			,beg_date			date
			,end_date			date)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		10-Apr-2017

Purpose:	Returns a resultset of all active customer flags for a given customer

-------------------------------------------------------------------------------- */

begin
	declare ln_Batch_id			integer;
	declare ln_Closed_Date		date;
	
	select closed_date
	into ln_Closed_Date
	from period
	where period_id = :pn_Period_id;
	
	select batch_id
	into ln_Batch_id
	from period_batch
	where period_id = :pn_Period_id
	and viewable = 1;
	
	if :ln_Closed_Date is null then
		if ifnull(:pn_See_All,0) = 0 then
			return
			select 
				  f.customer_flag_id
				, f.customer_id
				, f.flag_type_id
				, t.description 		as flag_type
				, f.flag_value
				, f.beg_date
				, f.end_date
			from customer_flag f, customer_flag_type t
			where f.flag_type_id = t.flag_type_id
			and f.customer_id = :pn_Customer_id
			and ifnull(f.beg_date,current_date) <= current_date
			and ifnull(f.end_date,current_date) >= current_date;
		else
			return
			select 
				  f.customer_flag_id
				, f.customer_id
				, f.flag_type_id
				, t.description 		as flag_type
				, f.flag_value
				, f.beg_date
				, f.end_date
			from customer_flag f, customer_flag_type t
			where f.flag_type_id = t.flag_type_id
			and f.customer_id = :pn_Customer_id;
		end if;
	else
		return
		select 
			  f.customer_history_flag_id	as customer_flag_id
			, f.customer_id
			, f.flag_type_id
			, t.description 				as flag_type
			, f.flag_value
			, null 							as beg_date
			, null							as end_date
		from customer_history_flag f, customer_flag_type t
		where f.flag_type_id = t.flag_type_id
		and f.customer_id = :pn_Customer_id
		and f.period_id = :pn_Period_id
		and f.batch_id = :ln_Batch_id;
	end if;
	
end;
