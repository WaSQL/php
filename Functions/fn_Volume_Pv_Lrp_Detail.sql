drop function Commissions.fn_Volume_Pv_Lrp_Detail;
CREATE function Commissions.fn_Volume_Pv_Lrp_Detail(
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
	from fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
	where ifnull(t.transaction_type_id,4) <> 0
	and case 
			when t.transaction_type_id = 2 then
				(select ifnull(a.transaction_category_id,1)
		   		 from fn_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) a
		   		 where a.transaction_id = t.transaction_ref_id)
		else 
			ifnull(t.transaction_category_id,1) 
		end in (3,6);
	
end;
