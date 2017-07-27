create procedure set_ranks_tree(
					 pn_Period_id	integer
                    ,pn_Dist_id     integer)
LANGUAGE SQLSCRIPT AS

begin
	
	declare ln_dist_id	    integer;
	declare ln_dst			integer;
	declare ln_pv           integer;
	declare ln_cv           integer;
	
	lc_Dist = 
        select 
             d.period_id
            ,t.dist_id
            ,d.pv_org
            ,t.level_id
        from hier_sponsor(:pn_Dist_id,1) t, comm_dist d
        where t.dist_id = d.dist_id
        order by level_id;
    
    --Store current values
    select pv
    into ln_pv
    from comm_dist
    where dist_id = :pn_Dist_id
    and period_id = :pn_Period_id;
        
    --Set Volume
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
	and d.dist_id = :pn_Dist_id
    Group By d.PERIOD_ID, d.DIST_ID
    having (Sum(ifnull(o.PV,0)) != 0
		or  Sum(ifnull(o.CV,0)) != 0);
   	
   	commit;
   	
   	--Set Fast Start Volume
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
   	and d.dist_id = :pn_Dist_id
   	And days_between(d.entry_date,o.entry_date) <= 60
   	Group By d.period_id,d.dist_id;
   	
   	commit;
   	
   	--Set Retail Volume
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
	and d.dist_id = :pn_Dist_id
	And a.dist_type_id In (2,3)
	And d.dist_type_id In (1,6)
	Group By d.period_id,d.dist_id,d.pv,d.cv,d.pv_fs,d.cv_fs
	having (ifnull(sum(a.pv),0) > 0
	    or  ifnull(sum(a.cv),0) > 0);
   	
   	commit;
   	
   	--Set EGV
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
	and d.dist_id = :pn_Dist_id
	and d.country_code = 'KOR'
	group by d.period_id, d.dist_id;
   	
   	commit;
   	
   	--Store delta values
    select pv-:ln_pv
    into ln_pv
    from comm_dist
    where dist_id = :pn_Dist_id
    and period_id = :pn_Period_id;
	    
    -- Set Org Volume
    replace comm_dist (PERIOD_ID,DIST_ID, PV_ORG)
    select 
         d.period_id
        ,d.dist_id
        ,d.pv_org + :ln_pv as pv_org
    from :lc_Dist d
    where d.period_id = :pn_Period_id;
    
    commit;
   	    
	-- Process All Distributors From the Bottom Up
	for ln_dst in 1..cardinality(array_agg(:lc_Dist.dist_id)) do
	    ln_dist_id = :lc_Dist.dist_id[:ln_dst];
	    
	    --Rollup Ranks
		lr_dst_level = 
			select -- Find Distributors matching requirments
				 d.period_id, d.dist_id, d.enroller_id, max(q.rank_id) as new_rank_id, 1 as rank_qual
			from comm_Dist d, comm_ranks_qual q
			where d.period_id = :pn_Period_id
			and d.dist_id = :ln_dist_id
		   	And d.dist_type_id = 1
    		and q.country_code = case when d.country_code = 'KOR' then 'KOR' else 'USA' end
			and (d.pv >= q.pv or
			     1 = 
			     case 
			        when d.country_code = 'KOR' 
			        then case when d.pv_egv >= q.egv then 1 else 0 end
			        else 0 end)
			and d.pv_org >= q.ov
			and (select count(c.dist_id)
				 from comm_dist c, (select period_id, dist_id, max(leg_rank_id) as leg_rank_id from comm_legs group by period_id, dist_id) l
				 where c.sponsor_id = d.dist_id
				 and c.period_id = d.period_id
				 and l.period_id = d.period_id
				 and c.dist_id = l.dist_id
				 and l.leg_rank_id >= q.leg_rank) >= q.leg_count
            group by d.period_id, d.dist_id, d.enroller_id;
		
		replace comm_dist (PERIOD_ID,DIST_ID, RANK_ID, RANK_QUAL)
		select period_id,dist_id, new_rank_id, rank_qual
		from :lr_dst_level;
		
		commit;
		
		replace comm_legs (PERIOD_ID,DIST_ID, LEG_DIST_ID, LEG_RANK_ID)
		select  
             d.period_id				as Period_id
            ,s.dist_id 					as Dist_id
            ,d.dist_id					as Leg_Dist_ID
            ,d.new_rank_id				as Leg_Rank_id
        from :lr_dst_level d, comm_dist e , comm_dist s
        where d.enroller_id = e.dist_id
        and s.sponsor_id = e.dist_id
        and d.new_rank_id >= 4
        and s.dist_id in (
            select dist_id
            from :lc_Dist);
		
		commit;
	end for;

end