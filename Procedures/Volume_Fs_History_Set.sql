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
   
	replace customer_history (period_id, batch_id, customer_id, vol_5, vol_10)
	select
		 period_id
		,batch_id
		,customer_id
		,sum(pv)
		,sum(cv)
	from fn_Volume_Pv_Fs_Detail(:pn_Period_id, :pn_Period_Batch_id)
	group by period_id,batch_id,customer_id;
	
	/*
	Select 
		  c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,Sum(ifnull(t.pv,0)) As pv
	     ,Sum(ifnull(t.cv,0)) As cv
	From  fn_Volume_Pv_Detail(:pn_Period_id) t
		  left outer join fn_Volume_Pv_Detail(:pn_Period_id) r
		  on t.transaction_ref_id = r.transaction_id
		, customer_history c
		  left outer join customer_type t1
		  on c.type_id = t1.type_id
   	Where t.customer_id = c.customer_id
   	And t.period_id = c.period_id
   	And c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
   	And ifnull(t1.has_downline,-1) = 1
   	and ifnull(t.transaction_type_id,4) <> 0
   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) <= 60
   	Group By c.period_id, c.batch_id, c.customer_id;
   	*/
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_fs = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
