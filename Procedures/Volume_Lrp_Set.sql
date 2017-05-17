drop procedure Commissions.Volume_Lrp_Set;
create procedure Commissions.Volume_Lrp_Set()
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
	
	replace customer (customer_id, vol_2, vol_7)
	select
		 t.customer_id
		,sum(t.pv)
		,sum(t.cv)
	from fn_Volume_Pv_Lrp_Detail(:ln_Period_id, 0) t
	Group By t.customer_id;
	
	/*
	Select 
	      t.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction t
	Where case when t.transaction_type_id = 2 then 
   		(select ifnull(a.transaction_category_id,1)
   		 from transaction a
   		 where a.transaction_id = t.transaction_ref_id)
   		 else ifnull(t.transaction_category_id,1) end in (3,6)
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By t.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);
	*/
   	
   	commit;

end;
