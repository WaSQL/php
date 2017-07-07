drop function Commissions.gl_Volume_Fs_Detail;
CREATE function Commissions.gl_Volume_Fs_Detail(
					 pn_Period_id		integer
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
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of Translations detailing PV/CV

-------------------------------------------------------------------------------- */

begin
	if gl_Period_isOpen(:pn_Period_id) = 1 then
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
		From  gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  on t.transaction_ref_id = r.transaction_id
			, customer c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = :pn_Period_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.entry_date,t.entry_date)) <= 60;
	else
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
		From  gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  on t.transaction_ref_id = r.transaction_id
			, customer_history c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = c.period_id
	   	And c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.entry_date,t.entry_date)) <= 60;
	end if;

end;
