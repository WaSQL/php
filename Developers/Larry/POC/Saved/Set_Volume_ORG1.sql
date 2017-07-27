create Procedure Set_Volume_Org1(
                 pn_Period_id	integer
                ,pc_Dist        table(root_id integer, dist_id integer, level_id integer)
)
LANGUAGE SQLSCRIPT AS
    
Begin
    Update comm_period
   	Set date_srt_vol_org = current_timestamp
   	   ,date_end_vol_org = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
   	
   	replace comm_dist (PERIOD_ID,DIST_ID, PV_ORG)
	select 
		 d.period_id	as period_id
		,h.root_id 		as dist_id
		,sum(d.pv) 		as pv_org
	from :pc_Dist h, comm_dist d
	where h.dist_id = d.dist_id
	--and d.dist_type_id in (1,6)
	and d.period_id = :pn_Period_id
	group by d.period_id, h.root_id;
   	
   	commit;
   
   	Update comm_period
   	Set date_end_vol_org = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;
   	

End