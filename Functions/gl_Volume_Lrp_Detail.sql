drop function Commissions.gl_Volume_Lrp_Detail;
CREATE function Commissions.gl_Volume_Lrp_Detail(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
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
	return
	Select 
	      t.period_id
	     ,t.batch_id
	     ,t.customer_id
	     ,t.rank_id
	     ,t.rank_high_id
	     ,t.sponsor_id
	     ,t.enroller_id
	     ,t.country
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
	from gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
	where ifnull(t.type_id,4) <> 0
	and case 
			when t.type_id = 2 then
				(select ifnull(a.category_id,1)
		   		 from gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) a
		   		 where a.transaction_id = t.transaction_ref_id)
		else 
			ifnull(t.category_id,1) 
		end in (3,6);
	
end;
