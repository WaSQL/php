drop procedure Commissions.sp_Customer_Rollup_Rank;
create procedure Commissions.sp_Customer_Rollup_Rank(
						pn_Customer_id	integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ld_Curr_date		timestamp = current_timestamp;
		
	-- Update Level with Calculated New Ranks	
	replace customer (customer_id, rank_id, rank_qual)
	select -- Find Distributors matching requirments
		   c.customer_id
		 , case w.flag_type_id
		 		when 3 then	greatest(to_number(w.flag_value),max(q.rank_id))
		 		when 4 then least(to_number(w.flag_value),max(q.rank_id))
		 		when 5 then to_number(w.flag_value)
		 		else max(q.rank_id) end as rank_id
		 , 1 as rank_qual
	from customer c
			left outer join version v
				on c.country = v.country
			left outer join customer_flag w
				on c.customer_id = w.customer_id
				and w.flag_type_id in (3,4,5)
		, rank_req q
	where q.version_id = ifnull(v.version_id,1)
   	And c.type_id = 1
   	and c.status_id in (1, 4)
   	and c.customer_id = :pn_Customer_id
	and ((c.vol_1 + c.vol_4) >= q.vol_1 or (c.vol_11 >= q.vol_3 and v.version_id = 2))
	and c.vol_13 >= q.vol_2
	and (select count(*)
		 from (
			 select customer_id, sponsor_id, max(leg_rank_id) as leg_rank_id 
			 from customer_qual_leg
			 where sponsor_id = c.customer_id
			 and sponsor_id = leg_enroller_id
			 and leg_rank_id >= q.leg_rank_id
			 group by customer_id, sponsor_id)) >= q.leg_rank_count
	group by c.customer_id, c.sponsor_id, c.enroller_id, w.flag_type_id, w.flag_value;
		
	-- Write Ranks To Qual Leg Table
	replace customer_qual_leg (customer_id, leg_customer_id, sponsor_id, leg_enroller_id, leg_rank_id) 
	select 
		 customer_id
		,customer_id as leg_customer_id
		,sponsor_id
		,enroller_id as leg_enroller_id
		,rank_id
	from customer
	where customer_id = :pn_Customer_id
	union all
	select
		 c.customer_id
		,q.leg_customer_id
		,c.sponsor_id
		,q.leg_enroller_id
		,q.leg_rank_id
	from customer c, customer_qual_leg  q
	where c.customer_id = q.sponsor_id
	and q.leg_enroller_id <> q.sponsor_id
	and c.customer_id = :pn_Customer_id;

	-- Clean Up Garbage
	delete
	from customer_qual_leg
	where sponsor_id = :pn_Customer_id
	and (leg_enroller_id <> sponsor_id or customer_id in (select customer_id from customer_flag where flag_type_id > 0));
	
end
