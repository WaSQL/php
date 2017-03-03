drop procedure Commissions.Volume_Egv_Set;
create procedure Commissions.Volume_Egv_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_egv = current_timestamp
      ,end_date_volume_egv = Null
   	Where period_id = 0
   	and batch_id = 0;
   	
   	commit;
   	
	replace customer (customer_id, vol_10)
	select 
		 c.customer_id
		,sum(ifnull(c.vol_1,0) + ifnull(c.vol_4,0)) + 
			(select sum(ifnull(vol_1,0) + ifnull(vol_4,0)) 
			from customer 
			where rank_high_id < 5
			and enroller_id = c.customer_id) as pv_egv
	from customer c
	where c.country = 'KOR'
	group by c.customer_id;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_egv = current_timestamp
   	Where period_id = 0
   	and batch_id = 0;
   	
   	commit;

end;
