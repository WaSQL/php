drop Procedure Commissions.sp_Earning_04_Set;
create Procedure Commissions.sp_Earning_04_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	Update period_batch
	Set beg_date_Earning_4 = current_timestamp
      ,end_date_Earning_4 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Customers
	lc_Customer =
		select 
			  c.hier_level 												as hier_level
			, c.period_id												as Period_id
			, c.batch_id												as Batch_id
			, c.customer_id												as Customer_id
			, c.sponsor_id												as Sponsor_id
			, map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency)	as Currency
			, c.type_id													as Type_id
   			, c.status_id												as Status_id
		from customer_history c
			  left outer join customer_history_flag f
				  on c.customer_id = f.customer_id
				  and c.period_id = f.period_id
				  and c.batch_id = f.batch_id
				  and f.flag_type_id in (2)
		where c.period_id = :pn_Period_id
		and c.batch_id = :pn_Period_Batch_id;
   	
   	-- Get Retail Transactions
   	lc_Transaction =
   		select
			  t.transaction_id						as transaction_id
			, t.type_id								as transaction_type_id
			, t.customer_id							as Customer_id
			, c.type_id								as type_id
			, 'USD'									as Currency
			--, t.currency							as Currency
			, round(t.value_1*(q.value_1/100),2)	as Bonus
   		from transaction t, customer_history c, req_preferred q
   		where t.customer_id = c.customer_id
   		and c.period_id = :pn_Period_id
   		and c.batch_id = :pn_Period_Batch_id
   		and t.period_id = c.period_id
   		and q.period_id = c.period_id
   		and q.version_id = 1
   		and c.type_id = 3
   		and t.value_1 <> 0;
   		
   	-- Get Exchange Rates
   	lc_Exchange = 
   		select *
   		from gl_Exchange(:pn_Period_id);
		
	-- Get Customer Type
	lc_Customer_Type =
		select *
		from customer_type;
		
	-- Get Customer Status
	lc_Customer_Status =
		select *
		from customer_status;
    
    -- Insert Transactions
	insert into Earning_04
	select 
		  s.period_id											as period_id
		, s.batch_id											as batch_id
		, t.transaction_id										as transaction_id
		, s.customer_id											as customer_id
		, 1														as lvl
		, 1														as lvl_paid
		, case
			when ifnull(t1.has_downline,-1) = 1
			and ifnull(s1.has_earnings,-1) = 1 then 1
			else 0 end											as qual_flag
		, fx.currency											as from_currency
		, tx.currency											as to_currency
		, round(tx.rate/fx.rate,7)								as exchange_rate
		, t.bonus												as bonus
		, round(t.bonus * (tx.rate/fx.rate), tx.round_factor)	as Bonus_Exchanged
	from :lc_Transaction t
		,:lc_Customer c
		,:lc_Customer s
	   	 left outer join :lc_Customer_Type t1
	   		on s.type_id = t1.type_id
	   	 left outer join :lc_Customer_Status s1
	   		on s.status_id = s1.status_id
		,:lc_Exchange fx
		,:lc_Exchange tx
	where t.customer_id = c.customer_id
	and c.sponsor_id = s.customer_id
	and t.currency = fx.currency
	and s.currency = tx.currency;
	
	commit;
    
    -- Delete Unqualified entries
	delete from Earning_04
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and qual_flag = 0;
    
   	commit;
   	
   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, Earning_4)
   	select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from Earning_04
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by customer_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_Earning_4 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
