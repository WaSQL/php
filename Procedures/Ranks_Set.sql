drop procedure Commissions.Ranks_Set;
create procedure Commissions.Ranks_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	
	declare ln_max_level	integer;
	declare ln_dst_level	integer;
	declare ld_Curr_Date	timestamp;
	
	select current_timestamp
	into ld_Curr_Date
	from dummy;
    
	Update period_batch
	Set beg_date_rank = current_timestamp
      ,end_date_rank = Null
   	Where period_id = 0
   	and batch_id = 0;
                  
   	Commit;
   		
	lc_Customer =
		select
			 c.customer_id							as customer_id
			,c.sponsor_id							as sponsor_id
			,c.enroller_id							as enroller_id
			,c.rank_id								as rank_id
			,c.type_id								as type_id
			,c.status_id							as status_id
			,c.vol_1								as vol_1
			,c.vol_4								as vol_4
			,c.vol_11								as vol_11
			,c.vol_13								as vol_13
			,ifnull(v.version_id,1)					as version_id
			,ifnull(w.flag_type_id,0)				as flag_type_id
			,ifnull(w.flag_value,0)					as flag_value
		from customer c
			left outer join version v
				on c.country = v.country
				and v.version_id in (1,2)
			left outer join customer_flag w
				on c.customer_id = w.customer_id
				and w.flag_type_id in (3,4,5);
		
	lc_Cust_Flag =
		select
			 customer_id
			,flag_type_id
			,flag_value
		from customer_flag
		where flag_type_id = 1;
		
	lc_Require_Leg =
		select *
		from req_qual_leg_template;
		
	lc_Cust_Level = 
		select
			 node_id 						as customer_id
			,parent_id 						as sponsor_id
			,enroller_id					as enroller_id
			,rank_id						as rank_id
			,type_id						as type_id
			,status_id						as status_id
			,vol_1							as vol_1
			,vol_4							as vol_4
			,vol_11							as vol_11
			,vol_13							as vol_13
			,version_id						as version_id
			,flag_type_id					as flag_type_id
			,flag_value						as flag_value
			,hierarchy_level				as level_id
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, t.*
			             from :lc_Customer t
			             --order by customer_id
			           )
	    		Start where customer_id = 3);
	    		        
    select max(level_id)
    into ln_max_level
    from :lc_Cust_Level;
	
	-- Process All Distributors From the Bottom Up
	for ln_dst_level in reverse 0..:ln_max_level do
		lc_Qual_Leg =
			select customer_id, sponsor_id, leg_customer_id, leg_enroller_id, leg_rank_id
			from customer_qual_leg;
 		
		lr_dst_level = 
			select -- Find Distributors matching requirments
				   h.customer_id
				 , h.sponsor_id
				 , h.enroller_id
				 , case h.flag_type_id
				 		when 3 then	greatest(h.flag_value,max(q.rank_id))
				 		when 4 then least(h.flag_value,max(q.rank_id))
				 		when 5 then h.flag_value
				 		else max(q.rank_id) end as new_rank_id
				 , 1 as rank_qual
			from :lc_Cust_Level h, :lc_Require_Leg q
			where q.version_id = h.version_id
		   	And h.type_id = 1
		   	and h.status_id in (1, 4)
		   	and h.level_id = :ln_dst_level
			and ((h.vol_1 + h.vol_4) >= q.vol_1 or (h.vol_11 >= q.vol_3 and h.version_id = 2))
			and h.vol_13 >= q.vol_2
			and (select count(*)
				 from (
					 select customer_id, sponsor_id, max(leg_rank_id) as leg_rank_id 
					 from :lc_Qual_Leg
					 where sponsor_id = h.customer_id
					 and sponsor_id = leg_enroller_id
					 --and leg_rank_id >= 1
					 and leg_rank_id >= q.leg_rank_id
					 group by customer_id, sponsor_id)) >= q.leg_rank_count
			group by h.customer_id, h.sponsor_id, h.enroller_id, h.flag_type_id, h.flag_value;
			
		-- Update Level with Calculated New Ranks	
		replace customer (customer_id, rank_id, rank_qual)
		select customer_id, new_rank_id, rank_qual
		from :lr_dst_level;
		
		commit;

		-- Write Ranks To Qual Leg Table
		replace customer_qual_leg (customer_id, leg_customer_id, entry_date, sponsor_id, leg_enroller_id, leg_rank_id) 
		select 
			 customer_id
			,customer_id as leg_customer_id
			,:ld_Curr_Date
			,sponsor_id
			,enroller_id as leg_enroller_id
			,new_rank_id
		from :lr_dst_level
		union all
		select
			 l.customer_id
			,h.leg_customer_id
			,:ld_Curr_Date
			,l.sponsor_id
			,h.leg_enroller_id
			,h.leg_rank_id
		from :lc_Cust_Level l, :lc_Qual_Leg  h
		where l.customer_id = h.sponsor_id
		and l.level_id = :ln_dst_level
		and h.leg_enroller_id <> h.sponsor_id;
		
		commit;

		-- Clean Up Garbage
		delete
		from customer_qual_leg
		where sponsor_id in (select customer_id from :lc_Cust_Level where level_id = :ln_dst_level)
		and (leg_enroller_id <> sponsor_id or customer_id in (select customer_id from :lc_Cust_Flag));

		commit;
	end for;
	
	-- Set All Rank Zeros To 1
	update customer
	set rank_id = 1
	, rank_qual = 0
	where rank_id = 0;
   
   	Update period_batch
   	Set end_date_rank = current_timestamp
   	Where period_id = 0
   	and batch_id = 0;
                  
   	Commit;

end
