drop function Commissions.fn_Customer_Flag_Type;
CREATE function Commissions.fn_Customer_Flag_Type()
returns table (
			 Flag_Type_id 			integer
			,Flag_Description		nvarchar(50)
			,Flag_Type_value_id 	integer
			,Flag_Type_Value		nvarchar(50)
			,Flag_Type_Description	nvarchar(100))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		18-Apr-2017

Purpose:	Returns a resultset of all customer flag types and values

-------------------------------------------------------------------------------- */

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
