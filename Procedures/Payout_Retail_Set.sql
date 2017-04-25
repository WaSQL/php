drop Procedure Commissions.Payout_Retail_Set;
create Procedure Commissions.Payout_Retail_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
    call Payout_Retail_Clear(:pn_Period_id, :pn_Period_Batch_id);
    
	Update period_batch
	Set beg_date_payout_3 = current_timestamp
      ,end_date_payout_3 = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Retail Transactions
   	lc_Transaction =
   		select *
   		from transaction
   		where period_id = :pn_Period_id
   		and ifnull(value_5,0) <> 0;
   		
   	-- Get Exchange Rates
   	lc_Exchange = 
   		select *
   		from fn_Exchange(:pn_Period_id);
   		
   	-- Insert Retail Volume into Payout Table
   	insert into payout_retail
   	select 
		  c.period_id												as Period_id
		, c.batch_id												as Batch_id
		, t.transaction_id											as transaction_id
		, s.customer_id												as Sponsor_id
		, t.customer_id												as Customer_id
		, t.currency												as From_Currency
		, map(ifnull(f.flag_type_id,0),2,f.flag_value,s.currency)	as To_Currency
		, round(tx.rate/fx.rate,7)									as Exchange_Rate
		, t.value_5													as Bonus
		, round(t.value_5 * (tx.rate/fx.rate), tx.round_factor)		as Bonus_Exchanged
	from  HIERARCHY ( 
			 	SOURCE (select customer_id AS node_id, sponsor_id AS parent_id, a.*
			            from customer_history a
			            where period_id = :pn_Period_id
						and batch_id = :pn_Period_Batch_id
			            order by customer_id)
	    		Start where sponsor_id = 3) c
		, customer_history s
			  left outer join customer_history_flag f
				  on s.customer_id = f.customer_id
				  and s.period_id = f.period_id
				  and s.batch_id = f.batch_id
				  and f.flag_type_id in (2,6,7)
		, :lc_Transaction t
		, :lc_Exchange fx
		, :lc_Exchange tx
	where c.customer_id = t.customer_id
	and c.sponsor_id = s.customer_id
	and c.period_id = s.period_id
	and c.batch_id = s.batch_id
	and c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
   	and t.currency = fx.currency
   	and s.type_id in (1)
   	and s.status_id in (1,4)
   	and map(ifnull(f.flag_type_id,0),2,f.flag_value,s.currency) = tx.currency;
   	
   	commit;
   	
   	-- Aggregate Bonus values for each customer
   	replace customer_history (customer_id, period_id, batch_id, payout_3)
   	select 
    	 sponsor_id
    	,period_id
   		,batch_id
   		,sum(bonus_exchanged)
   	from payout_retail
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	group by sponsor_id,period_id,batch_id;
   	
   	commit;
	
   	Update period_batch
   	Set end_date_payout_3 = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
