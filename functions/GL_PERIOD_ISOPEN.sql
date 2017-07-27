drop function commissions.gl_Period_isOpen;
create function commissions.gl_Period_isOpen
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			22-May-2017
*
* @describe		Returns a boolean value:
*				1 - Open
*				0 - Closed
*
* @param		integer		pn_Period_id 		Commission Period
*
* @return		Boolean		isOpen
*
* @example		call Commissions.gl_Period_isOpen(10);
-------------------------------------------------------*/
(pn_Period_id	integer)
returns ln_Result integer
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

BEGIN
	select map(count(*),0,0,1)
	into ln_Result
	from period
	where period_id = :pn_Period_id
	and closed_date is null;
END;
