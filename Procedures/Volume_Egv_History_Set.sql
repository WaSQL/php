drop procedure Commissions.Volume_Egv_History_Set;
create procedure Commissions.Volume_Egv_History_Set(
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
   
	replace customer_history (period_id, batch_id, customer_id, vol_10)
	select 
		 c.period_id
	    ,c.batch_id
		,c.customer_id
		,sum(ifnull(c.vol_1,0) + ifnull(c.vol_4,0)) + 
			(select sum(ifnull(vol_1,0) + ifnull(vol_4,0))
			from customer_history 
			where period_id = :pn_Period_id
   			and batch_id = :pn_Period_Batch_id
			and rank_high_id < 5
			and enroller_id = c.customer_id) as pv_egv
	from customer_history c
	where c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
	and c.country = 'KOR'
	group by c.period_id, c.batch_id, c.customer_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_egv = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
