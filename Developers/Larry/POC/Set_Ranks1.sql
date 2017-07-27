drop procedure set_ranks;
create procedure set_ranks(
            pn_Period_id      Integer)
LANGUAGE SQLSCRIPT AS

begin
	
	declare ln_Rank			integer;
	declare ln_Qual			integer;
	declare ln_Leg_Count	integer;
	declare ln_Count		integer;
	declare ln_max_level	integer;
	declare ln_dst_level	integer;
	declare ln_dst			integer;
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
 	
	select max(level_id) into ln_max_level from comm_sponsor_hier;
	   	
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
	for ln_dst_level in reverse 0..ln_max_level do
		for ln_x in reverse 1..cardinality(:req1_rank_id) do
				
			-- Find Distributors matching requirments [All Markets]
			lr_dst_level_USA = 
				select 
					 d.period_id, d.dist_id, d.enroller_id, :ln_x as rank_id, 1 as rank_qual
				from comm_sponsor_hier h, comm_dist d
				where d.period_id = :pn_Period_id
				and h.dist_id = d.dist_id
				and d.rank_id = 0
			   	And d.dist_type_id = 1
				and d.country_code != 'KOR'
			   	and h.level_id = :ln_dst_level
				and d.pv >= :req1_pv[:ln_x]
				and d.pv_org >= :req1_ov[:ln_x]
				and (select count(dist_id)
					 from comm_dist
					 where sponsor_id = d.dist_id
					 and period_id = d.period_id
					 and leg_rank_id >= :req1_leg_rank[:ln_x]) >= :req1_leg_count[:ln_x];
				
			-- Find Distributors matching requirments [Korean Market]
			lr_dst_level_KOR = 
				select 
					 d.period_id, d.dist_id, d.enroller_id, :ln_x as rank_id, 1 as rank_qual
				from comm_sponsor_hier h, comm_dist d
				where d.period_id = :pn_Period_id
				and h.dist_id = d.dist_id
				and d.rank_id = 0
			   	And d.dist_type_id = 1
				and d.country_code = 'KOR'
			   	and h.level_id = :ln_dst_level
				and (d.pv >= :req2_pv[:ln_x] or
				     d.pv_egv >= :req2_egv[:ln_x])
				and d.pv_org >= :req2_ov[:ln_x]
				and (select count(dist_id)
					 from comm_dist
					 where sponsor_id = d.dist_id
					 and period_id = d.period_id
					 and leg_rank_id >= :req2_leg_rank[:ln_x]) >= :req2_leg_count[:ln_x];
					 
			lr_dst_level = CE_UNION_ALL(:lr_dst_level_USA,:lr_dst_level_KOR);
				
			--select count(dist_id) into ln_Count from :lr_dst_level;
				
			--if :ln_Count > 0 then
				replace comm_dist (PERIOD_ID,DIST_ID, RANK_ID, RANK_QUAL)
				select period_id,dist_id, rank_id, rank_qual
				from :lr_dst_level;
				
				commit;
				
				--if :ln_x >= 4 then
					replace comm_dist (PERIOD_ID,DIST_ID, LEG_RANK_DIST_ID, LEG_RANK_ID)
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
					
					commit;
				--end if;
			--end if;
		end for;
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

end;
