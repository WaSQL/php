drop function Commissions.gl_Volume_Qual_Detail;
CREATE function Commissions.gl_Volume_Qual_Detail(
					 pn_Period_id		integer
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
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of Translations detailing PV/CV

-------------------------------------------------------------------------------- */

begin
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		-- if period is open use customer table
	   	lc_Cust =
	   		select *
	   		from customer;
	   		
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
		From :lc_Cust c
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
		From :lc_Cust r
			,gl_Volume_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
		where r.customer_id = rt.customer_id;
	else
		-- if period is closed use customer_history table
	   	lc_Cust_Hist =
	   		select *
	   		from customer_history
	   		where period_id = :pn_Period_id
	   		and batch_id = :pn_Period_Batch_id;
		
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
		From :lc_Cust_Hist c
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
		From :lc_Cust_Hist r
			,gl_Volume_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
		where r.customer_id = rt.customer_id;
	end if;
	
end;
