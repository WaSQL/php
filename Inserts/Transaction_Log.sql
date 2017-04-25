--truncate table commissions.transaction;
--truncate table commissions.orabth;

--select distinct order_source
--from orabth

--drop SEQUENCE "COMMISSIONS"."TRANSACTION_LOG_ID";
--CREATE SEQUENCE "COMMISSIONS"."TRANSACTION_LOG_ID";

/*
replace transaction_log (transaction_log_id, period_id)
select *
from (
	select
	transaction_log_id
	,(select period_id 
		  from period 
		  where beg_date <= to_date(o.pv_date,'yyyymm')
		  and end_date >= to_date(o.pv_date,'yyyymm'))	as period_id
	from transaction_log t, orabth o
	where t.source_key_id = o.record_number
	and t.period_id is null)
where period_id is not null;
*/

insert into commissions.transaction
select
	 commissions.transaction_id.nextval				as transaction_id
	,ifnull(o.rma_record_number,0)					as transaction_log_ref_id
	,o.dist_id										as customer_id
	,(select type_id
	  from commissions.customer_status_mapping 
	  where status_legacy = o.dist_status
	  and source_id = 1)							as type_id
	,o.record_number								as source_key_id
	,1												as source_id
	,(select period_id 
	  from commissions.period 
	  where beg_date <= to_date(o.pv_date,'yyyymm')
	  and end_date >= to_date(o.pv_date,'yyyymm')
	  and period_type_id = 1
	  and period_id != 0)							as period_id
	,o.transaction_date								as transaction_date
	,(select transaction_type_id
	  from commissions.transaction_type_mapping
	  where type_legacy = o.order_type)				as transaction_type_id
	,(select transaction_category_id
	  from commissions.transaction_category_mapping
	  where source_legacy = o.order_source)			as transaction_category_id
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
	,price_6										as value_6
	,price_7										as value_7
	,price_8										as value_8
	,price_9										as value_9
	,0												as value_10
	,0												as value_11
	,0												as value_12
	,0												as value_13
	,0												as value_14
	,0												as value_15
	,0												as flag_1
	,0												as flag_2
	,0												as flag_3
	,0												as flag_4
	,0												as flag_5
	,null											as note
from commissions.orabth o;

--where dist_bus_ctr = 1
--and pv_date >= 201604
--and pv_date <= 201701;

update commissions.transaction t
set t.transaction_ref_id = (select transaction_id from commissions.transaction where t.transaction_ref_id = source_key_id and period_id = 13)
where t.transaction_ref_id <> 0;

--truncate table orabth;
