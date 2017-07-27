drop Procedure Set_Volume_Retail;
create Procedure Set_Volume_Retail(
              pn_Period_id	integer)
LANGUAGE SQLSCRIPT AS
    
Begin
   	Update comm_period
   	Set date_srt_vol_retail = current_timestamp
   	   ,date_end_vol_retail = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
               
	replace comm_dist (PERIOD_ID,DIST_ID, PV, CV, PV_FS, CV_FS, PV_RETAIL, CV_RETAIL)
	Select 
		 d.period_id
		,d.dist_id
	    ,d.pv + ifnull(sum(a.pv),0) as pv
	    ,d.cv + ifnull(sum(a.cv),0) as cv
	    ,d.pv_fs + ifnull(sum(a.pv_fs),0) As pv_fs
	    ,d.cv_fs + ifnull(sum(a.cv_fs),0) As cv_fs
	    ,ifnull(sum(a.pv),0) As pv_retail
	    ,ifnull(sum(a.cv),0) As cv_retail
	From comm_dist d, comm_dist a
	Where d.dist_id = a.sponsor_id
	And d.period_id = a.period_id
	And d.period_id = :pn_Period_id
	And a.dist_type_id In (2,3)
	And d.dist_type_id In (1,6)
	Group By d.period_id,d.dist_id,d.pv,d.cv,d.pv_fs,d.cv_fs
	having (ifnull(sum(a.pv),0) > 0
	    or  ifnull(sum(a.cv),0) > 0);
   	
   	commit;
      
   	Update comm_dist
   	Set 
    	  pv = 0
    	, cv = 0
    	, pv_retail = 0
    	, cv_retail = 0
    	, pv_fs = 0
    	, cv_fs = 0
   	Where dist_type_id In (2,3);
   	
   	commit;
   
   	Update comm_period
   	Set date_end_vol_retail = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;

End;