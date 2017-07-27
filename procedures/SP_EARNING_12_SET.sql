drop Procedure Commissions.sp_Earning_12_Set;
create Procedure Commissions.sp_Earning_12_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   SQL SECURITY INVOKER
   DEFAULT SCHEMA Commissions
AS
    
Begin
	Update period_batch
	Set beg_date_Earning_12 = current_timestamp
      ,end_date_Earning_12 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Customers
	lc_Customer =
		select *
		from gl_Customer(:pn_Period_id, :pn_Period_Batch_id)
		where hier_level <> 0;

   	-- Get Retail Transactions
   	lc_Transaction =
   		select
			  t.transaction_id						as transaction_id
			, t.type_id								as transaction_type_id
			, c.customer_id							as Customer_id
			, c.enroller_id							as Enroller_id
			, c.type_id								as type_id
			, t.from_currency						as Currency
			, t.cv									as CV
   		from gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  	on t.transaction_ref_id = r.transaction_id
   			  left outer join :lc_Customer c
   			  	on c.customer_id = t.customer_id
   		where t.customer_type_id = 4
   		and t.cv <> 0
   		And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.entry_date,t.entry_date)) > 60;

   	-- Get Exchange Rates
   	lc_Exchange = 
   		select *
   		from gl_Exchange(:pn_Period_id);

    -- Insert Transactions
	insert into Earning_12
	select 
		  e.period_id												as period_id
		, e.batch_id												as batch_id
		, t.transaction_id											as transaction_id
		, e.customer_id												as customer_id
		, t.currency												as from_currency
		, e.currency												as to_currency
		, round(tx.rate/fx.rate,7)									as exchange_rate
		, round(t.cv*.05,fx.round_factor)							as bonus
		, round((t.cv*.05) * (tx.rate/fx.rate), tx.round_factor)	as Bonus_Exchanged
	from :lc_Transaction t
		,:lc_Customer c
		,:lc_Customer e
		,:lc_Exchange fx
		,:lc_Exchange tx
	where t.customer_id = c.customer_id
	and c.enroller_id = e.customer_id
	and t.currency = fx.currency
	and e.currency = tx.currency;
	
	commit;

   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, Earning_12)
   	select 
    	 customer_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from Earning_12
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by customer_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_Earning_12 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
