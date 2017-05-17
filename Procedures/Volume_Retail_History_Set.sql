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
   	
   	/*
   	lc_Cust =
   		select *
   		from customer_history
   		where period_id = :pn_Period_id
   		and batch_id = :pn_Period_Batch_id;
   		
   	lc_Exchange =
   		select *
   		from fn_exchange(:pn_Period_id);
	*/
               
    -- Rollup Retail Volume
	replace customer_history (period_id,batch_id,customer_id, vol_4, vol_9)
	select
		 period_id
		,batch_id
		,customer_id
		,sum(pv)
		,sum(cv)
	from fn_Volume_Pv_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id)
	group by period_id,batch_id,customer_id;
	
	/*
	Select 
		 d.period_id
		,d.batch_id
		,d.customer_id
	    ,ifnull(sum(a.vol_1),0) as pv
	    ,ifnull(sum(a.vol_6*(x1.rate/x2.rate)),0) as cv
	From  :lc_Cust d
		  left outer join :lc_Exchange x1
		  on x1.currency = d.currency
		  left outer join customer_type t1
		  on d.type_id = t1.type_id
		, :lc_Cust a
		  left outer join :lc_Exchange x2
		  on x2.currency = a.currency
		  left outer join customer_type t2
		  on a.type_id = t2.type_id
	Where d.customer_id = a.sponsor_id
	And ifnull(t2.has_retail,-1) = 1
	And ifnull(t1.has_downline,-1) = 1
	Group By d.period_id,d.batch_id,d.customer_id
	having (ifnull(sum(a.vol_1),0) != 0
	    or  ifnull(sum(a.vol_6),0) != 0);
	*/
	    
	-- Zero out Reail Volume
	replace customer_history (period_id,batch_id,customer_id, vol_1, vol_6)
	select c.period_id,c.batch_id,c.customer_id, 0, 0
	from customer_history c
		  left outer join customer_type t1
		  on c.type_id = t1.type_id
	where ifnull(t1.has_retail,-1) = 1
	and c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_retail = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

End;
