drop procedure set_volume_egv; 
create procedure set_volume_egv(
					pn_Period_id	integer)
LANGUAGE SQLSCRIPT AS

begin
	Update comm_period
	Set date_srt_vol_egv = current_timestamp
      ,date_end_vol_egv = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
   
	replace comm_dist (PERIOD_ID,DIST_ID, PV_EGV)
	select 
		 d.period_id
		,d.dist_id
		,ifnull(sum(d.pv),0) + (select ifnull(sum(pv),0) 
								from comm_dist 
								where period_id = :pn_Period_id
								and high_rank_id < 5 
								and enroller_id = d.dist_id) as pv_egv
	from comm_dist d
	where d.period_id = :pn_Period_id
	and d.country_code = 'KOR'
	group by d.period_id, d.dist_id;
   	
   	commit;
   
   	Update comm_period
   	Set date_end_vol_egv = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;

end;