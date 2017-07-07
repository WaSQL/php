drop Procedure Commissions.sp_Earning_01_Set;
create Procedure Commissions.sp_Earning_01_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
    declare ln_max      integer;
    declare ln_x        integer;
    
	Update period_batch
	Set beg_date_Earning_1 = current_timestamp
      ,end_date_Earning_1 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
    -- Set Elephant Leg Flag
	replace customer_history (customer_id, period_id, batch_id, Earning_1_cap)
	select
		 c.customer_id
		,c.period_id
		,c.batch_id
		,r.value_2
	from customer_history c, req_cap r, customer_type t
	where c.rank_id = r.rank_id
	and c.type_id = t.type_id
	and c.period_id = r.period_id
   	and c.batch_id = r.batch_id
	and c.period_id = :pn_period_id
   	and c.batch_id = :pn_Period_Batch_id
   	and c.vol_13 <> 0
   	and t.has_downline = 1
   	and (select count(*) from customer_history where period_id = c.period_id and batch_id = c.batch_id and sponsor_id = c.customer_id) > 0
   	and round((select max(vol_13) from customer_history where period_id = c.period_id and batch_id = c.batch_id and sponsor_id = c.customer_id) / c.vol_13,2)*100 >= r.value_1;
   	
   	commit;
   	
   	-- Get Period Customers
    lc_Customers_Level = 
    	with lc_Customers as (
    		select 
				 c.customer_id
				,c.type_id
				,ifnull(c.comm_status_date,
					case when c.type_id in (1,4,5) then c.entry_date								-- Type Wellness, Professional and Wholesale default to entry_date
					else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
				,c.entry_date
				,c.sponsor_id
				,c.period_id
				,c.batch_id
				,c.country
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency) 		as currency			-- Use currency of flag 2 - Comm Payto Currency
				,c.rank_id
				,c.rank_qual
				,c.Earning_1_cap
				,c.hier_level
			from customer_history c
				  left outer join customer_history_flag f
					  on c.customer_id = f.customer_id
					  and c.period_id = f.period_id
					  and c.batch_id = f.batch_id
					  and f.flag_type_id = 2
			where c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id
			and c.type_id not in (6))
		select
			 c.customer_id				as customer_id
			,c.period_id				as period_id 
			,c.batch_id					as batch_id
			,c.type_id					as type_id
			,c.country					as country
			,c.currency					as currency
			,c.comm_status_date			as comm_status_date
			,c.entry_date				as entry_date
			,c.rank_id					as rank_id
			,c.rank_qual				as rank_qual
			,c.Earning_1_cap				as Earning_1_cap
			,s.customer_id				as sponsor_id
			,ifnull(s.rank_id,1)		as sponsor_rank_id
			,ifnull(s.rank_qual,0)		as sponsor_rank_qual
			,s.currency					as sponsor_currency_code
			,s.country					as sponsor_country_code
			,c.hier_level					as hier_level
		from lc_Customers c
	    	, lc_Customers s 
	    where c.sponsor_id = s.customer_id;
		
	/*
	lc_Customers_Level = 
    	with lc_Customers as (
    		select 
				 c.customer_id
				,c.type_id
				,ifnull(c.comm_status_date,
					case when c.type_id in (1,4,5) then c.entry_date								-- Type Wellness, Professional and Wholesale default to entry_date
					else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
				,c.entry_date
				,c.sponsor_id
				,c.period_id
				,c.batch_id
				,c.country
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency) 		as currency			-- Use currency of flag 2 - Comm Payto Currency
				,c.rank_id
				,c.rank_qual
				,c.Earning_1_cap
			from customer_history c
				  left outer join customer_history_flag f
					  on c.customer_id = f.customer_id
					  and c.period_id = f.period_id
					  and c.batch_id = f.batch_id
					  and f.flag_type_id = 2
			where c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id
			and c.type_id not in (6))
		select
			 c.customer_id				as customer_id
			,c.period_id				as period_id 
			,c.batch_id					as batch_id
			,c.type_id					as type_id
			,c.country					as country
			,c.currency					as currency
			,c.comm_status_date			as comm_status_date
			,c.entry_date				as entry_date
			,c.rank_id					as rank_id
			,c.rank_qual				as rank_qual
			,c.Earning_1_cap				as Earning_1_cap
			,s.customer_id				as sponsor_id
			,ifnull(s.rank_id,1)		as sponsor_rank_id
			,ifnull(s.rank_qual,0)		as sponsor_rank_qual
			,s.currency					as sponsor_currency_code
			,s.country					as sponsor_country_code
			,h.hierarchy_level			as hier_level
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.customer_id, a.sponsor_id
			             from lc_Customers a
			             order by customer_id)
	    		Start where sponsor_id = 3) h
	    	, lc_Customers c
	    	, lc_Customers s 
	    where h.customer_id = c.customer_id
	    and h.sponsor_id = s.customer_id;
	*/
		
	-- Get Period Transactions
	lc_Transactions = 
		select
			  t.transaction_id
			 ,ifnull(r.entry_date,t.entry_date) 				as entry_date		-- Return orders use ref transaction_date
			 ,c.period_id
			 ,c.batch_id
		     ,t.customer_id
		     ,t.customer_type_id
		     ,t.type_id
		     ,ifnull(t.value_1,0) 								As pv
		     ,ifnull(t.value_2,0) 								As cv
		     ,ifnull(t.currency,c.currency)						as currency
		From transaction t
			  left outer join transaction r
			  	on t.transaction_ref_id = r.transaction_id
			,:lc_Customers_Level c
		Where t.customer_id = c.customer_id
	   	and ifnull(t.type_id,4) <> 0
	   	and t.period_id = :pn_Period_id;
        
    -- Get Max Level
    select max(hier_level)
    into ln_max
    from :lc_Customers_Level;
		
	-- Get Exchange Rates
	lc_Exchange = 
		select *
		from gl_Exchange(:pn_Period_id);
		
	-- Get Requirements for Unilevel
	lc_Req_Unilevel = 
		select *
		from req_unilevel
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
		
	-- Get Customer Type
	lc_Customer_Type =
		select *
		from customer_type;
		
	-- Insert non retail transactions
	insert into Earning_01
	select 
		 Earning_01_id.nextval			as Earning_01_id
		,t.period_id					as period_id
		,t.batch_id						as batch_id
		,c.sponsor_id					as customer_id
		,t.transaction_id				as transaction_id
		,t.customer_id					as transaction_customer_id
		,t.type_id						as transaction_type_id
		,s.rank_qual					as qual_flag
		,t.pv							as pv
		,t.cv							as cv
		,t.currency						as from_currency
	    ,case 
	     	when upper(c.country) = 'KOR'
	     	and upper(s.country) = 'KOR' 
	     	and upper(t.currency) <> 'KRW' then
	     		'USD'
	     	else
	     		s.currency
	      end							as to_currency				-- Exception for KOR to KOR NFR orders
		,1								as exchange_rate
		,2								as percentage
		,1								as lvl
		,1								as lvl_paid
		,t.cv*req.value_1				as bonus
		,0								as bonus_exchanged
	from :lc_Req_Unilevel req
	   , :lc_Transactions t
	   	 left outer join :lc_Customer_Type t1
	   		on t.customer_type_id = t1.type_id
	   , :lc_Customers_Level c
	   	 left outer join :lc_Customer_Type t2
	   		on c.type_id = t2.type_id
	   , :lc_Customers_Level s
	Where t.customer_id = c.customer_id
	and c.sponsor_id = s.customer_id
	and ifnull(t2.has_retail,-1) = 0
   	and days_between(c.comm_status_date,t.entry_date) > 
   		case when ifnull(t1.has_faststart,-1) = 1 then 60
   		else -1 end
	and t.period_id = req.period_id
	and t.batch_id = req.batch_id
	and req.level_id = 1;
		
	-- Insert retail transactions
	insert into Earning_01
	select 
		 Earning_01_id.nextval			as Earning_01_id
		,t.period_id					as period_id
		,t.batch_id						as batch_id
		,s1.sponsor_id					as customer_id
		,t.transaction_id				as transaction_id
		,s1.customer_id					as transaction_customer_id
		,t.type_id						as transaction_type_id
		,s2.rank_qual					as qual_flag
		,t.pv							as pv
		,t.cv							as cv
		,t.currency						as from_currency
	    ,case 
	     	when upper(c.country) = 'KOR'
	     	and upper(s2.country) = 'KOR'
	     	and upper(t.currency) <> 'KRW' then
	     		'USD'
	     	else
	     		s2.currency
	      end							as to_currency					-- Exception for KOR to KOR NFR orders
		,1								as exchange_rate
		,2								as percentage
		,1								as lvl
		,1								as lvl_paid
		,t.cv*req.value_1				as bonus
		,0								as bonus_exchanged
	from :lc_Req_Unilevel req
	   , :lc_Transactions t
	   , :lc_Customers_Level c
	   	 left outer join :lc_Customer_Type t2
	   		on c.type_id = t2.type_id
	   , :lc_Customers_Level s1
	   , :lc_Customers_Level s2 
	Where t.customer_id = c.customer_id
	and c.sponsor_id = s1.customer_id
	and s1.sponsor_id = s2.customer_id
	and ifnull(t2.has_retail,-1) = 1
	and t.period_id = req.period_id
	and t.batch_id = req.batch_id
	and req.level_id = 1;
	
	commit;
    
    -- Loop through all tree levels from bottom to top
    for ln_x in reverse 1..:ln_max do
    	insert into Earning_01
    	select 
    		 Earning_01_id.nextval												as Earning_01_id
			,p.period_id														as period_id
			,p.batch_id															as batch_id
			,d.sponsor_id														as customer_id
			,p.transaction_id													as transaction_id
			,p.transaction_customer_id											as transaction_customer_id
			,p.transaction_type_id												as transaction_type_id
			,case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then 1
			 	else 0 end														as qual_flag					-- Sponsor's Qualified Rank must be at least the Paid Level
			,p.pv																as pv
			,p.cv																as cv
			,p.from_currency													as from_currency
		    ,case 
		     	when upper(t.country) = 'KOR'
		     	and upper(d.sponsor_country_code) = 'KOR'
		     	and upper(p.from_currency) <> 'KRW' then 'USD'
		     	else d.sponsor_currency_code
		      end																as to_currency					-- Exception for KOR to KOR NFR orders
			,1																	as exchange_rate
			,case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then u.value_1*100
			 	else 0 end														as percentage					-- Increment Level
			,p.lvl+1															as lvl
			,p.lvl_paid+map(p.qual_flag,1,1,0)									as lvl_paid						-- Increment Paid Level when Sponsor Qualifies
			,cv*case 
			 	when p.lvl_paid+map(p.qual_flag,1,1,0) <= d.sponsor_rank_id 
			 	and d.sponsor_rank_qual = 1 then u.value_1
			 	else 0 end														as bonus						-- Bonus is CV * Level Earning percent
			,0																	as bonus_exchanged
		from :lc_Customers_Level d, :lc_Req_Unilevel u, Earning_01 p, :lc_Customers_Level t
		where p.transaction_customer_id = t.customer_id
		and d.customer_id = p.customer_id
		and p.lvl_paid+map(p.qual_flag,1,1,0) = u.level_id
		and d.hier_level = :ln_x
		and p.period_id = :pn_period_id
		and p.batch_id = :pn_Period_Batch_id
		and (p.lvl_paid < 7 or p.qual_flag = 0);																-- Bubble up Transactions that are less than paid Level 7 or when qual_flag is zero
		
        commit;
    end for;
    
    -- Delete all non qualified entries
    delete from Earning_01
    where  period_id = :pn_period_id
	and batch_id = :pn_Period_Batch_id
	and (ifnull(qual_flag,0) = 0 
	 or bonus = 0
	 or lvl = 0);
    
    commit;
    
    -- Get Earning Records
    lc_Earning_Unilevel = 
    	select *
    	from Earning_01
    	where period_id = :pn_period_id
		and batch_id = :pn_Period_Batch_id;
    
    -- Set Currency Rates when From/To not equal
    replace Earning_01 (Earning_01_id, exchange_rate, bonus, bonus_exchanged)
    select 
    	 p.Earning_01_id
   		,round(et.rate / ef.rate,7)
   		,round(p.bonus,ef.round_factor)
   		,round(p.bonus * (et.rate / ef.rate),et.round_factor)
   	from :lc_Earning_Unilevel p
   		left outer join :lc_Exchange ef
   			on p.from_currency = ef.currency
   		left outer join :lc_Exchange et
   			on p.to_currency = et.currency
   	where from_currency <> to_currency;
	
	commit;
    
    -- Set Currency Rates when From/To equal
    replace Earning_01 (Earning_01_id, exchange_rate, bonus, bonus_exchanged)
    select 
    	 p.Earning_01_id
    	,1
   		,round(p.bonus,ef.round_factor)
   		,round(p.bonus,et.round_factor)
   	from :lc_Earning_Unilevel p
		left outer join :lc_Exchange ef
			on p.from_currency = ef.currency
		left outer join :lc_Exchange et
			on p.to_currency = et.currency
   	where from_currency = to_currency;
	
	commit;
	
	-- Insert Elephant Leg Caps
	insert into Earning_01
	select 
		 Earning_01_id.nextval						as Earning_01_id
	    ,period_id									as period_id
	   	,batch_id									as batch_id
	   	,customer_id								as customer_id
	   	,0											as transaction_id
	   	,customer_id								as transaction_customer_id
	   	,4											as transaction_type_id
	   	,1											as qual_flag
	   	,0											as pv
	   	,0											as cv
	   	,to_currency								as from_currency
	   	,to_currency								as to_currency
	   	,rate										as exchange_rate
	   	,0											as percentage
	   	,0											as lvl
	   	,0											as lvl_paid
	   	,Earning_1_cap_exchanged - bonus_exchanged	as bonus
	   	,Earning_1_cap_exchanged - bonus_exchanged	as bonus_exchanged
	from (
		select 
		    	 h.customer_id
		    	,h.period_id
		   		,h.batch_id
		   		,x.rate
		   		,x.round_factor
		   		,p.to_currency
		   		,(h.Earning_1_cap*x.rate) as Earning_1_cap_exchanged
		   		,sum(ifnull(p.bonus_exchanged,0)) as bonus_exchanged
		   	from customer_history h
		   		left outer join Earning_01 p
		   			on h.customer_id = p.customer_id
		   			and h.period_id = p.period_id
		   			and h.period_id = p.period_id
		   			and p.qual_flag = 1
		   		,:lc_Exchange x
		   	where p.to_currency = x.currency
		   	and h.period_id = :pn_Period_id
			and h.batch_id = :pn_Period_Batch_id
			and h.Earning_1_cap <> 0 
			and 1 = case when h.country = 'KOR' and p.to_currency = 'USD' then 0
					else 1 end														-- Exclude Korean NFR Earnings
			group by h.customer_id,h.period_id,h.batch_id,x.rate,x.round_factor,h.Earning_1_cap,p.to_currency)
	where Earning_1_cap_exchanged < bonus_exchanged;
	
	commit;
    
    -- Aggregate Bonus values for each customer
    replace customer_history (customer_id, period_id, batch_id, Earning_1)
    select 
    	 h.customer_id
    	,h.period_id
   		,h.batch_id
   		,sum(ifnull(p.bonus_exchanged,0))
   	from customer_history h
   		left outer join Earning_01 p
   			on h.customer_id = p.customer_id
   			and h.period_id = p.period_id
   			and h.period_id = p.period_id
   			and p.qual_flag = 1
   	where h.period_id = :pn_Period_id
	and h.batch_id = :pn_Period_Batch_id
	and 1 = case when h.country = 'KOR' and p.to_currency = 'USD' then 0
			else 1 end														-- Exclude Korean NFR Earnings
	group by h.customer_id,h.period_id,h.batch_id;
	
	commit;
	
   	Update period_batch
   	Set end_date_Earning_1 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
