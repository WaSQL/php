drop Procedure Commissions.Payout_Power3_Set;
create Procedure Commissions.Payout_Power3_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
    declare ln_max      integer;
    declare ln_count    integer = 1;
    declare ln_x        integer = 1;
    declare ln_y        integer;
    
	call Payout_Power3_Clear(:pn_Period_id, :pn_Period_Batch_id);
    
	Update period_batch
	Set beg_date_payout_2 = current_timestamp
      ,end_date_payout_2 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Period Customers
    lc_Customers_Level = 
    	with lc_Customers as (
    		select 
				 c.customer_id
				,c.sponsor_id
				,c.type_id
				,ifnull(c.comm_status_date,
					case when c.type_id in (1,4,5) then c.entry_date								-- Type Wellness, Professional and Wholesale default to entry_date
					else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
				,c.entry_date
				,c.period_id
				,c.batch_id
				,c.country
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,co.currency_code) 	as currency			-- Use currency of flag 2 - Comm Payto Currency
				,1 																as version_id
				,vol_1+vol_4													as pv
				,vol_2															as pv_lrp
				,map(ifnull(f.flag_type_id,0),6,1,0) 							as pv_lrp_waiver
				,vol_14															as tv
				,map(ifnull(f.flag_type_id,0),7,1,0) 							as tv_waiver
			from customer_history c
				  left outer join customer_history_flag f
					  on c.customer_id = f.customer_id
					  and c.period_id = f.period_id
					  and c.batch_id = f.batch_id
					  and f.flag_type_id in (2,6,7)
				  left outer join country co
				  	  on c.country = co.country_code
			where c.period_id = :pn_Period_id
			and c.batch_id = :pn_Period_Batch_id
			and c.type_id in (1,5)
			and c.status_id in (1,4))
		select
			 c.customer_id				as customer_id
			,c.sponsor_id				as sponsor_id
			,c.period_id				as period_id 
			,c.batch_id					as batch_id
			,c.type_id					as type_id
			,c.country					as country
			,c.currency					as currency
			,map(c.country,'KOR',1000,e.rate)	as exchange_rate
			,e.round_factor				as round_factor
			,c.comm_status_date			as comm_status_date
			,c.entry_date				as entry_date
			,c.version_id				as version_id
			,c.pv						as pv
			,c.pv_lrp					as pv_lrp
			,c.pv_lrp_waiver			as pv_lrp_waiver
			,c.tv						as tv
			,c.tv_waiver				as tv_waiver
			,h.hierarchy_level			as level_id
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.customer_id, a.sponsor_id
			             from lc_Customers a
			             order by customer_id)
	    		Start where sponsor_id = 3) h
	    	, lc_Customers c
	    	, fn_Exchange(:pn_Period_id) e
	    where h.customer_id = c.customer_id
	    and c.currency = e.currency;
        
    -- Get Max Level
    select max(level_id)
    into ln_max
    from :lc_Customers_Level;
		
	-- Get Exchange Rates
	lc_Exchange = 
		select *
		from fn_Exchange(:pn_Period_id);
	    
	-- Get Requirements for Power3
	lc_Req_Power3 =
		select *
		from req_power3_history
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
    
    while :ln_count <> 0 do
	    -- Loop through all tree levels from bottom to top
	    for ln_y in reverse 0..:ln_max do
			lc_Qual =
				select sponsor_id, customer_id, case when paid_lvl_id > 1 then 4 else lvl_id end as lvl_id, paid_lvl_id
				from payout_power3
				where period_id = :pn_Period_id
				and batch_id = :pn_Period_Batch_id;
				
			replace payout_power3 (period_id, batch_id, sponsor_id, customer_id, lvl_id, paid_lvl_id, from_currency, to_currency, exchange_rate)
	    	select 
	    		 c.period_id
	    		,c.batch_id 
	    		,c.sponsor_id												as Sponsor_id
	    		,c.customer_id												as Customer_id
	    		,max(r.level_id) 											as Lvl_id
	    		,:ln_x + map(max(r.level_id),4,1,0)							as Paid_Lvl_id
	    		,'USD'														as from_currency
	    		,c.currency													as to_currency
	    		,c.exchange_rate											as exchange_rate
	    	from :lc_Customers_Level c
	    		left outer join :lc_Qual p
		    		on c.customer_id = p.customer_id
	    		, :lc_Req_Power3 r
	    	where c.version_id = r.version_id
		    and ifnull(p.paid_lvl_id,1) = :ln_x
		   	and c.level_id = :ln_y
		   	and (c.pv_lrp >= (r.value_1 * :ln_x) or c.pv_lrp_waiver = 1 or case when :ln_x > 1 then c.pv else c.pv_lrp end >= (r.value_1 * :ln_x))
		   	and (c.tv >= (r.value_2 * :ln_x) or c.tv_waiver = 1)
		   	and (select count(*)
		   		 from :lc_Qual
		   		 where sponsor_id = c.customer_id
		   		 and lvl_id >= r.structure_level) >= (r.structure_count * :ln_x)
	    	group by c.period_id, c.batch_id, c.sponsor_id, c.customer_id, c.currency, c.exchange_rate
		   	having max(r.level_id) > 0;
			
	        commit;
	    end for;
	    
	    ln_x = :ln_x + 1;
	    
	    select count(*)
	    into ln_count
	    from payout_power3
	    where paid_lvl_id = :ln_x;
    end while;
    
    -- Set Payout Amounts
	replace payout_power3 (period_id, batch_id, sponsor_id, customer_id, bonus, bonus_exchanged)
	select 
		 p.period_id
		,p.batch_id
		,p.sponsor_id
		,p.customer_id
		,((select value_3
			from :lc_Req_Power3
			where level_id = 4)*(p.paid_lvl_id-1))
		 + (select map(level_id,4,0,value_3)
		 	from :lc_Req_Power3
			where level_id = p.lvl_id)										as bonus
		,round((((select value_3
			from :lc_Req_Power3
			where level_id = 4)*(p.paid_lvl_id-1))
		 + (select map(level_id,4,0,value_3)
		 	from :lc_Req_Power3
			where level_id = p.lvl_id)) * p.exchange_rate, x.round_factor) 	as bonus_exchanged
	from payout_power3 p, :lc_Exchange x
	where p.period_id = :pn_Period_id
	and p.batch_id = :pn_Period_Batch_id
	and p.to_currency = x. currency;
	
	commit;
    
    -- Aggregate Bonus values for each customer
    replace customer_history (customer_id, period_id, batch_id, payout_2)
    select 
    	 customer_id
    	,period_id
   		,batch_id
   		,bonus_exchanged
   	from payout_power3
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
   	Update period_batch
   	Set end_date_payout_2 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
