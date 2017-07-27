drop function Commissions.gl_Volume_Qual_Detail;
CREATE function Commissions.gl_Volume_Qual_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of Translations detailing Qual PV/CV
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @return		table		
*					integer		period_id
*					integer		batch_id
*					integer		customer_id
*					varchar		country
*					integer		sponsor_id
*					integer		enroller_id
*					integer		rank_id
*					integer		rank_high_id
*					integer		transaction_id
*					integer		transaction_customer_id
*		    		integer		order_number
*					decimal		pv
*
* @example		select * from Commissions.gl_Volume_Qual_Detail(10, 0);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
			,country					varchar(5)
			,sponsor_id					integer
			,enroller_id				integer
			,rank_id					integer
			,rank_high_id				integer
			,transaction_id				integer
			,transaction_customer_id	integer
		    ,order_number				integer
			,pv							decimal(18,8))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	/*
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		Select 
			  ct.period_id
			 ,ct.batch_id
			 ,ct.customer_id
			 ,c.country
			 ,c.sponsor_id
			 ,c.enroller_id
			 ,c.rank_id
			 ,c.rank_high_id
			 ,ct.transaction_id
			 ,ct.customer_id 		as transaction_customer_id
			 ,ct.order_number
			 ,ifnull(ct.pv,0)		as pv
		From :lc_Customer c
			,gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) ct
			,customer_type t
		where c.customer_id = ct.customer_id
		and c.type_id = t.type_id
		and t.has_retail <> 1
		union all
		Select 
			  rt.period_id
			 ,rt.batch_id
			 ,rt.customer_id
			 ,r.country
			 ,r.sponsor_id
			 ,r.enroller_id
			 ,r.rank_id
			 ,r.rank_high_id
			 ,rt.transaction_id
			 ,rt.customer_id 		as transaction_customer_id
			 ,rt.order_number
			 ,ifnull(rt.pv,0)		as pv
		From :lc_Customer r
			,gl_Volume_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
		where r.customer_id = rt.customer_id;
	else
	*/
		return
		Select 
			  ct.period_id
			 ,ct.batch_id
			 ,ct.customer_id
			 ,c.country
			 ,c.sponsor_id
			 ,c.enroller_id
			 ,c.rank_id
			 ,c.rank_high_id
			 ,ct.transaction_id
			 ,ct.customer_id 		as transaction_customer_id
			 ,ct.order_number
			 ,ifnull(ct.pv,0)		as pv
		From gl_Customer(:pn_Period_id, :pn_Period_Batch_id) c
			,gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) ct
			,customer_type t
		where c.customer_id = ct.customer_id
		and c.type_id = t.type_id
		and t.has_retail <> 1
		union all
		Select 
			  rt.period_id
			 ,rt.batch_id
			 ,rt.customer_id
			 ,r.country
			 ,r.sponsor_id
			 ,r.enroller_id
			 ,r.rank_id
			 ,r.rank_high_id
			 ,rt.transaction_id
			 ,rt.transaction_customer_id
			 ,rt.order_number
			 ,ifnull(rt.pv,0)		as pv
		From gl_Customer(:pn_Period_id, :pn_Period_Batch_id) r
			,gl_Volume_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
		where r.customer_id = rt.customer_id;
	--end if;
	
end;
