drop function Commissions.fn_Volume_Pv_Qual_Detail;
CREATE function Commissions.fn_Volume_Pv_Qual_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
			,sponsor_id				integer
			,transaction_id				integer
			,transaction_customer_id	integer
			,pv							decimal(18,8))
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
			  ct.period_id
			 ,ct.batch_id
			 ,ct.customer_id
			 ,c.sponsor_id
			 ,ct.transaction_id
			 ,ct.customer_id 		as transaction_customer_id
			 ,ifnull(ct.pv,0)		as pv
		From :lc_Cust c
			,fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) ct
			,customer_type t
		where c.customer_id = ct.customer_id
		and c.type_id = t.type_id
		and t.has_retail <> 1
		union all
		Select 
			  rt.period_id
			 ,rt.batch_id
			 ,rt.customer_id
			 ,r.sponsor_id
			 ,rt.transaction_id
			 ,rt.customer_id 		as transaction_customer_id
			 ,ifnull(rt.pv,0)		as pv
		From :lc_Cust r
			,fn_Volume_Pv_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
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
			 ,c.sponsor_id
			 ,ct.transaction_id
			 ,ct.customer_id 		as transaction_customer_id
			 ,ifnull(ct.pv,0)		as pv
		From :lc_Cust_Hist c
			,fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) ct
			,customer_type t
		where c.customer_id = ct.customer_id
		and c.type_id = t.type_id
		and t.has_retail <> 1
		union all
		Select 
			  rt.period_id
			 ,rt.batch_id
			 ,rt.customer_id
			 ,r.sponsor_id
			 ,rt.transaction_id
			 ,rt.transaction_customer_id
			 ,ifnull(rt.pv,0)		as pv
		From :lc_Cust_Hist r
			,fn_Volume_Pv_Retail_Detail(:pn_Period_id, :pn_Period_Batch_id) rt
		where r.customer_id = rt.customer_id;
	end if;
	
end;
