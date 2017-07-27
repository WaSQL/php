drop Procedure Commissions.sp_Volume_Retail_Set;
create Procedure Commissions.sp_Volume_Retail_Set(
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
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		replace customer (customer_id, vol_4, vol_9)
		select
			 customer_id
			,sum(pv)
			,sum(cv)
		from gl_Volume_Retail_Detail(:pn_Period_id, 0)
		group by customer_id;
		    
		update customer
		set vol_1 = 0
		  , vol_6 = 0
		where type_id In (2,3);
	else
		-- Rollup Retail Volume
		replace customer_history (period_id,batch_id,customer_id, vol_4, vol_9)
		select
			 period_id
			,batch_id
			,customer_id
			,sum(pv)
			,sum(cv)
		from gl_Volume_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id)
		group by period_id,batch_id,customer_id;
	
		-- Zero out Reail Volume
		replace customer_history (period_id,batch_id,customer_id, vol_1, vol_6)
		select c.period_id,c.batch_id,c.customer_id, 0, 0
		from customer_history c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
		where ifnull(t1.has_retail,-1) = 1
		and c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id;
	end if;
	   	
	commit;
   
   	Update period_batch
   	Set end_date_volume_retail = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

End;
