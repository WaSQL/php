drop procedure set_ranks;
create procedure set_ranks(
            pn_Period_id      Integer)
LANGUAGE SQLSCRIPT AS

begin
	
	declare ln_Rank			integer;
	declare ln_Qual			integer;
	declare ln_Leg_Count	integer;
	declare ln_Count		integer;
	declare ln_dst_level	integer;
	declare ln_dst			integer;
	declare ln_dstid		integer;
	declare ln_leg_rank_id	integer;
	declare ln_x			integer;
	declare req1_rank_id	integer array;
	declare req1_pv			double array;
	declare req1_ov			double array;
	declare req1_leg_rank	integer array;
	declare req1_leg_count	integer array;
	declare req1_egv		integer array;
	declare req2_rank_id	integer array;
	declare req2_pv			double array;
	declare req2_ov			double array;
	declare req2_leg_rank	integer array;
	declare req2_leg_count	integer array;
	declare req2_egv		integer array;
	
	-- Update Status
   	Update comm_period
   	Set date_srt_rank = current_timestamp
       ,date_end_rank = Null
   	Where period_id = :pn_Period_id;
                  
   	Commit;
	
	lc_Dist = 
		select *
		from (
			select 
				 d.period_id
				,d.dist_id
				,d.pv
				,d.pv_org
				,d.pv_egv
				,d.rank_id
				,d.country_code
				,0 as rank_qual
				,(select max(level_id) from comm_sponsor_hier where dist_id = d.dist_id) as level_id
			from comm_dist d
			where d.period_id = :pn_Period_id
			and d.rank_id = 0
		   	And d.dist_type_id = 1)
		order by level_id desc;
	   	
	-- Get Rank Requirements for All Others
   	lc_Rank_Req_USA =
	   	Select rank_id ,pv ,ov ,leg_rank ,leg_count ,egv
	   	From comm_ranks_qual
	   	Where country_code = 'USA';
	
	req1_rank_id = array_agg(:lc_Rank_Req_USA.rank_id order by rank_id);
	req1_pv = array_agg(:lc_Rank_Req_USA.pv order by rank_id);
	req1_ov = array_agg(:lc_Rank_Req_USA.ov order by rank_id);
	req1_leg_rank = array_agg(:lc_Rank_Req_USA.leg_rank order by rank_id);
	req1_leg_count = array_agg(:lc_Rank_Req_USA.leg_count order by rank_id);
	req1_egv = array_agg(:lc_Rank_Req_USA.egv order by rank_id);
	
   	-- Get Rank Requirements for Korea
   	lc_Rank_Req_KOR =
	   	Select rank_id ,pv ,ov ,leg_rank ,leg_count ,egv
	   	From comm_ranks_qual
	   	Where country_code = 'KOR';
	
	req2_rank_id = array_agg(:lc_Rank_Req_KOR.rank_id order by rank_id);
	req2_pv = array_agg(:lc_Rank_Req_KOR.pv order by rank_id);
	req2_ov = array_agg(:lc_Rank_Req_KOR.ov order by rank_id);
	req2_leg_rank = array_agg(:lc_Rank_Req_KOR.leg_rank order by rank_id);
	req2_leg_count = array_agg(:lc_Rank_Req_KOR.leg_count order by rank_id);
	req2_egv = array_agg(:lc_Rank_Req_KOR.egv order by rank_id);
	
	-- Process All Distributors From the Bottom Up
	for ln_dst in 1..cardinality(array_agg(:lc_Dist.dist_id)) do
		ln_dstid = :lc_Dist.dist_id[:ln_dst];
		lc_Sponsored = 
			select *
			from comm_dist
			where period_id = :pn_Period_id
			and sponsor_id = :ln_dstid;
			
		if :lc_Dist.country_code[:ln_dst] != 'KOR' then
			-- Find Distributors matching requirments [All Markets]
			for ln_x in reverse 1..cardinality(:req1_rank_id) do
				ln_leg_rank_id = :req1_leg_rank[:ln_x];
				
				select count(dist_id)
				into ln_Leg_Count
				from :lc_Sponsored
				where leg_rank_id >= :ln_leg_rank_id;
					
				if      :lc_Dist.pv[:ln_dst] >= :req1_pv[:ln_x]
					and :lc_Dist.pv_org[:ln_dst] >= :req1_ov[:ln_x]
					and :ln_Leg_Count >= :req1_leg_count[:ln_x]
				then
					update comm_dist
					set  rank_id = :ln_x
						,rank_qual = 1
					where period_id = :pn_Period_id
					and dist_id = :ln_dstid;
					commit;
					
					replace comm_dist (PERIOD_ID,DIST_ID, LEG_RANK_DIST_ID, LEG_RANK_ID)
					select 
						 d1.period_id					as Period_id
						,h2.root_id 					as Dist_id
						,d1.dist_id						as Leg_Rank_Dist_ID
						,d1.rank_id						as Leg_Rank_id
					from comm_dist d1, comm_dist d2, comm_sponsor_hier h1, comm_sponsor_hier h2
					where d1.period_id = :pn_Period_id
					and d2.period_id = :pn_Period_id
					and d1.dist_id = :ln_dstid
					and d1.dist_id = h1.dist_id
					and d1.enroller_id = h1.root_id
					and h1.dist_id = h2.dist_id
					and h1.level_id-1 = h2.level_id
					and h2.root_id = d2.dist_id
					and d1.rank_id >= 4
					and ifnull(d2.leg_rank_id,0) < d1.rank_id;
					commit;
					
					break;
				end if;
			end for;
		else
			-- Find Distributors matching requirments [Korean Market]
			for ln_x in reverse 1..cardinality(:req2_rank_id) do
				ln_leg_rank_id = :req2_leg_rank[:ln_x];
				
				select count(dist_id)
				into ln_Leg_Count
				from :lc_Sponsored
				where leg_rank_id >= :ln_leg_rank_id;
					
				if      (:lc_Dist.pv[:ln_dst] >= :req2_pv[:ln_x]
					 or  :lc_Dist.pv_egv[:ln_dst] >= :req2_egv[:ln_x])
					and :lc_Dist.pv_org[:ln_dst] >= :req2_ov[:ln_x]
					and :ln_Leg_Count >= :req2_leg_count[:ln_x]
				then
					update comm_dist
					set  rank_id = :ln_x
						,rank_qual = 1
					where period_id = :pn_Period_id
					and dist_id = :ln_dstid;
					commit;
					
					replace comm_dist (PERIOD_ID,DIST_ID, LEG_RANK_DIST_ID, LEG_RANK_ID)
					select 
						 d1.period_id					as Period_id
						,h2.root_id 					as Dist_id
						,d1.dist_id						as Leg_Rank_Dist_ID
						,d1.rank_id						as Leg_Rank_id
					from comm_dist d1, comm_dist d2, comm_sponsor_hier h1, comm_sponsor_hier h2
					where d1.period_id = :pn_Period_id
					and d2.period_id = :pn_Period_id
					and d1.dist_id = :ln_dstid
					and d1.dist_id = h1.dist_id
					and d1.enroller_id = h1.root_id
					and h1.dist_id = h2.dist_id
					and h1.level_id-1 = h2.level_id
					and h2.root_id = d2.dist_id
					and d1.rank_id >= 4
					and ifnull(d2.leg_rank_id,0) < d1.rank_id;
					commit;
					
					break;
				end if;
			end for;
		end if;
	end for;
				
				--if :ln_x >= 4 then
					/*replace comm_dist (PERIOD_ID,DIST_ID, LEG_RANK_DIST_ID, LEG_RANK_ID)
					select 
						 d1.period_id					as Period_id
						,h2.root_id 					as Dist_id
						,d1.dist_id						as Leg_Rank_Dist_ID
						,d1.rank_id						as Leg_Rank_id
					from :lr_dst_level d1, comm_dist d2, comm_sponsor_hier h1, comm_sponsor_hier h2
					where d1.dist_id = h1.dist_id
					and d1.enroller_id = h1.root_id
					and h1.dist_id = h2.dist_id
					and h1.level_id-1 = h2.level_id
					and h2.root_id = d2.dist_id
					and d1.rank_id >= 4
					and ifnull(d2.leg_rank_id,0) < d1.rank_id;
					
					commit;*/
				--end if;
			--end if;
	
	update comm_dist
	set rank_id = 1
	, rank_qual = 0
	where rank_id = 0;
   
	-- Update Status
   	Update comm_period
   	Set date_end_rank = current_timestamp
   	Where period_id = :pn_Period_id;
                  
   	Commit;

end;
