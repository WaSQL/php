drop Procedure Commissions.Payout_Unilevel_Set;
create Procedure Commissions.Payout_Unilevel_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
    declare ln_max      integer;
    declare ln_x        integer;
    
	call Payout_Unilevel_Clear(:pn_Period_id, :pn_Period_Batch_id);
    
	Update period_batch
	Set beg_date_payout_1 = current_timestamp
      ,end_date_payout_1 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Period Customers
    lc_Customers_Level = 
    	with lc_Customers as (
    		select 
				 c.customer_id
				,c.type_id
				,c.comm_status_date
				,c.entry_date
				,c.sponsor_id
				,c.period_id
				,c.batch_id
				,c.country
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,co.currency_code) as currency
				,c.rank_id
				,c.rank_qual
			from customer_history c
				  left outer join customer_history_flag f
					  on c.customer_id = f.customer_id
					  and c.period_id = f.period_id
					  and c.batch_id = f.batch_id
					  and f.flag_type_id = 2
				  left outer join country co
				  	  on c.country = co.country_code
			where c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id)
		select
			 h.customer_id				as customer_id
			,h.period_id				as period_id 
			,h.batch_id					as batch_id
			,h.type_id					as type_id
			,h.country					as country
			,h.currency					as currency
			,h.comm_status_date			as comm_status_date
			,h.entry_date				as entry_date
			,h.rank_id					as rank_id
			,h.rank_qual				as rank_qual
			,s.customer_id				as sponsor_id
			,ifnull(s.rank_id,1)		as sponsor_rank_id
			,ifnull(s.rank_qual,0)		as sponsor_rank_qual
			,s.currency					as sponsor_currency_code
			,h.hierarchy_level			as level_id
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*
			             from lc_Customers a
			             order by customer_id)
	    		Start where sponsor_id = 3) h
	    	, lc_Customers s 
	    where h.sponsor_id = s.customer_id;
		
	-- Get Period Transactions
	lc_Transactions = 
		select
			  t.transaction_log_id
			 ,t.transaction_log_ref_id 
			 ,t.transaction_date
			 ,c.period_id
			 ,c.batch_id
		     ,t.customer_id
		     ,t.transaction_type_id
		     ,ifnull(t.value_2,0) 									As pv
		     ,ifnull(t.value_4,0) 									As cv
		     ,ifnull(t.currency_code,co.currency_code)				as currency
		From transaction_log t
			,:lc_Customers_Level c
			  left outer join country co
			  	on c.country = co.country_code
		Where t.customer_id = c.customer_id
	   	and ifnull(t.transaction_type_id,4) <> 0
	   	and t.period_id = :pn_Period_id;
        
    -- Get Max Level
    select max(level_id)
    into ln_max
    from :lc_Customers_Level;
		
	-- Get Exchange Rates
	lc_Exchange = 
		select *
		from fn_Exchange();
		
	-- Get Requirements for Unilevel
	lc_Req_Unilevel = 
		select *
		from req_unilevel_history
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
		
	-- Insert non retail transactions
	insert into payout_unilevel
	select 
		 payout_unilevel_id.nextval		as payout_unilevel_id
		,t.period_id					as period_id
		,t.batch_id						as batch_id
		,c.sponsor_id					as customer_id
		,t.transaction_log_id			as transaction_id
		,t.customer_id					as transaction_customer_id
		,t.transaction_type_id			as transaction_type_id
		,s.rank_qual					as qual_flag
		,t.pv							as pv
		,t.cv							as cv
		,t.currency						as from_currency
	    ,case 
	     	when upper(c.country) = 'KOR' 
	     	and upper(t.currency) <> 'KRW' then
	     		'USD'
	     	else
	     		s.currency
	      end							as to_currency
		,1								as exchange_rate
		,2								as percentage
		,1								as lvl
		,1								as lvl_paid
		,t.cv*req.value_1				as bonus
		,0								as bonus_exchanged
	from :lc_Req_Unilevel req
	   , :lc_Transactions t
		  left outer join :lc_Transactions r
		  	on t.transaction_log_ref_id = r.transaction_log_id
	   , :lc_Customers_Level c
	   , :lc_Customers_Level s
	Where t.customer_id = c.customer_id
	and c.sponsor_id = s.customer_id
	and c.type_id not in (2,3)
   	and ifnull(t.transaction_type_id,4) <> 0
   	and days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) > map(c.type_id,1,60,5,60,-1)
	and t.period_id = req.period_id
	and t.batch_id = req.batch_id
	and req.level_id = 1;
		
	-- Insert retail transactions
	insert into payout_unilevel
	select 
		 payout_unilevel_id.nextval		as payout_unilevel_id
		,t.period_id					as period_id
		,t.batch_id						as batch_id
		,s1.sponsor_id					as customer_id
		,t.transaction_log_id			as transaction_id
		,s1.customer_id					as transaction_customer_id
		,t.transaction_type_id			as transaction_type_id
		,s2.rank_qual					as qual_flag
		,t.pv							as pv
		,t.cv							as cv
		,t.currency						as from_currency
	    ,case 
	     	when upper(c.country) = 'KOR'
	     	and upper(t.currency) <> 'KRW' then
	     		'USD'
	     	else
	     		s2.currency
	      end							as to_currency
		,1								as exchange_rate
		,2								as percentage
		,1								as lvl
		,1								as lvl_paid
		,t.cv*req.value_1				as bonus
		,0								as bonus_exchanged
	from :lc_Req_Unilevel req
	   , :lc_Transactions t
	   , :lc_Customers_Level c
	   , :lc_Customers_Level s1
	   , :lc_Customers_Level s2 
	Where t.customer_id = c.customer_id
	and c.sponsor_id = s1.customer_id
	and s1.sponsor_id = s2.customer_id
	and c.type_id in (2,3)
   	and ifnull(t.transaction_type_id,4) <> 0
	and t.period_id = req.period_id
	and t.batch_id = req.batch_id
	and req.level_id = 1;
	
	commit;
    
    -- Loop through all tree levels from bottom to top
    for ln_x in reverse 0..:ln_max do
    	insert into payout_unilevel
    	select 
    		 payout_unilevel_id.nextval											as payout_unilevel_id
			,p.period_id														as period_id
			,p.batch_id															as batch_id
			,d.sponsor_id														as customer_id
			,p.transaction_id													as transaction_id
			,p.transaction_customer_id											as transaction_customer_id
			,p.transaction_type_id												as transaction_type_id
			,case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then 1
			 	else 0 end														as qual_flag
			,p.pv																as pv
			,p.cv																as cv
			,p.from_currency													as from_currency
		    ,case 
		     	when upper(t.country) = 'KOR'
		     	and upper(p.from_currency) <> 'KRW' then 'USD'
		     	else d.sponsor_currency_code
		      end																as to_currency
			--,d.sponsor_currency_code											as to_currency
			,1																	as exchange_rate
			,case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then u.value_1*100
			 	else 0 end														as percentage
			,p.lvl+1															as lvl
			,p.lvl_paid+map(p.qual_flag,1,1,0)									as lvl_paid
			,cv*case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then u.value_1
			 	else 0 end														as bonus
			,0																	as bonus_exchanged
		from :lc_Customers_Level d, :lc_Req_Unilevel u, payout_unilevel p, :lc_Customers_Level t
		where p.transaction_customer_id = t.customer_id
		and d.customer_id = p.customer_id
		and p.lvl_paid+map(p.qual_flag,1,1,0) = u.level_id
		and d.level_id = :ln_x
		and p.period_id = :pn_period_id
		and p.batch_id = :pn_Period_Batch_id
		and (p.lvl_paid < 7 or p.qual_flag = 0);
		
        commit;
    end for;
    
    -- Delete all non qualified entries
    delete from payout_unilevel
    where  period_id = :pn_period_id
	and batch_id = :pn_Period_Batch_id
	and (ifnull(qual_flag,0) = 0 or bonus = 0);
    
    commit;
    
    -- Get Payout Records
    lc_Payout_Unilevel = 
    	select *
    	from payout_unilevel
    	where period_id = :pn_period_id
		and batch_id = :pn_Period_Batch_id;
    
    -- Set Currency Rates when From/To not equal
    replace payout_unilevel (payout_unilevel_id, exchange_rate, bonus, bonus_exchanged)
    select 
    	 p.payout_unilevel_id
   		,round(et.rate / ef.rate,5)
   		,round(p.bonus,ef.round_factor)
   		,round(p.bonus * (et.rate / ef.rate),et.round_factor)
   	from :lc_Payout_Unilevel p
   		left outer join :lc_Exchange ef
   			on p.from_currency = ef.currency
   		left outer join :lc_Exchange et
   			on p.to_currency = et.currency
   	where from_currency <> to_currency;
	
	commit;
    
    -- Set Currency Rates when From/To equal
    replace payout_unilevel (payout_unilevel_id, exchange_rate, bonus, bonus_exchanged)
    select 
    	 p.payout_unilevel_id
    	,1
   		,round(p.bonus,ef.round_factor)
   		,round(p.bonus,et.round_factor)
   	from :lc_Payout_Unilevel p
		left outer join :lc_Exchange ef
			on p.from_currency = ef.currency
		left outer join :lc_Exchange et
			on p.to_currency = et.currency
   	where from_currency = to_currency;
	
	commit;
    
    -- Aggregate Bonus values for each customer
    replace customer_history (customer_id, period_id, batch_id, payout_1)
    select 
    	 h.customer_id
    	,h.period_id
   		,h.batch_id
   		,sum(ifnull(p.bonus_exchanged,0))
   	from customer_history h
   		left outer join country c
   			on h.country = c.country_code
   		left outer join payout_unilevel p
   			on h.customer_id = p.customer_id
   			and h.period_id = p.period_id
   			and h.period_id = p.period_id
   			and p.qual_flag = 1
   	where h.period_id = :pn_Period_id
	and h.batch_id = :pn_Period_Batch_id
	and c.currency_code = p.to_currency
	group by h.customer_id,h.period_id,h.batch_id;
	
	/*
    select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from payout_unilevel
   	where period_id = :pn_period_id
	and batch_id = :pn_Period_Batch_id
   	and qual_flag = 1
	group by customer_id,period_id,batch_id;
	*/
	
   	Update period_batch
   	Set end_date_payout_1 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
