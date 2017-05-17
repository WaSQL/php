drop procedure Commissions.Volume_Tw_cv_History_Set;
create procedure Commissions.Volume_Tw_cv_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_tw_cv = current_timestamp
      ,end_date_volume_tw_cv = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
	replace customer_history (period_id, batch_id, customer_id, vol_15)
	select
		 period_id
		,batch_id
		,customer_id
		,sum(cv)
	from fn_Volume_Tw_Cv_Detail(:pn_Period_id, :pn_Period_Batch_id)
	group by period_id,batch_id,customer_id;
	
	
	/*
	Select 
	      c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,sum(ifnull(t.cv,0)) As tw_cv
	From fn_Volume_Pv_Detail(:pn_Period_id) t
	   , customer_history c
	Where c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
   	and t.period_id = c.period_id
   	and t.customer_id = c.customer_id
   	and upper(t.from_country) = 'TWN'
   	group by c.period_id, c.batch_id, c.customer_id;
   	*/
   	
   	commit;
   	
   	replace customer_history (period_id, batch_id, customer_id, vol_15)
   	select 
   		 period_id
   		,batch_id
   		,1								as customer_id
   		,round(sum(ifnull(vol_15,0)),2)	as vol_15
   	from customer_history
   	where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id
   	group by period_id, batch_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_tw_cv = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
