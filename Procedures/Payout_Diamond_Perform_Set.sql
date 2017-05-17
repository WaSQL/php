drop Procedure Commissions.Payout_Diamond_Perform_Set;
create Procedure Commissions.Payout_Diamond_Perform_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	declare ln_Value		decimal(18,8);
	declare ln_Volume		decimal(18,8);
	declare ln_Fund			decimal(18,8);
	declare ln_Extra		decimal(18,8);
    
	Update period_batch
	Set beg_date_payout_6 = current_timestamp
      ,end_date_payout_6 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
		
	-- Get Pool Requirements
	lc_Require =
		select *
		from req_pool
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id
		and version_id = 1
		and pool_id = 2;
		
	-- Get Exchange Rate for Period
	lc_Exchange =
		select *
		from fn_exchange(:pn_Period_id);
   	
   	-- Get Customers
   	lc_Customer =
   		select 
   			  a.*
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
				,r.pool_id													as Pool_id
				,r.value_2													as Shares
	   		from customer_history c
				  left outer join customer_history_flag f
					  on c.customer_id = f.customer_id
					  and c.period_id = f.period_id
					  and c.batch_id = f.batch_id
					  and f.flag_type_id in (2)
				, :lc_Require r
	   		where c.rank_id = r.value_1
	   		and c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id
			and r.type_id = 3) a
			left outer join :lc_Exchange x
			on a.currency = x.currency;
		
	-- Get Volumes
	select round(c.vol_13,2), round(c.vol_13 * (r1.value_2/100) + (ifnull(r2.value_2,0)), 2)
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
	select sum((select count(*)
	from customer_history e
		left outer join :lc_Require r
		on r.type_id = 4
	where d.customer_id = e.enroller_id
	and d.period_id = e.period_id
	and d.batch_id = e.batch_id
	and e.rank_id >= r.value_1
	and r.value_1 > ifnull((select max(rank_id)
       		from customer_rank_history h1, period p1
       		where p1.period_id = e.period_id
       		and h1.effective_date < p1.end_date
       		and customer_id = e.customer_id),1)))
    into ln_Extra
	from :lc_Customer d;
   	
   	-- Set Pool Header
   	replace payout_pool_head (period_id, batch_id, pool_id, count, volume, percent, fund, shares, shares_extra, share_value)
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
			on r2.type_id = 1
		group by h.period_id, h.batch_id, h.pool_id, r2.value_2) a;
	
	commit;
	
	-- Get Share Value
	select share_value
	into ln_Value
	from payout_pool_head
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 2;
	
	-- Set Payout Detail
	replace payout_06 (period_id, batch_id, customer_id, rank_id, shares, shares_extra, from_currency, to_currency, exchange_rate, bonus, bonus_exchanged)
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
			, (select count(*)
			   from customer_history e
			   where d.customer_id = e.enroller_id
			   and d.period_id = e.period_id
			   and d.batch_id = e.batch_id
			   and e.rank_id = r.value_1
			   and r.value_1 > ifnull((select max(rank_id)
		           		from customer_rank_history h, period p
		           		where p.period_id = e.period_id
		           		and h.effective_date < p.end_date
		           		and customer_id = e.customer_id),1)) 			as shares_extra
		    , 'USD'														as from_currency
		    , d.to_currency												as to_currency
		    , d.exchange_rate											as exchange_rate
		    , d.round_factor											as round_factor
		from :lc_Customer d
			left outer join :lc_Require r
			on r.type_id = 4
		group by d.period_id, d.batch_id, d.customer_id, d.rank_id, d.shares, d.to_currency, d.exchange_rate, d.round_factor, r.value_1) a;
	
	commit;
   	
   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, payout_6)
   	select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from payout_06
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by customer_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_payout_6 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
