select c.customer_id, c.customer_name, c.country, c.status_id, c.type_id, c.sponsor_id, c.enroller_id, c.agg_pv, b.pv,round(c.agg_pv,2) - round(b.pv,2) as diff, c.vol_6, b.cv, c.vol_13, b.ov, c.rank_id, c.rank_id-c.rank_high_type_id as rank_diff
from HIERARCHY ( 
	 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*, round(a.vol_1+a.vol_4,2) as agg_pv
	             from (select * from commissions.customer_history where period_id = 12) a)
		Start where customer_id = 3) c, commissions.orabwt b
where c.customer_id = b.dist_id
--and round(c.agg_pv,2) <> round(b.pv,2)
and round(c.vol_13,2) <> round(b.ov,2)
--and c.customer_id = 1001;