drop procedure Commissions.Volume_Fs_Set;
create procedure Commissions.Volume_Fs_Set()
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
	
	replace customer (customer_id, vol_5, vol_10)
	select
		 customer_id
		,sum(pv)
		,sum(cv)
	from fn_Volume_Pv_Fs_Detail(:ln_Period_id, 0)
	group by customer_id;
	
	/*
	Select 
		  c.customer_id
	     ,Sum(ifnull(t.pv,0)) As pv
	     ,Sum(ifnull(t.cv,0)) As cv
	From fn_Volume_Pv_Detail(:ln_Period_id) t
		left outer join fn_Volume_Pv_Detail(:ln_Period_id) r
			on t.transaction_ref_id = r.transaction_id
		, customer c
   	Where t.customer_id = c.customer_id
   	And t.period_id = 0
   	and c.type_id = 1
   	and ifnull(t.transaction_type_id,4) <> 0
   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) <= 60
   	Group By c.customer_id;
   	*/
   	
   	commit;

end;
