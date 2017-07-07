drop function commissions.gl_Period_Viewable;
create function commissions.gl_Period_Viewable(
						 pn_Period_id	integer)
returns ln_Result integer
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		22-May-2017

Purpose:	Returns viewable batchid

-------------------------------------------------------------------------------- */

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