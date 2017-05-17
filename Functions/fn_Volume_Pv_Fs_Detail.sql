drop function Commissions.fn_Volume_Pv_Fs_Detail;
CREATE function Commissions.fn_Volume_Pv_Fs_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
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
		
	-- Get period closed status
	select map(closed_date,null,0,1)
	into ln_Closed
	from period
	where period_id = :pn_Period_id;
	
	if :ln_Closed = 0 then
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From  fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  on t.transaction_ref_id = r.transaction_id
			, customer c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = :pn_Period_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.transaction_type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) <= 60;
	else
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.transaction_type_id
		     ,t.transaction_category_id
		     ,t.transaction_date
		     ,t.transaction_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From  fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  on t.transaction_ref_id = r.transaction_id
			, customer_history c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = c.period_id
	   	And c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.transaction_type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) <= 60;
	end if;

end;
