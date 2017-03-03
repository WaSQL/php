CREATE function Commissions.exchange_amt(
					 pn_Amount  	double
					,ps_Country		varchar)
returns ret_Amount double
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
   	READS SQL DATA
AS

begin
	declare ln_Rate double;
	
	select rate
	into ln_Rate
	from currency
	where country_code = :ps_Country;
	
	ret_Amount = :pn_Amount * ln_Rate;
	
end;