DROP PROCEDURE SP_VOLUME_EGV_SET;
create procedure Commissions.sp_Volume_Egv_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_egv = current_timestamp
      ,end_date_volume_egv = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		replace customer (customer_id, vol_11, vol_12)
		select 
			 c.customer_id
			,sum(ifnull(c.vol_1,0) + ifnull(c.vol_4,0)) + 
				ifnull((select sum(ifnull(vol_1,0) + ifnull(vol_4,0)) 
				from customer 
				where rank_high_id < 5
				and enroller_id = c.customer_id),0) as pv_egv
			,sum(ifnull(c.vol_2,0)) + 
				ifnull((select sum(ifnull(vol_2,0)) 
				from customer 
				where rank_high_id < 5
				and enroller_id = c.customer_id),0) as pv_egv_lrp
		from customer c
		where c.country = 'KOR'
		group by c.customer_id;
	else
		replace customer_history (period_id, batch_id, customer_id, vol_11, vol_12)
		select 
			 c.period_id
		    ,c.batch_id
			,c.customer_id
			,sum(ifnull(c.vol_1,0) + ifnull(c.vol_4,0)) + 
				ifnull((select sum(ifnull(vol_1,0) + ifnull(vol_4,0))
				from customer_history 
				where period_id = c.period_id
	   			and batch_id = c.batch_id
				and rank_high_id < 5
				and enroller_id = c.customer_id),0) as pv_egv
			,sum(ifnull(c.vol_2,0)) + 
				ifnull((select sum(ifnull(vol_2,0))
				from customer_history 
				where period_id = c.period_id
	   			and batch_id = c.batch_id
				and rank_high_id < 5
				and enroller_id = c.customer_id),0) as pv_egv_lrp
		from customer_history c
		where c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id
		and c.country = 'KOR'
		group by c.period_id, c.batch_id, c.customer_id;
	end if;
	   	
	commit;
   
   	Update period_batch
   	Set end_date_volume_egv = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;