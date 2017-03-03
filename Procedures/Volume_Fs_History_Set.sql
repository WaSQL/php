drop procedure Commissions.Volume_Fs_History_Set;
create procedure Commissions.Volume_Fs_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_fs = current_timestamp
      ,end_date_volume_fs = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
	replace customer_history (period_id, batch_id, customer_id, vol_5)
	Select 
		  c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     --,Sum(ifnull(t.value_4,0)) As cv
	From transaction_log t, customer_history c
   	Where t.customer_id = c.customer_id
   	And t.period_id = c.period_id
   	And c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
   	and c.type_id = 1
   	and ifnull(t.transaction_type_id,4) <> 0
   	And days_between(c.entry_date,t.transaction_date) <= 60
   	Group By c.period_id, c.batch_id, c.customer_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_fs = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
