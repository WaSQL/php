drop function Commissions.fn_Volume_Pv_Retail_Detail;
CREATE function Commissions.fn_Volume_Pv_Retail_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
		    ,source_id					integer
		    ,source						varchar(20)
			,customer_id				integer
			,transaction_customer_id	integer
			,transaction_id				integer
			,transaction_ref_id			integer
			,transaction_type_id		integer
			,transaction_category_id	integer
			,transaction_date			date
			,transaction_number			integer
			,from_country				varchar(4)
		    ,from_currency				varchar(5)
		    ,to_currency				varchar(5)
			,pv							decimal(18,8)
			,cv							decimal(18,8))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of Translations detailing PV/CV

-------------------------------------------------------------------------------- */

begin
	declare ln_Closed		integer;
	
	-- Get Period Exchange Rates
	lc_Exchange =
		select *
		from fn_exchange(:pn_Period_id);
		
	-- Get period closed status
	select map(closed_date,null,0,1)
	into ln_Closed
	from period
	where period_id = :pn_Period_id;
	
	
	if :ln_Closed = 0 then
		-- if period is open use customer table
	   	lc_Cust =
	   		select *
	   		from customer;
	   		
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.source_id
		     ,t.source
		     ,c.customer_id
		     ,a.customer_id									as transaction_customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,a.country										as from_country
		     ,x2.currency									as from_currency
		     ,x1.currency									as to_currency
		     ,t.pv
		     ,ifnull(t.cv,0) * (x1.rate / x2.rate)			as cv
		From  :lc_Cust c
			  left outer join :lc_Exchange x1
			  on x1.currency = c.currency
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
			, :lc_Cust a
			  left outer join :lc_Exchange x2
			  on x2.currency = a.currency
			  left outer join customer_type t2
			  on a.type_id = t2.type_id
			, fn_Volume_Pv_Detail(:pn_Period_id, 0) t
		Where t.customer_id = c.customer_id
		and c.customer_id = a.sponsor_id
		And ifnull(t2.has_retail,-1) = 1
		And ifnull(t1.has_downline,-1) = 1;
	else
		-- if period is closed use customer_history table
	   	lc_Cust_Hist =
	   		select *
	   		from customer_history
	   		where period_id = :pn_Period_id
	   		and batch_id = :pn_Period_Batch_id;
		
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.source_id
		     ,t.source
		     ,c.customer_id
		     ,a.customer_id									as transaction_customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,a.country										as from_country
		     ,x2.currency									as from_currency
		     ,x1.currency									as to_currency
		     ,t.pv
		     ,ifnull(t.cv,0) * (x1.rate / x2.rate)			as cv
		From  :lc_Cust_Hist c
			  left outer join :lc_Exchange x1
			  on x1.currency = c.currency
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
			, :lc_Cust_Hist a
			  left outer join :lc_Exchange x2
			  on x2.currency = a.currency
			  left outer join customer_type t2
			  on a.type_id = t2.type_id
			, fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		Where t.customer_id = a.customer_id
		and c.customer_id = a.sponsor_id
		And ifnull(t2.has_retail,-1) = 1
		And ifnull(t1.has_downline,-1) = 1;
	end if;
	
end;
