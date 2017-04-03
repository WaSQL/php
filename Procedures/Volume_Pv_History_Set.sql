drop procedure Commissions.Volume_Pv_History_Set;
create procedure Commissions.Volume_Pv_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume = current_timestamp
      ,end_date_volume = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
	replace customer_history (period_id, batch_id, customer_id, vol_1, vol_6)
	Select 
	      c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction_log t, customer_history c
	Where t.customer_id = c.customer_id
	And t.period_id = c.period_id
	And c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By c.period_id, c.batch_id, c.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
