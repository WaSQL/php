insert into commissions.transaction
select
	 commissions.transaction_id.nextval				as transaction_id
	,ifnull(o.rma_record_number,0)					as transaction_log_ref_id
	,o.dist_id										as customer_id
	,(select type_id
	  from commissions.customer_status_mapping 
	  where status_legacy = o.dist_status
	  and source_id = 1)							as customer_type_id
	,o.record_number								as source_key_id
	,1												as source_id
	,(select period_id 
	  from commissions.period 
	  where beg_date <= to_date(o.pv_date,'yyyymm')
	  and end_date >= to_date(o.pv_date,'yyyymm')
	  and period_type_id = 1
	  and period_id != 0)							as period_id
	,current_timestamp								as entry_date
	,o.entry_date									as order_date
	,null											as bonus_date
	,o.transaction_date								as transaction_date
	,(select transaction_type_id
	  from commissions.transaction_type_mapping
	  where type_legacy = o.order_type)				as transaction_type_id
	,(select transaction_category_id
	  from commissions.transaction_category_mapping
	  where source_legacy = o.order_source)			as transaction_category_id
	,o.transaction_number							as transaction_number
	,(select country
	  from commissions.currency_mapping
	  where currency_legacy = o.country_code)		as country
	,(select currency
	  from commissions.currency_mapping
	  where currency_legacy = o.country_code)		as currency
	,price_1										as value_1
	,price_2										as value_2
	,price_3										as value_3
	,price_4										as value_4
	,price_5										as value_5
from commissions.orabth o;

update commissions.transaction t
set t.transaction_ref_id = (select transaction_id from commissions.transaction where t.transaction_ref_id = source_key_id and period_id = 15)
where t.transaction_ref_id <> 0
and period_id = 15;

select *
from commissions.transaction
where transaction_ref_id <> 0
and period_id = 15;
