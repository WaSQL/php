drop procedure Commissions.Volume_Pv_Set;
create procedure Commissions.Volume_Pv_Set()
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
	
	replace customer (customer_id, vol_1, vol_6)
	Select 
	      t.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction t
	Where t.period_id = :ln_Period_id
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By t.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);
   	
   	commit;

end;
