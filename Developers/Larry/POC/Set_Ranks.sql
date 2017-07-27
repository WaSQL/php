drop procedure set_ranks;
create procedure set_ranks(
                 pn_Period_id   Integer)
LANGUAGE SQLSCRIPT AS

begin
	
	declare ln_max_level	integer;
	declare ln_dst_level	integer;
	
	-- Update Status
   	Update comm_period
   	Set date_srt_rank = current_timestamp
       ,date_end_rank = Null
   	Where period_id = :pn_Period_id;
                  
   	Commit;
   	
    lc_dist = 
        select 
			 d.period_id    as period_id
			,d.dist_id 	    as dist_id
			,d.sponsor_id 	as sponsor_id
			,c.level		as level_id
			,c.path         as path
			,c.is_leaf		as is_leaf
		from sponsor_tree_dn c, comm_dist d
		where c.result_node = d.dist_id
		and d.period_id = :pn_Period_id
		and c.query_node != 0;
        
    select max(level_id)
    into ln_max_level
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
	
	-- Process All Distributors From the Bottom Up
	for ln_dst_level in reverse 0..:ln_max_level do
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
        and d.level_id = :ln_dst_level
        group by d1.period_id,d1.sponsor_id,d2.pv;
        
        commit;
        
	    lr_dst_level = 
			select -- Find Distributors matching requirments
				 d.period_id, d.dist_id, d.enroller_id, max(q.rank_id) as new_rank_id, 1 as rank_qual
			from :lc_dist h, comm_dist d, comm_ranks_qual q
			where d.period_id = :pn_Period_id
			and h.dist_id = d.dist_id
			and d.rank_id = 0
		   	And d.dist_type_id = 1
    		and q.country_code = case when d.country_code = 'KOR' then 'KOR' else 'USA' end
		   	and h.level_id = :ln_dst_level
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
        and locate_regexpr('/' || s.dist_id || '/' in (select '/' || path || '/' from :lc_dist where dist_id = d.dist_id)) > 1;
            
		commit;
	end for;
	
	update comm_dist
	set rank_id = 1
	, rank_qual = 0
	where rank_id = 0;
   
	-- Update Status
   	Update comm_period
   	Set date_end_rank = current_timestamp
   	Where period_id = :pn_Period_id;
                  
   	Commit;

end