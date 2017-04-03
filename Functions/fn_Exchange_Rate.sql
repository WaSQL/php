drop function Commissions.fn_Exchange_Rate;
CREATE function Commissions.fn_Exchange_Rate(
					 ps_From_Currency	varchar(5)
					,ps_To_Currency		varchar(5))
returns ret_Rate double
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
   	--READS SQL DATA
AS

begin
	declare ln_Rate1 double;
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			ret_Rate = 1;
		end;
		
	-- From and To Equal
	if upper(:ps_From_Currency) = upper(:ps_To_Currency) then
		ret_Rate = 1;
	else
		-- From and To Don't Equal
		-- From is USD
		if upper(:ps_From_Currency) = 'USD' then
			select rate
			into ret_Rate
			from (
				select rank() over (partition by currency order by effective_date desc) as rn, e.*
				from exchange e)
			where rn = 1
			and upper(currency) = upper(:ps_To_Currency);
			
		-- From and To Don't Equal
		-- From is not USD
		else
			select rate
			into ln_Rate1
			from (
				select rank() over (partition by currency order by effective_date desc) as rn, e.*
				from exchange e)
			where rn = 1
			and upper(currency) = upper(:ps_From_Currency);
			
			
			select rate / :ln_Rate1
			into ret_Rate
			from (
				select rank() over (partition by currency order by effective_date desc) as rn, e.*
				from exchange e)
			where rn = 1
			and upper(currency) = upper(:ps_To_Currency);
		end if;
	end if;
	
end;
