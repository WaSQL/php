drop procedure set_payout_2_detail;
create procedure set_payout_2_detail(
            pn_Period_id      Integer)
LANGUAGE SQLSCRIPT AS

begin
	declare ln_payout		double array = array(.02,.03,.05,.05,.06,.06,.07);
	declare ln_max_level	integer;
	declare ln_level		integer;
	
	-- Update Status
   	Update comm_period
   	Set date_srt_payout_2_detail = current_timestamp
       ,date_end_payout_2_detail = Null
   	Where period_id = :pn_Period_id;
                  
   	Commit;
   	
   	select max(level_id)
   	into ln_max_level
   	from comm_sponsor_hier;
   	
   	lc_dst_all = 
   		select 
   			  dist_id
   			, pv
   			, cv
   			, rank_qual
   			, currency_code
   			, (select max(level_id) from comm_sponsor_hier where dist_id = d.dist_id) as level_id
		from comm_dist d
		where period_id = :pn_Period_id
		and cv <> 0;
		
	for ln_level in 1..ln_max_level do
		lc_dst_level =
			select *
			from :lc_dst_all
			where level_id = :ln_level;
		
		Insert Into comm_payout_2_detail
			(period_id
			,f_dist_id
			,t_dist_id
			,qualified
			,pv
			,cv
			,cv_exchanged
			,f_currency_code
			,t_currency_code
			,ex_rate
			,percent
			,lvl
			,plvl
			,bonus
			,bonus_exchanged)
		select 
			 period_id
			,f_dist_id
			,t_dist_id
			,qualified
			,pv
			,cv
			,cv_exchanged
			,f_currency_code
			,t_currency_code
			,1								as ex_rate
			,:ln_payout[plvl]				as percent
			,lvl
			,plvl
			,round(cv * :ln_payout[plvl],2)	as bonus
			,round(cv * :ln_payout[plvl],2)	as bonus_exchanged
		from (
			select 
				  d.period_id				as period_id
				, f.dist_id 				as f_dist_id
				, d.dist_id					as t_dist_id
				, f.rank_qual				as qualified
				, f.pv						as pv
				, f.cv						as cv
				, f.cv						as cv_exchanged
				, f.currency_code			as f_currency_code
				, d.currency_code			as t_currency_code
				, h.level_id				as lvl	
				, row_number() over (partition by f.dist_id order by h.level_id) as plvl
			from comm_dist d, comm_sponsor_hier h, :lc_dst_level f
			where f.dist_id = h.dist_id
			and h.root_id = d.dist_id
			and d.rank_qual = 1
			and d.dist_type_id = 1
			and h.level_id > 0
			and d.rank_id >=  h.level_id
			and d.period_id = :pn_Period_id)
		where plvl < 8;
		
		commit;
	
	end for;
   
	-- Update Status
   	Update comm_period
   	Set date_end_payout_2_detail = current_timestamp
   	Where period_id = :pn_Period_id;
                  
   	Commit;

end;