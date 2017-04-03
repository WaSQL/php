drop procedure Commissions.Volume_Pv_Set;
create procedure Commissions.Volume_Pv_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	replace customer (customer_id, vol_1, vol_6)
	Select 
	      c.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction_log t, customer c
	Where t.customer_id = c.customer_id
	And t.period_id = 0
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By c.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);
   	
   	commit;

end;
