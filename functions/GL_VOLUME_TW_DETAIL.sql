drop function Commissions.gl_Volume_Tw_Detail;
CREATE function Commissions.gl_Volume_Tw_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of Translations detailing Taiwan PV/CV
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @return		table		
*					integer		period_id
*					integer		batch_id
*					integer		customer_id
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
*
* @example		select * from Commissions.gl_Volume_Tw_Detail(10, 0);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
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
			,cv							decimal(18,8))
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
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		   , :lc_Customer c
		Where t.period_id = :pn_Period_id
	   	and t.customer_id = c.customer_id
	   	and upper(t.from_country) = 'TWN';
	else
	*/
		-- if period is closed use customer_history table
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		   , gl_Customer(:pn_Period_id, :pn_Period_Batch_id) c
		Where c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id
	   	and t.period_id = c.period_id
	   	and t.customer_id = c.customer_id
	   	and upper(t.from_country) = 'TWN';
	--end if;
	
end;
