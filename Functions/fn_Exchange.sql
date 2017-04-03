drop function Commissions.fn_Exchange;
CREATE function Commissions.fn_Exchange()
returns table (
			 Exchange_id 		integer
			,Effective_date		date
			,Currency			varchar(5)
			,Rate				decimal(18,8)
			,Round_Factor		integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	return
	select 
		 Exchange_id
		,Effective_date
		,Currency
		,Rate
		,Round_Factor
	from (
		select rank() over (partition by currency order by effective_date desc) as rn, e.*
		from exchange e)
	where rn = 1;
	
end;
