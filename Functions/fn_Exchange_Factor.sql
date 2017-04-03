drop function Commissions.fn_Exchange_Factor;
CREATE function Commissions.fn_Exchange_Factor(
					 ps_Currency	varchar(5))
returns ret_Factor integer
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Factor	integer;
	declare exit handler for sqlexception 
		begin
			ret_Factor = 2;
		end;
		
	select round_factor
	into ln_Factor
	from (
		select rank() over (partition by currency order by effective_date desc) as rn, e.*
		from exchange e)
	where rn = 1
	and upper(currency) = upper(:ps_Currency);
	
	ret_Factor = ifnull(:ln_Factor,2);
	
end;
