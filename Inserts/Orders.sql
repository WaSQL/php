replace commissions.transaction
(transaction_id
,transaction_ref_id
,customer_id
,source_key_id
,source_id
,period_id
,transaction_date
,transaction_type_id
,transaction_category_id
,country
,currency
,value_2
,value_4)
select 
	commissions.transaction_id.nextval
	,0
	,customer_id
	,0
	,1
	,13
	,current_timestamp
	,5 -- Trans Type 5 - Manual
	,1 -- Trans Sub Type 1 - Standard
	,country
	,currency
	,pv_diff
	,cv_diff
from (select c.customer_id, c.country, c.currency, b.pv,round(b.pv,2)-round(c.agg_pv,2) as pv_diff,round(b.cv,2)-round(c.agg_cv,2) as cv_diff
	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*, round(a.vol_1+a.vol_4,2) as agg_pv, round(a.vol_6+a.vol_9,2) as agg_cv
		             from (select * from commissions.customer_history where period_id = 13) a)
			Start where customer_id = 1) c, commissions.orabwt b
	where c.customer_id = b.dist_id
	and round(c.agg_pv,2) <> round(b.pv,2))
