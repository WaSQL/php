with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select 
	c.customer_id
	, c.customer_name
	, c.country
	, c.status_id
	, c.type_id
	, c.sponsor_id
	, c.enroller_id
	, c.agg_pv
	, b.pv
	, round(b.pv,2)-round(c.agg_pv,2) as pv_diff
	, c.vol_6
	, b.cv
	, round(b.cv,2)-round(c.agg_cv,2) as cv_diff
	, c.rank_id
	, c.rank_id-c.rank_high_type_id as rank_diff
from HIERARCHY ( 
	 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*, round(a.vol_1+a.vol_4,2) as agg_pv, round(a.vol_6+a.vol_9,2) as agg_cv
	             from (select a.* 
	                   from commissions.customer_history a, lc_period z
	                   where a.period_id = z.period_id
	                   and a.batch_id = z.batch_id) a)
		Start where customer_id = 3) c
	, commissions.orabwt b
where c.customer_id = b.dist_id
and round(c.agg_pv,2) <> round(b.pv,2);
