drop procedure Commissions.Volume_Lrp_History_Set;
create procedure Commissions.Volume_Lrp_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_lrp = current_timestamp
      ,end_date_volume_lrp = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
	
	replace customer_history (period_id, batch_id, customer_id, vol_2, vol_7)
	select
		 t.period_id
		,t.batch_id 
		,t.customer_id
		,sum(t.pv)
		,sum(t.cv)
	from fn_Volume_Pv_Lrp_Detail(:pn_Period_id, :pn_Period_Batch_id) t
	Group By t.period_id, t.batch_id , t.customer_id;
	
	/*
	Select 
	      :pn_Period_id
	     ,:pn_Period_Batch_id
	     ,t.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction t
	Where period_id = :pn_Period_id
   	and case when t.transaction_type_id = 2 then 
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
   
   	Update period_batch
   	Set end_date_volume_lrp = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
