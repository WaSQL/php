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
				
		-- Find Distributors matching requirments
		lr_dst_level = 
			with lr_dst as (
				select 
					 d.dist_id, d.period_id, d.enroller_id, d.country_code, d.pv, d.pv_org, d.pv_egv
				from comm_sponsor_hier h, comm_dist d
				where d.period_id = :pn_Period_id
				and h.dist_id = d.dist_id
				and d.rank_id = 0
			   	And d.dist_type_id = 1
			   	and h.level_id = :ln_dst_level)
			select period_id, dist_id, enroller_id, max(rank_id) as rank_id
			from (
				select
					 d.period_id, d.dist_id, d.enroller_id, 1 as rank_id
				from lr_dst d
			   	where ((d.country_code != 'KOR'
		   		   		and d.pv >= :req1_pv[1]
		   		   		and d.pv_org >= :req1_ov[1]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[1]) >= :req1_leg_count[1])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[1] 
				        or  d.pv_egv >= :req2_egv[1])
			   	       	and d.pv_org >= :req2_ov[1]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[1]) >= :req2_leg_count[1]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 2 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[2]
		   		   		and d.pv_org >= :req1_ov[2]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[2]) >= :req1_leg_count[2])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[2] 
				        or  d.pv_egv >= :req2_egv[2])
			   	       	and d.pv_org >= :req2_ov[2]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[2]) >= :req2_leg_count[2]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 3 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[3]
		   		   		and d.pv_org >= :req1_ov[3]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[3]) >= :req1_leg_count[3])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[3] 
				        or  d.pv_egv >= :req2_egv[3])
			   	       	and d.pv_org >= :req2_ov[3]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[3]) >= :req2_leg_count[3]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 4 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[4]
		   		   		and d.pv_org >= :req1_ov[4]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id>= :req1_leg_rank[4]) >= :req1_leg_count[4])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[4] 
				        or  d.pv_egv >= :req2_egv[4])
			   	       	and d.pv_org >= :req2_ov[4]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[4]) >= :req2_leg_count[4]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 5 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[5]
		   		   		and d.pv_org >= :req1_ov[5]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[5]) >= :req1_leg_count[5])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[5] 
				        or  d.pv_egv >= :req2_egv[5])
			   	       	and d.pv_org >= :req2_ov[5]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[5]) >= :req2_leg_count[5]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 6 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[6]
		   		   		and d.pv_org >= :req1_ov[6]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[6]) >= :req1_leg_count[6])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[6] 
				        or  d.pv_egv >= :req2_egv[6])
			   	       	and d.pv_org >= :req2_ov[6]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[6]) >= :req2_leg_count[6]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 7 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[7]
		   		   		and d.pv_org >= :req1_ov[7]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[7]) >= :req1_leg_count[7])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[7] 
				        or  d.pv_egv >= :req2_egv[7])
			   	       	and d.pv_org >= :req2_ov[7]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[7]) >= :req2_leg_count[7]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 8 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[8]
		   		   		and d.pv_org >= :req1_ov[8]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[8]) >= :req1_leg_count[8])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[8] 
				        or  d.pv_egv >= :req2_egv[8])
			   	       	and d.pv_org >= :req2_ov[8]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[8]) >= :req2_leg_count[8]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 9 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[9]
		   		   		and d.pv_org >= :req1_ov[9]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[9]) >= :req1_leg_count[9])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[9] 
				        or  d.pv_egv >= :req2_egv[9])
			   	       	and d.pv_org >= :req2_ov[9]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[9]) >= :req2_leg_count[9]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 10 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[10]
		   		   		and d.pv_org >= :req1_ov[10]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[10]) >= :req1_leg_count[10])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[10] 
				        or  d.pv_egv >= :req2_egv[10])
			   	       	and d.pv_org >= :req2_ov[10]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[10]) >= :req2_leg_count[10]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 11 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[11]
		   		   		and d.pv_org >= :req1_ov[11]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[11]) >= :req1_leg_count[11])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[11] 
				        or  d.pv_egv >= :req2_egv[11])
			   	       	and d.pv_org >= :req2_ov[11]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[11]) >= :req2_leg_count[11]))
				union all
				select 
					 d.period_id, d.dist_id, d.enroller_id, 12 as rank_id
				from lr_dst d
				where ((d.country_code != 'KOR' 
		   		   		and d.pv >= :req1_pv[12]
		   		   		and d.pv_org >= :req1_ov[12]
		   		   		and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req1_leg_rank[12]) >= :req1_leg_count[12])
						or (d.country_code = 'KOR'
						and (d.pv >= :req2_pv[12] 
				        or  d.pv_egv >= :req2_egv[12])
			   	       	and d.pv_org >= :req2_ov[12]
			   	       	and (select count(dist_id)
					 		from comm_dist
					 		where sponsor_id = d.dist_id
					 		and period_id = d.period_id
					 		and leg_rank_id >= :req2_leg_rank[12]) >= :req2_leg_count[12]))
				)
			group by period_id, dist_id, enroller_id;
			
		replace comm_dist (PERIOD_ID,DIST_ID, RANK_ID, RANK_QUAL)
		select period_id,dist_id, rank_id, 1
		from :lr_dst_level;
		
		commit;
				
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
