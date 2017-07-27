drop procedure Commissions.sp_Volume_Pv_Set;
create procedure Commissions.sp_Volume_Pv_Set(
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
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		replace customer (customer_id, vol_1, vol_6)
		select
			 customer_id
			,sum(pv)
			,sum(cv)
		from gl_Volume_Pv_Detail(:pn_Period_id,0)
		group by customer_id;
	else
	   	replace customer_history (period_id, batch_id, customer_id, vol_1, vol_6)
		select 
			 period_id
			,batch_id
			,customer_id
			,sum(pv)
			,sum(cv)
		from gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id)
		group by period_id, batch_id, customer_id;
	end if;
	
	commit;
   
   	Update period_batch
   	Set end_date_volume = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
