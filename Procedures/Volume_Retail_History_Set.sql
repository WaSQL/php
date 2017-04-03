drop Procedure Commissions.Volume_Retail_History_Set;
create Procedure Commissions.Volume_Retail_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	Update period_batch
	Set beg_date_volume_retail = current_timestamp
      ,end_date_volume_retail = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	lc_Cust =
   		select *
   		from customer_history
   		where period_id = :pn_Period_id
   		and batch_id = :pn_Period_Batch_id;
               
	replace customer_history (period_id,batch_id,customer_id, vol_4, vol_9)
	Select 
		 d.period_id
		,d.batch_id
		,d.customer_id
	    ,ifnull(sum(a.vol_1),0) as pv
	    ,ifnull(sum(a.vol_6),0) as cv
	From :lc_Cust d, :lc_Cust a
	Where d.customer_id = a.sponsor_id
	And a.type_id In (2,3)
	And d.type_id = 1
	Group By d.period_id,d.batch_id,d.customer_id
	having (ifnull(sum(a.vol_1),0) != 0
	    or  ifnull(sum(a.vol_6),0) != 0);
	    
	update customer_history
	set vol_1 = 0
	  , vol_6 = 0
	where type_id In (2,3)
	and period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_retail = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

End;
