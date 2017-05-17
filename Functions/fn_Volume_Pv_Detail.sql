drop function Commissions.fn_Volume_Pv_Detail;
CREATE function Commissions.fn_Volume_Pv_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,source_id					integer
			,source						varchar(20)
			,customer_id				integer
			,sponsor_id					integer
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
		return
		Select 
		      t.period_id										as period_id
		     ,0													as batch_id
		     ,t.source_id
		     ,s.description										as source
		     ,t.customer_id
		     ,c.sponsor_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,t.country											as from_country
		     ,x1.currency										as from_currency
		     ,x2.currency										as to_currency
		     ,ifnull(t.value_2,0)								as pv
		     ,ifnull(t.value_4,0) * (x2.rate / x1.rate)			as cv
		From transaction t
			 left outer join :lc_Exchange x1
			 on x1.currency = t.currency
			 left outer join source s
			 on t.source_id = s.source_id
		   , customer c
			 left outer join :lc_Exchange x2
			 on x2.currency = c.currency
		Where t.customer_id = c.customer_id
		and t.period_id = :pn_Period_id
	   	and ifnull(t.transaction_type_id,4) <> 0
	   	and (ifnull(t.value_2,0) != 0
	   	 or  ifnull(t.value_4,0) != 0);
	else
		-- if period is closed use customer_history table
		return
		Select 
		      c.period_id
		     ,c.batch_id
		     ,t.source_id
		     ,s.description										as source
		     ,t.customer_id
		     ,c.sponsor_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,t.country											as from_country
		     ,x1.currency										as from_currency
		     ,x2.currency										as to_currency
		     ,ifnull(t.value_2,0)								as pv
		     ,ifnull(t.value_4,0 * (x2.rate / x1.rate))			as cv
		From transaction t
			 left outer join :lc_Exchange x1
			 on x1.currency = t.currency
			 left outer join source s
			 on t.source_id = s.source_id
		   , customer_history c
			 left outer join :lc_Exchange x2
			 on x2.currency = c.currency
		Where t.customer_id = c.customer_id
		and c.period_id = :pn_Period_id
		and c.batch_id = :pn_Period_Batch_id
		and t.period_id = c.period_id
	   	and ifnull(t.transaction_type_id,4) <> 0
	   	and (ifnull(t.value_2,0) != 0
	   	 or  ifnull(t.value_4,0) != 0);
	end if;
	
end;
