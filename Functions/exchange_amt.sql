drop function Commissions.fn_Exchange_Rate;
CREATE function Commissions.fn_Exchange_Rate(
					 ps_From_Currency	varchar
					,ps_To_Currency		varchar)
returns ret_Rate double
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
   	READS SQL DATA
AS

begin
	declare ln_Rate1 double;
	
	if upper(:ps_To_Currency) = 'USD' then
		select rate
		into ret_Rate
		from (
			select rank() over (partition by currency order by effective_date desc) as rn, e.*
			from exchange e)
		where rn = 1
		and upper(currency) = upper(:ps_From_Currency);
	else
	
	end if;
	
end;
