DROP PROCEDURE SP_VOLUME_LRP_SET;
create procedure Commissions.sp_Volume_Lrp_Set(
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
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		replace customer (customer_id, vol_2, vol_7)
		select
			 t.customer_id
			,sum(t.pv)
			,sum(t.cv)
		from gl_Volume_Lrp_Detail(:pn_Period_id, 0) t
		Group By t.customer_id;
	else
		replace customer_history (period_id, batch_id, customer_id, vol_2, vol_7)
		select
			 t.period_id
			,t.batch_id 
			,t.customer_id
			,sum(t.pv)
			,sum(t.cv)
		from gl_Volume_Lrp_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		Group By t.period_id, t.batch_id , t.customer_id;
   	end if;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_lrp = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;