drop function Commissions.gl_Volume_Retail_Detail;
CREATE function Commissions.gl_Volume_Retail_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 						integer
			,batch_id						integer
		    ,source_id						integer
		    ,source							varchar(20)
			,customer_id					integer
		    ,customer_name					nvarchar(900)
			,customer_type_id				integer
			,customer_type					varchar(20)
			,transaction_customer_id		integer
		    ,transaction_customer_name		nvarchar(900)
			,transaction_customer_type_id	integer
			,transaction_customer_type		varchar(20)
			,transaction_id					integer
			,transaction_ref_id				integer
			,type_id						integer
			,category_id					integer
			,entry_date						date
			,order_number					integer
			,from_country					varchar(4)
		    ,from_currency					varchar(5)
		    ,to_currency					varchar(5)
			,pv								decimal(18,8)
			,cv								decimal(18,8)
			,sales_amt						decimal(18,8))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of Translations detailing PV/CV

-------------------------------------------------------------------------------- */

begin
	-- Get Period Exchange Rates
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
		
	if gl_Period_isOpen(:pn_Period_id) = 1 then
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
		     ,c.customer_name
		     ,t.customer_type_id
		     ,ct.description								as customer_type
		     ,a.customer_id									as transaction_customer_id
		     ,a.customer_name								as transaction_customer_name
		     ,a.type_id										as transaction_customer_type_id
		     ,at.description								as transaction_customer_type
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,a.country										as from_country
		     ,x2.currency									as from_currency
		     ,x1.currency									as to_currency
		     ,t.pv
		     ,ifnull(t.cv,0) * (x1.rate / x2.rate)			as cv
		     ,t.sales_amt
		From  :lc_Cust c
			left outer join :lc_Exchange x1
			  	on x1.currency = c.currency
			left outer join customer_type t1
			  	on c.type_id = t1.type_id
			left outer join customer_type ct
			 	on ct.type_id = c.type_id
			, :lc_Cust a
			left outer join customer_type at
			 	on at.type_id = a.type_id
			left outer join :lc_Exchange x2
				on x2.currency = a.currency
			left outer join customer_type t2
			  	on a.type_id = t2.type_id
			, gl_Volume_Pv_Detail(:pn_Period_id, 0) t
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
		     ,c.customer_name
		     ,t.customer_type_id
		     ,ct.description								as customer_type
		     ,a.customer_id									as transaction_customer_id
		     ,a.customer_name								as transaction_customer_name
		     ,a.type_id										as transaction_customer_type_id
		     ,at.description								as transaction_customer_type
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,a.country										as from_country
		     ,x2.currency									as from_currency
		     ,x1.currency									as to_currency
		     ,t.pv
		     ,ifnull(t.cv,0) * (x1.rate / x2.rate)			as cv
		     ,t.sales_amt
		From  :lc_Cust_Hist c
			left outer join :lc_Exchange x1
				on x1.currency = c.currency
			left outer join customer_type t1
			  	on c.type_id = t1.type_id
			left outer join customer_type ct
			 	on ct.type_id = c.type_id
			, :lc_Cust_Hist a
			left outer join customer_type at
			 	on at.type_id = a.type_id
			left outer join :lc_Exchange x2
			  	on x2.currency = a.currency
			left outer join customer_type t2
			  	on a.type_id = t2.type_id
			, gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		Where t.customer_id = a.customer_id
		and c.customer_id = a.sponsor_id
		And ifnull(t2.has_retail,-1) = 1
		And ifnull(t1.has_downline,-1) = 1;
	end if;
	
end;
