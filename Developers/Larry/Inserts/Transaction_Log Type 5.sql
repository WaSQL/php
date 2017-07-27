insert into commissions.transaction (
	transaction_id
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
	,c.customer_id
	,0
	,1
	,c.period_id
	,current_timestamp
	,5
	,1
	,c.country
	,(select currency_code from commissions.country where country_code = c.country)
	,b.pv-(c.vol_1+c.vol_4)
	,b.cv-(c.vol_6+c.vol_9)
from commissions.customer_history c, commissions.orabwt b
where c.customer_id = b.dist_id
and c.period_id = 15
and c.customer_id in (
	select ac.customer_id
	from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, aa.*, round(aa.vol_1+aa.vol_4,2) as agg_pv
		             from (select * 
		                   from commissions.customer_history 
		                   where period_id = 15) aa)
			Start where customer_id = 1) ac, commissions.orabwt ab
	where ac.customer_id = ab.dist_id
	and round(ac.agg_pv,2) <> round(ab.pv,2)
);

/*
replace orabwt (dist_id, customer_name, comm_status_date, terminate_date, qflg2)
select dist_id, customer_name, comm_status_date, terminate_date, qflg2
from orabwta;
*/
