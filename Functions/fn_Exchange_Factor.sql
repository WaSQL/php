drop function Commissions.fn_Exchange_Factor;
CREATE function Commissions.fn_Exchange_Factor(
					  ps_Currency	varchar(5)
					 ,pn_Period_id	integer default 0)
returns ret_Factor integer
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		29-Mar-2017

Purpose:	Returns the currency rounding factor

-------------------------------------------------------------------------------- */

begin
	declare ln_Factor	integer;
	declare exit handler for sqlexception 
		begin
			ret_Factor = 2;
		end;
		
	select round_factor
	into ln_Factor
	from fn_exchange(ifnull(:pn_Period_id,0))
	where upper(currency) = upper(:ps_Currency);
	
	ret_Factor = ifnull(:ln_Factor,2);
	
end;
