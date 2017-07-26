drop function Commissions.gl_Customer_Flag;
CREATE function Commissions.gl_Customer_Flag
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			10-Apr-2017
*
* @describe		Returns a resultset of all active customer flags for a given customer
*
* @param		integer pn_Customer_id 		Customer id
* @param		integer pn_Period_id 		Commission Period
* @param		integer [pn_See_All] 		1 - See All ; 0 - See Only Active
*
* @return		table
*					integer		customer_flag_id
*					integer		customer_id
*					integer		flag_type_id
*					nvarchar	flag_type
*					nvarchar	flag_value
*					date		beg_date
*					date		end_date
*
* @example		select * from gl_Customer_Flag(1001, 10, 1);
-------------------------------------------------------*/
(pn_Customer_id		integer
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

begin
	declare ld_Current_Date		date;
	declare ln_Period_Batch_id	integer = gl_Period_Viewable(:pn_Period_id);
	
	-- Get Current Date
	select current_date
	into ld_Current_Date
	from dummy;
	
	if gl_Period_isOpen(:pn_Period_id) = 1 or :pn_Period_id = 0 then
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
			from customer_flag f
				left outer join customer_flag_type t
					on f.flag_type_id = t.flag_type_id
			where f.customer_id = map(:pn_Customer_id,0,f.customer_id,:pn_Customer_id)
			and ifnull(f.beg_date,:ld_Current_Date) <= :ld_Current_Date
			and ifnull(f.end_date,:ld_Current_Date) >= :ld_Current_Date;
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
			from customer_flag f
				left outer join customer_flag_type t
					on f.flag_type_id = t.flag_type_id
			where f.customer_id = map(:pn_Customer_id,0,f.customer_id,:pn_Customer_id);
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
		from customer_history_flag f
				left outer join customer_flag_type t
					on f.flag_type_id = t.flag_type_id
		where f.customer_id = map(:pn_Customer_id,0,f.customer_id,:pn_Customer_id)
		and f.period_id = :pn_Period_id
		and f.batch_id = :ln_Period_Batch_id;
	end if;
	
end;
