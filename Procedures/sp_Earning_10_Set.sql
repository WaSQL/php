drop Procedure Commissions.sp_Earning_10_Set;
create Procedure Commissions.sp_Earning_10_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	declare ln_Count		integer;
	declare ln_Value		decimal(18,8);
	declare ln_Volume		decimal(18,8);
	declare ln_Fund			decimal(18,8);
	declare ln_Extra		decimal(18,8);
	declare ld_Beg_date		date;
	declare ld_End_date		date;
    
	Update period_batch
	Set beg_date_Earning_10 = current_timestamp
      ,end_date_Earning_10 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Period Dates
	select beg_date, end_date
	into ld_Beg_date, ld_End_date
	from period
	where period_id = :pn_Period_id;
		
	-- Get Pool Requirements
	lc_Require =
		select *
		from req_pool
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id
		and version_id = 3
		and pool_id = 6;
		
	-- Get Exchange Rate for Period
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
   	
   	-- Get Customers
   	lc_Customer =
   		select 
   			  a.*
   			, case when a.New_Enroll >= r2.value_1 then r2.value_2 else 0 end				as Shares
   			, case when a.New_Enroll >= r3.value_1 then r3.value_2 else 0 end				as Shares_extra
   			, x.currency												as to_currency
		    , x.rate													as exchange_rate
		    , x.round_factor											as round_factor
   		from (
	   		select 
	   			 c.period_id												as Period_id
	   			,c.batch_id													as Batch_id
	   			,c.customer_id												as Customer_id
	   			,c.rank_id													as Rank_id
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency)	as Currency
				,r1.pool_id													as Pool_id
				,v.version_id												as Version_id
				,(select count(customer_id)
					from customer_history
					where period_id = c.period_id
					and batch_id = c.batch_id
					and enroller_id = c.customer_id
					and entry_date >= ld_Beg_date
					and entry_date <= ld_End_date
					and upper(country) = v.country
					and vol_1 >= r1.value_2)								as New_Enroll
	   		from customer_history c
				  left outer join customer_history_flag f
					on c.customer_id = f.customer_id
					and c.period_id = f.period_id
					and c.batch_id = f.batch_id
					and f.flag_type_id in (2)
				  left outer join :lc_Require r1
				  	on c.rank_id = r1.value_1
				  	and r1.type_id = 3
				, version v
	   		where c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id
			and c.country = v.country
			and r1.version_id = v.version_id) a
			left outer join :lc_Require r2
				on r2.type_id = 5
				and r2.value_1 = 2
			left outer join :lc_Require r3
		  		on r3.type_id = 5
		  		and r3.value_1 = 3
			left outer join :lc_Exchange x
				on a.currency = x.currency
		where a.New_Enroll >= 2;
		
	-- Get Volumes
	select round(c.vol_15,2), round(c.vol_15 * (r1.value_2/100) + (ifnull(r2.value_2,0)), 2)
	into ln_Volume, ln_Fund
	from customer_history c
		left outer join :lc_Require r1
		on r1.type_id = 1
		left outer join :lc_Require r2
		on r2.type_id = 2
	where c.period_id = :pn_Period_id
	and c.batch_id = :pn_Period_Batch_id
	and c.customer_id = 1;
	
	
	-- Get Extra Shares
	select sum(d.shares_extra)
    into ln_Extra
	from :lc_Customer d;
	
	-- Check for Record in table
	select count(*)
	into ln_Count
	from pool_head
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 6;
	
	if :ln_Count = 0 then
		insert into pool_head (period_id, batch_id, pool_id, count) values (:pn_Period_id, :pn_Period_Batch_id, 6, 0);
		commit;
	end if;
   	
   	-- Set Pool Header
   	replace pool_head (period_id, batch_id, pool_id, count, volume, percent, fund, shares, shares_extra, share_value)
   	select a.*, round(a.fund / (a.shares+a.shares_extra),2) as share_value
   	from (
	   	select 
	   		 h.period_id
	   		,h.batch_id
	   		,h.pool_id					as pool_id
	   		,count(*)					as count
			,:ln_Volume					as volume
			,r2.value_2					as percent
			,:ln_Fund					as fund
			,sum(h.shares)				as shares
			,:ln_Extra  				as shares_extra
		from :lc_Customer h
			left outer join :lc_Require r2
			on r2.version_id = h.version_id
			and r2.type_id = 1
		group by h.period_id, h.batch_id, h.pool_id, r2.value_2) a;
	
	commit;
	
	-- Get Share Value
	select share_value
	into ln_Value
	from pool_head
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 6;
	
	-- Set Earning Detail
	replace Earning_10 (period_id, batch_id, customer_id, rank_id, shares, shares_extra, from_currency, to_currency, exchange_rate, bonus, bonus_exchanged)
	select 
		  a.period_id
		, a.batch_id
		, a.customer_id
		, a.rank_id
		, a.shares
		, a.shares_extra
		, a.from_currency
		, a.to_currency
		, (a.to_rate/a.from_rate)																		as exchange_rate
		, (a.shares+a.shares_extra) * :ln_Value															as bonus
		, round(((a.shares+a.shares_extra) * :ln_Value) * (a.to_rate/a.from_rate),a.to_round_factor)	as bonus_exchanged
	from (
		select 
			  d.period_id
			, d.batch_id
			, d.customer_id
			, d.rank_id
			, d.shares
			, d.shares_extra
		    , x.currency												as from_currency
		    , x.rate													as from_rate
		    , d.to_currency												as to_currency
		    , d.exchange_rate											as to_rate
		    , d.round_factor											as to_round_factor
		from :lc_Customer d
			left outer join :lc_Exchange x
			on x.currency = 'TWD'
		group by d.period_id, d.batch_id, d.customer_id, d.rank_id, d.shares, d.shares_extra, x.currency, x.rate, d.to_currency, d.exchange_rate, d.round_factor) a;
	
	commit;
   	
   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, Earning_10)
   	select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from Earning_10
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by customer_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_Earning_10 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
