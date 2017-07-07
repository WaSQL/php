drop function commissions.gl_Period_isOpen;
create function commissions.gl_Period_isOpen(
						 pn_Period_id	integer)
returns ln_Result integer
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		22-May-2017

Purpose:	Returns a boolean value:
				1 - Open
				0 - Closed

-------------------------------------------------------------------------------- */

BEGIN
	select map(count(*),0,0,1)
	into ln_Result
	from period
	where period_id = :pn_Period_id
	and closed_date is null;
END;
