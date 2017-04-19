drop procedure Commissions.Ranks_Trans_Set;
create procedure Commissions.Ranks_Trans_Set(
						pn_transaction_id	integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Customer_id		integer;
	declare ld_Transaction_date	timestamp;
	declare ld_Entry_date		date;
	declare ld_Curr_date		timestamp;
	declare ln_PV				double;
	declare ln_CV				double;
	declare ln_Rank_High_id		integer;
	declare ln_Type_id			integer;
	declare ls_Enrol_Country	varchar(5);
	
	select t.customer_id, t.transaction_date, t.value_2, t.value_4, c.rank_high_id, current_timestamp, c.type_id, c.entry_date, e.country
	into ln_Customer_id, ld_Transaction_date, ln_PV, ln_CV, ln_Rank_High_id, ld_Curr_date, ln_Type_id, ld_Entry_date, ls_Enrol_Country
	from transaction t, customer c, customer e
	where t.customer_id = c.customer_id
	and c.enroller_id = e.customer_id
	and t.transaction_id = :pn_transaction_id
	and ifnull(t.transaction_type_id,4) <> 0;
	
	-- Only run if there is some volume
	if ifnull(:ln_PV,0) <> 0 or ifnull(:ln_CV,0) <> 0 then
		-- Set Retail
		if :ln_Type_id = 2 or :ln_Type_id = 3 then
			replace customer (customer_id, vol_4, vol_9)
			Select 
				 s.customer_id
			    ,:ln_PV As pv_retail
			    ,:ln_CV As cv_retail
			From customer a, customer s
			Where a.sponsor_id = s.customer_id
			and a.customer_id = :ln_Customer_id;
			
			select s.customer_id, s.type_id, s.rank_high_id, s.entry_date, e.country
			into ln_Customer_id, ln_Type_id, ln_Rank_High_id, ld_Entry_date, ls_Enrol_Country
			from customer a, customer s, customer e
			where a.sponsor_id = s.customer_id
			and s.enroller_id = e.customer_id
			and a.customer_id = :ln_Customer_id;
		end if;
		
		-- Set FS
		if days_between(:ld_Entry_date,:ld_Transaction_date) <= 60 then
			replace customer (customer_id, vol_5, vol_10)
			Select 
				  customer_id
			     ,vol_5 + :ln_PV As pv
			     ,vol_10 + :ln_CV As cv
			From customer
			Where customer_id = :ln_Customer_id;
		end if;
		
		-- Set EGV
		if :ls_Enrol_Country = 'KOR' and :ln_Rank_High_id < 5 then
			replace customer (customer_id, vol_11)
			select 
				 e.customer_id
				,e.vol_11 + :ln_PV as vol_11
			from customer a, customer e
			where a.enroller_id = e.customer_id
			and a.customer_id = :ln_Customer_id;
		end if;
	
		-- Set PV / CV
		replace customer (customer_id, vol_1, vol_6)
		Select 
		      customer_id
		     ,vol_1 + :ln_PV As pv
		     ,vol_6 + :ln_CV As cv
		From customer
		Where customer_id = :ln_Customer_id;
		
		-- Set OGV
		loop
			if :ln_Customer_id = 3 then
				break;
			end if;
			
			-- Update Org Volume by rolling up PV
	    	replace customer (customer_id, vol_13)
	        select
	            customer_id       	as customer_id
	           ,vol_13 + :ln_PV		as vol_13
	        from customer c
			Where type_id = 1
			and customer_id = :ln_Customer_id;
			
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
					left outer join req_qual_leg_version v
						on c.country = v.country
					left outer join customer_flag w
						on c.customer_id = w.customer_id
						and flag_type_id in (3,4,5)
				, req_qual_leg q
			where q.version_id = ifnull(v.version_id,1)
		   	And c.type_id = 1
		   	and c.status_id in (1, 4)
		   	and c.customer_id = :ln_Customer_id
			and (c.vol_1 >= q.vol_1 or (c.vol_11 >= q.vol_3 and v.version_id = 2))
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
			replace customer_qual_leg (customer_id, leg_customer_id, entry_date, sponsor_id, leg_enroller_id, leg_rank_id) 
			select 
				 customer_id
				,customer_id as leg_customer_id
				,:ld_Curr_date
				,sponsor_id
				,enroller_id as leg_enroller_id
				,rank_id
			from customer
			where customer_id = :ln_Customer_id
			union all
			select
				 c.customer_id
				,q.leg_customer_id
				,:ld_Curr_date
				,c.sponsor_id
				,q.leg_enroller_id
				,q.leg_rank_id
			from customer c, customer_qual_leg  q
			where c.customer_id = q.sponsor_id
			and q.leg_enroller_id <> q.sponsor_id
			and c.customer_id = :ln_Customer_id;
	
			-- Clean Up Garbage
			delete
			from customer_qual_leg
			where sponsor_id = :ln_Customer_id
			and (leg_enroller_id <> sponsor_id or customer_id in (select customer_id from customer_flag where flag_type_id > 0));

			select sponsor_id
			into ln_Customer_id
			from customer
			where customer_id = :ln_Customer_id;
			
		end loop;
		
		commit;
		
	end if;
end
