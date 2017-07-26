drop function Commissions.gl_Volume_Retail_Detail;
CREATE function Commissions.gl_Volume_Retail_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of Translations detailing Retail PV/CV
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @return		table		
*					integer		period_id
*					integer		batch_id
*					integer		source_id
*		    		varchar		source
*					integer		customer_id
*		    		nvarchar	customer_name
*					integer		customer_type_id
*					varchar		customer_type
*					integer		transaction_customer_id
*		    		nvarchar	transaction_customer_name
*					integer		transaction_customer_type_id
*					varchar		transaction_customer_type
*					integer		transaction_id
*					integer		transaction_ref_id
*					integer		type_id
*					integer		category_id
*					date		entry_date
*					integer		order_number
*					varchar		from_country
*		    		varchar		from_currency
*		    		varchar		to_currency
*					decimal		pv
*					decimal		cv
*					decimal		sales_amt
*
* @example		select * from Commissions.gl_Volume_Retail_Detail(10, 0);
-------------------------------------------------------*/
(pn_Period_id		integer
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

begin
	/*
	if gl_Period_isOpen(:pn_Period_id) = 1 then
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
		From  :lc_Customer c
			left outer join :lc_Exchange x1
			  	on x1.currency = c.currency
			left outer join customer_type t1
			  	on c.type_id = t1.type_id
			left outer join customer_type ct
			 	on ct.type_id = c.type_id
			, :lc_Customer a
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
	*/
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
		From  gl_Customer(:pn_Period_id, :pn_Period_Batch_id) c
			left outer join gl_Exchange(:pn_Period_id) x1
				on x1.currency = c.currency
			left outer join customer_type t1
			  	on c.type_id = t1.type_id
			left outer join customer_type ct
			 	on ct.type_id = c.type_id
			, gl_Customer(:pn_Period_id, :pn_Period_Batch_id) a
			left outer join customer_type at
			 	on at.type_id = a.type_id
			left outer join gl_Exchange(:pn_Period_id) x2
			  	on x2.currency = a.currency
			left outer join customer_type t2
			  	on a.type_id = t2.type_id
			, gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		Where t.customer_id = a.customer_id
		and c.customer_id = a.sponsor_id
		And ifnull(t2.has_retail,-1) = 1
		And ifnull(t1.has_downline,-1) = 1;
	--end if;
	
end;
