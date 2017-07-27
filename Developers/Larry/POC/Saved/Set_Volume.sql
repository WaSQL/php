drop procedure set_volume;
create procedure set_volume(
					pn_Period_id	integer)
LANGUAGE SQLSCRIPT AS

begin
	Update comm_period
	Set date_srt_vol = current_timestamp
      ,date_end_vol = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
   
	replace comm_dist (PERIOD_ID,DIST_ID, PV, CV)
	Select 
	      d.PERIOD_ID
	     ,d.DIST_ID
	     ,Sum(ifnull(o.PV,0)) As pv
	     ,Sum(ifnull(o.CV,0)) As cv
	From comm_orders o, comm_dist d
	Where o.DIST_ID = d.DIST_ID
	And o.PERIOD_ID = d.PERIOD_ID
	And d.PERIOD_ID = :pn_Period_id
    Group By d.PERIOD_ID, d.DIST_ID
    having (Sum(ifnull(o.PV,0)) != 0
		or  Sum(ifnull(o.CV,0)) != 0);
   	
   	commit;
   
   	Update comm_period
   	Set date_end_vol = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;

end;