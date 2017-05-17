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
   	
   	/*	
   	lc_Exchange =
   		select *
   		from fn_exchange(:pn_Period_id);
   	*/
   
	replace customer_history (period_id, batch_id, customer_id, vol_1, vol_6)
	select 
		 period_id
		,batch_id
		,customer_id
		,sum(pv)
		,sum(cv)
	from fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id)
	group by period_id, batch_id, customer_id;
	
	/*
	Select 
	      :pn_Period_id
	     ,:pn_Period_Batch_id
	     ,t.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0 * (x2.rate / x1.rate))) As cv
	From transaction t
		 left outer join :lc_Exchange x1
		 on x1.currency = t.currency
	   , customer_history c
		 left outer join :lc_Exchange x2
		 on x2.currency = c.currency
	Where t.customer_id = c.customer_id
	and c.period_id = :pn_Period_id
	and c.batch_id = :pn_Period_Batch_id
	and t.period_id = c.period_id
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By t.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);*/
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
