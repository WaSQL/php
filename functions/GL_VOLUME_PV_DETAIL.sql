drop function Commissions.gl_Volume_Pv_Detail;
CREATE function Commissions.gl_Volume_Pv_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of Translations detailing PV/CV
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @return		table		
*					integer		period_id
*					integer		batch_id
*					integer		source_id
*					varchar		source
*					integer		customer_id
*					nvarchar	customer_name
*					integer		customer_type_id
*					varchar		customer_type
*					integer		rank_id
*					integer		rank_high_id
*					integer		sponsor_id
*					integer		enroller_id
*					varchar		country
*					integer		transaction_id
*					integer		transaction_ref_id
*					integer		type_id
*					integer		category_id
*					date		entry_date
*		    		integer		order_number
*					varchar		from_country
*		    		varchar		from_currency
*		    		varchar		to_currency
*					decimal		pv
*					decimal		cv
*					decimal		sales_amt
*
* @example		select * from Commissions.gl_Volume_Pv_Detail(10, 0);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,source_id					integer
			,source						varchar(20)
			,customer_id				integer
			,customer_name				nvarchar(900)
			,customer_type_id			integer
			,customer_type				varchar(20)
			,rank_id					integer
			,rank_high_id				integer
			,sponsor_id					integer
			,enroller_id				integer
			,country					varchar(5)
			,transaction_id				integer
			,transaction_ref_id			integer
			,type_id					integer
			,category_id				integer
			,entry_date					date
		    ,order_number				integer
			,from_country				varchar(4)
		    ,from_currency				varchar(5)
		    ,to_currency				varchar(5)
			,pv							decimal(18,8)
			,cv							decimal(18,8)
			,sales_amt					decimal(18,8))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	/*
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		-- if period is open use customer table
		return
		Select 
		      t.period_id										as period_id
		     ,0													as batch_id
		     ,t.source_id
		     ,s.description										as source
		     ,t.customer_id
		     ,c.customer_name
		     ,t.customer_type_id
		     ,ct.description									as customer_type
		     ,c.rank_id
		     ,c.rank_high_id
		     ,c.sponsor_id
		     ,c.enroller_id
		     ,c.country
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.country											as from_country
		     ,x1.currency										as from_currency
		     ,x2.currency										as to_currency
		     ,ifnull(t.value_1,0)								as pv
		     ,ifnull(t.value_2,0) * (x2.rate / x1.rate)			as cv
		     ,ifnull(t.value_3,0)								as sales_amt
		From transaction t
			 left outer join :lc_Exchange x1
			 	on x1.currency = t.currency
			 left outer join source s
			 	on t.source_id = s.source_id
		   , :lc_Customer c
			 left outer join :lc_Exchange x2
			 	on x2.currency = c.currency
			 left outer join customer_type ct
			 	on ct.type_id = c.type_id
		Where t.customer_id = c.customer_id
		and t.period_id = :pn_Period_id
	   	and ifnull(t.type_id,4) <> 0
	   	and (ifnull(t.value_1,0) != 0
	   	 or  ifnull(t.value_2,0) != 0);
	else
	*/
		-- if period is closed use customer_history table
		return
		Select 
		      c.period_id
		     ,c.batch_id
		     ,t.source_id
		     ,s.description										as source
		     ,t.customer_id
		     ,c.customer_name
		     ,t.customer_type_id
		     ,ct.description									as customer_type
		     ,c.rank_id
		     ,c.rank_high_id
		     ,c.sponsor_id
		     ,c.enroller_id
		     ,c.country
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.country											as from_country
		     ,x1.currency										as from_currency
		     ,x2.currency										as to_currency
		     ,ifnull(t.value_1,0)								as pv
		     ,ifnull(t.value_2,0 * (x2.rate / x1.rate))			as cv
		     ,ifnull(t.value_3,0)								as sales_amt
		From transaction t
			 left outer join gl_Exchange(:pn_Period_id) x1
			 	on x1.currency = t.currency
			 left outer join source s
			 	on t.source_id = s.source_id
		   , gl_Customer(:pn_Period_id, :pn_Period_Batch_id) c
			 left outer join gl_Exchange(:pn_Period_id) x2
			 	on x2.currency = c.currency
			 left outer join customer_type ct
			 	on ct.type_id = c.type_id
		Where t.customer_id = c.customer_id
		and c.period_id = :pn_Period_id
		and c.batch_id = :pn_Period_Batch_id
		and t.period_id = c.period_id
	   	and ifnull(t.type_id,4) <> 0
	   	and (ifnull(t.value_1,0) != 0
	   	 or  ifnull(t.value_2,0) != 0);
	--end if;
	
end;
