drop procedure Commissions.sp_Volume_Tw_Set;
create procedure Commissions.sp_Volume_Tw_Set(
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
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		replace customer (customer_id, vol_15)
		select
			 customer_id
			,sum(cv)
		from gl_Volume_Tw_Detail(:pn_Period_id, 0)
		group by customer_id;
		
	   	commit;
	   	
	   	replace customer (customer_id, vol_15)
	   	select 
	   		 1						as customer_id
	   		,round(sum(vol_15),2)	as vol_15
	   	from customer;
	else
		replace customer_history (period_id, batch_id, customer_id, vol_15)
		select
			 period_id
			,batch_id
			,customer_id
			,sum(cv)
		from gl_Volume_Tw_Detail(:pn_Period_id, :pn_Period_Batch_id)
		group by period_id,batch_id,customer_id;
   	
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
	end if;
	   	
	commit;
   
   	Update period_batch
   	Set end_date_volume_tw_cv = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
