create Procedure Set_Volume_Org(
                 pn_Period_id	integer
)
LANGUAGE SQLSCRIPT AS
    
Begin
    declare ln_max      integer;
    declare ln_x        integer;
    
    Update comm_period
   	Set date_srt_vol_org = current_timestamp
   	   ,date_end_vol_org = Null
   	Where period_id = :pn_Period_id;
   	
   	commit;
    
    lc_dist = 
        select 
			 d.period_id    as period_id
			,d.dist_id 	    as dist_id
			,d.sponsor_id 	as sponsor_id
			,c.level		as level_id
			,c.is_leaf		as is_leaf
		from sponsor_tree_dn c, comm_dist d
		where c.result_node = d.dist_id
		and d.period_id = :pn_Period_id
		and c.query_node != 0;
        
    select max(level_id)
    into ln_max
    from :lc_dist;
    
    replace comm_dist (PERIOD_ID,DIST_ID, PV_ORG)
    select 
         d1.period_id   as period_id
        ,d1.dist_id     as dist_id
        ,d1.pv          as pv_org
    from :lc_dist d, comm_dist d1
    where d.dist_id = d1.dist_id
    and d.period_id = d1.period_id
    and d.is_leaf = 1;
    
    commit;
    
    for ln_x in reverse 0..:ln_max do
        replace comm_dist (PERIOD_ID,DIST_ID, PV_ORG)
        select 
            d1.period_id             as period_id
           ,d1.sponsor_id            as dist_id
           ,sum(d1.pv_org) + d2.pv   as pv_org
        from :lc_dist d, comm_dist d1, comm_dist d2
        where d.dist_id = d1.dist_id
        and d.sponsor_id = d2.dist_id
        and d.period_id = d1.period_id
        and d.period_id = d2.period_id
        and d.level_id = :ln_x
        group by d1.period_id,d1.sponsor_id,d2.pv;
        
        commit;
        
    end for;
   
   	Update comm_period
   	Set date_end_vol_org = current_timestamp
   	Where period_id = :pn_Period_id;
   	
   	commit;
   	
End