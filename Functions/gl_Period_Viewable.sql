drop function commissions.gl_Period_Viewable;
create function commissions.gl_Period_Viewable
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			22-May-2017
*
* @describe		Returns the viewable batchid
*
* @param		integer		pn_Period_id 		Commission Period
*
* @return		integer		Batch_id
*
* @example		call Commissions.gl_Period_Viewable(10);
-------------------------------------------------------*/
(pn_Period_id	integer)
returns ln_Result integer
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

BEGIN
	declare exit handler for sqlexception 
		begin
			ln_Result = 0;
		end;
	
	select batch_id
	into ln_Result
	from period_batch
	where period_id = :pn_Period_id
	and viewable = 1;
	
END;
