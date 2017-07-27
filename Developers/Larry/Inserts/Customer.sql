truncate table commissions.customer;

insert into commissions.customer
select 
	 to_integer(dist_id)						as customer_id
	,customer_name								as customer_name
	,to_integer(dist_id)						as source_key_id
	,1											as source_id
	,(select type_id
	  from commissions.customer_status_mapping 
	  where status_legacy = o.status
	  and source_id = 1)						as type_id
	,(select status_id 
	  from commissions.customer_status_mapping 
	  where status_legacy = o.status
	  and source_id = 1)						as status_id
	,sponsor_id									as sponsor_id
	,enroller_id								as enroller_id
	,(select country
	  from commissions.country_mapping
	  where country_legacy = o.country
	  and source_id = 1)						as country
	,(select currency
	  from commissions.country_mapping
	  where country_legacy = o.country
	  and source_id = 1)						as currency
	,to_date(comm_status_date, 'dd-Mon-yyyy')	as comm_status_date
	,to_date(entry_date, 'dd-Mon-yyyy')			as entry_date
	,to_date(terminate_date, 'dd-Mon-yyyy')		as termination_date
	,rank_id			as rank_id
	,high_rank_id		as rank_high_id
	,rank_id			as rank_high_type_id
	,qflg2				as rank_qual
	,0					as vol_1
	,0					as vol_2
	,0					as vol_3
	,0					as vol_4
	,0					as vol_5
	,0					as vol_6
	,0					as vol_7
	,0					as vol_8
	,0					as vol_9
	,0					as vol_10
	,0					as vol_11
	,0					as vol_12
	,0					as vol_13
	,0					as vol_14
	,0					as vol_15
from commissions.orabwt o
where dist_id < 2000000000
order by dist_id;

replace commissions.customer (customer_id, customer_name)
select
	customer_id
	,customer_name
from commissions.orabwt_a;
