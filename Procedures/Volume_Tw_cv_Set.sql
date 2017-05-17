drop procedure Commissions.Volume_Tw_cv_Set;
create procedure Commissions.Volume_Tw_cv_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_id	integer;
	
	select period_id
	into ln_Period_id
	from fn_period(1)
	where beg_date <= current_date
	and end_date >= current_date;
	
	replace customer (customer_id, vol_15)
	select
		 customer_id
		,sum(cv)
	from fn_Volume_Tw_Cv_Detail(:ln_Period_id, 0)
	group by customer_id;
	
	/*
	Select 
	      customer_id
	     ,sum(ifnull(cv,0)) As tw_cv
	From fn_Volume_Pv_Detail(:ln_Period_id)
	Where customer_id = :ln_Period_id
   	and upper(from_country) = 'TWN'
   	group by customer_id;
   	*/
   	
   	commit;
   	
   	replace customer (customer_id, vol_15)
   	select 
   		 1						as customer_id
   		,round(sum(vol_15),2)	as vol_15
   	from customer;
   	
   	commit;

end;
