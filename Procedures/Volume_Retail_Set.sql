drop Procedure Commissions.Volume_Retail_Set;
create Procedure Commissions.Volume_Retail_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	declare ln_Period_id	integer;
	
	select period_id
	into ln_Period_id
	from fn_period(1)
	where beg_date <= current_date
	and end_date >= current_date;
	
	replace customer (customer_id, vol_4, vol_9)
	select
		 customer_id
		,sum(pv)
		,sum(cv)
	from fn_Volume_Pv_Retail_Detail(:ln_Period_id, 0)
	group by customer_id;
	
	/*
	lc_Cust =
   		select *
   		from customer;
               
	Select 
		 d.customer_id
	    ,ifnull(sum(a.vol_1),0) as pv
	    ,ifnull(sum(a.vol_6),0) as cv
	From :lc_Cust d, :lc_Cust a
	Where d.customer_id = a.sponsor_id
	And a.type_id In (2,3)
	And d.type_id = 1
	Group By d.customer_id
	having (ifnull(sum(a.vol_1),0) != 0
	    or  ifnull(sum(a.vol_6),0) != 0);
	*/
	    
	update customer
	set vol_1 = 0
	  , vol_6 = 0
	where type_id In (2,3);
   	
   	commit;

End;
