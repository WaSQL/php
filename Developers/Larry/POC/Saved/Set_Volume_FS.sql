drop procedure set_volume_fs;
create procedure set_volume_fs(
					pn_Period_id	integer)
LANGUAGE SQLSCRIPT AS

begin
	Update comm_period
	Set date_srt_vol_fs = current_timestamp
      ,date_end_vol_fs = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
   
	replace comm_dist (PERIOD_ID,DIST_ID, PV_FS, CV_FS)
	Select 
		 d.period_id
		,d.dist_id 
		,Sum(o.pv) As pv_fs
		,Sum(o.cv) As cv_fs
	From comm_orders o, comm_dist d
   	Where o.dist_id = d.dist_id
   	And d.period_id = o.period_id
   	And o.period_id = :pn_Period_id
   	And days_between(d.entry_date,o.entry_date) <= 60
   	Group By d.period_id,d.dist_id;
   	
   	commit;
   
   	Update comm_period
   	Set date_end_vol_fs = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;

end;