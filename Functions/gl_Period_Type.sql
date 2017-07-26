drop function commissions.gl_Period_Type;
create function commissions.gl_Period_Type
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			20-Jul-2017
*
* @describe		Returns the period type for a given period
*
* @param		integer		pn_Period_id 		Commission Period
*
* @return		integer		Period_Type_id
*
* @example		call Commissions.gl_Period_Type(10);
-------------------------------------------------------*/
(pn_Period_id	integer)
returns ln_Result integer
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

BEGIN
	select period_type_id
	into ln_Result
	from Period
	where period_id = :pn_Period_id;
	
END;
