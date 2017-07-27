drop function Commissions.fn_Exchange_Rate;
CREATE function Commissions.fn_Exchange_Rate(
					 ps_From_Currency	varchar(5)
					,ps_To_Currency		varchar(5)
					,pn_Period_id		integer default 0)
returns ret_Rate double
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
   	--READS SQL DATA
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		29-Mar-2017

Purpose:	Returns the exchange rate between two currencies

-------------------------------------------------------------------------------- */

begin
	declare ln_Rate1 double;
 	declare le_Error nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			ret_Rate = 1;
		end;
		
	-- 'From' and 'To' Currencies Equal
	if upper(:ps_From_Currency) = upper(:ps_To_Currency) then
		ret_Rate = 1;
	-- 'From' and 'To' Currencies don't Equal
	else
		select t.rate / f.rate
		into ret_Rate
		from gl_Exchange(ifnull(:pn_Period_id,0)) f, gl_Exchange(ifnull(:pn_Period_id,0)) t
		where upper(f.currency) = upper(:ps_From_Currency)
		and upper(t.currency) = upper(:ps_To_Currency);
	end if;
	
end;
