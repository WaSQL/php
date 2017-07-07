drop function Commissions.gl_Exchange;
CREATE function Commissions.gl_Exchange(
						pn_Period_id	integer default 0)
returns table (
			 Currency			varchar(5)
			,Rate				decimal(18,8)
			,Round_Factor		integer)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		29-Mar-2017

Purpose:	Returns the exchange rates of all currencies, given a period

-------------------------------------------------------------------------------- */

begin
	declare ld_End_date		date;
	
	select end_date
	into ld_End_date
	from period
	where period_id = 
		case when ifnull(:pn_Period_id,0) = 0 then
			(select period_id
			 from period
			 where period_type_id = 1
			 and closed_date is null)
		else
			:pn_Period_id
		end;

	return
	select 
		 c.currency_code							as Currency
		,ifnull(b.Rate,1)							as Rate
		,ifnull(b.Round_Factor,2)					as Round_Factor
	from currency c
		left outer join (select * from (
			select rank() over (partition by currency order by effective_date desc) as rn, e.*
			from exchange e
			where e.effective_date <= ld_End_date) a where a.rn = 1) b
			on c.currency_code = b.currency;
	
end;
