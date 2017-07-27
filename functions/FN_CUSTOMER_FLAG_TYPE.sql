drop function Commissions.fn_Customer_Flag_Type;
CREATE function Commissions.fn_Customer_Flag_Type
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Function
* @date			19-May-2017
*
* @describe		Returns a resultset of all customer flag types and values
*
* @return		table
*					integer		Flag_Type_id
*					nvarchar	Flag_Description
*					integer		Flag_Type_value_id
*					nvarchar	Flag_Type_Value
*					nvarchar	Flag_Type_Description
*
* @example		select * from fn_Customer_Flag_Type();
-------------------------------------------------------*/
()
returns table (
			 Flag_Type_id 			integer
			,Flag_Description		nvarchar(50)
			,Flag_Type_value_id 	integer
			,Flag_Type_Value		nvarchar(50)
			,Flag_Type_Description	nvarchar(100))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	return
	select 
		 t.flag_type_id			as Flag_Type_id
		,t.description			as Flag_Description
		,v.flag_type_value_id	as Flag_Type_value_id
		,v.value				as Flag_Type_Value
		,v.description			as Flag_Type_Description
	from customer_flag_type t
		left outer join customer_flag_type_value v
			on t.flag_type_id = v.flag_type_id
	order by t.description, v.value;
	
end;
