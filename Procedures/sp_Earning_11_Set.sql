drop Procedure Commissions.sp_Earning_11_Set;
create Procedure Commissions.sp_Earning_11_Set(
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
	declare ln_Percent		decimal(18,8);
	declare ld_Beg_date		date;
	declare ld_End_date		date;
    
	Update period_batch
	Set beg_date_Earning_11 = current_timestamp
      ,end_date_Earning_11 = Null
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
		from pool_req
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id
		and version_id = 1
		and pool_id = 7;

	-- Get Exchange Rate for Period
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);

   	-- Get Customers
   	lc_Customer =
   		select *
		from (
				select 
		   			 c.period_id							as Period_id
		   			,c.batch_id								as Batch_id
		   			,c.customer_id							as Customer_id
		   			,c.rank_id								as Rank_id
		   			,c.country								as Country
					,c.currency								as To_Currency
					,c.exchange_rate						as exchange_rate
					,c.round_factor							as round_factor
					,r3.pool_id								as Pool_id
					,1 										as Shares
					,(select count(customer_id)
						from customer_history
						where period_id = c.period_id
						and batch_id = c.batch_id
						and enroller_id = c.customer_id
						and entry_date >= :ld_Beg_date
						and entry_date <= :ld_End_date
						and type_id in (select type_id 
										from customer_type 
										where has_power3 = 1)
						and vol_1 >= r6.value_2)			as New_Enroll
		   		from gl_customer(:pn_Period_id, :pn_Period_Batch_id) c
					  left outer join :lc_Require r3
					  	on r3.value_1 = c.rank_id
					  	and r3.type_id = 3
			   			and r3.version_id = 1
					  left outer join :lc_Require r6
					  	on r6.type_id = 6
			   			and r6.version_id = 1)
		where pool_id = 7
		and new_enroll > 0
		and country not in ('TWN','KOR');
		
	-- Get Volumes
	select round(c.vol_13,2), round(c.vol_13 * (r1.value_2/100) + (ifnull(r2.value_2,0)), 2), r1.value_2
	into ln_Volume, ln_Fund, ln_Percent
	from customer_history c
		left outer join :lc_Require r1
		on r1.type_id = 1
		left outer join :lc_Require r2
		on r2.type_id = 2
	where c.period_id = :pn_Period_id
	and c.batch_id = :pn_Period_Batch_id
	and c.customer_id = 1;
	
	-- Check for Record in table
	select count(*)
	into ln_Count
	from pool_head
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 7;
	
	if :ln_Count = 0 then
		insert into pool_head (period_id, batch_id, pool_id, count) values (:pn_Period_id, :pn_Period_Batch_id, 7, 0);
		commit;
	end if;

	-- Set Pool Header
   	replace pool_head (period_id, batch_id, pool_id, count, volume, percent, fund, shares, shares_extra, share_value)
   	select a.*, round(a.fund / (a.shares),2) as share_value
   	from (
	   	select 
	   		 h.period_id
	   		,h.batch_id
	   		,h.pool_id					as pool_id
	   		,count(*)					as count
			,:ln_Volume					as volume
			,:ln_Percent				as percent
			,:ln_Fund					as fund
			,sum(h.shares)				as shares
			,0  						as shares_extra
		from :lc_Customer h
		group by h.period_id, h.batch_id, h.pool_id) a;
	
	commit;

	-- Get Share Value
	select share_value
	into ln_Value
	from pool_head
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 7;
	
	-- Set Earning Detail
	replace Earning_11 (period_id, batch_id, customer_id, rank_id, shares, shares_extra, from_currency, to_currency, exchange_rate, bonus, bonus_exchanged)
	select 
		  a.period_id
		, a.batch_id
		, a.customer_id
		, a.rank_id
		, a.shares
		, a.shares_extra
		, a.from_currency
		, a.to_currency
		, a.exchange_rate
		, (a.shares+a.shares_extra) * :ln_Value												as bonus
		, round(((a.shares+a.shares_extra) * :ln_Value) * a.exchange_rate,a.round_factor)	as bonus_exchanged
	from (
		select 
			  d.period_id
			, d.batch_id
			, d.customer_id
			, d.rank_id
			, d.shares
			, 0						as shares_extra
		    , 'USD'					as from_currency
		    , d.to_currency			as to_currency
		    , d.exchange_rate		as exchange_rate
		    , d.round_factor		as round_factor
		from :lc_Customer d) a;
	
	commit;
   	
   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, Earning_11)
   	select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from Earning_11
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by customer_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_Earning_11 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
