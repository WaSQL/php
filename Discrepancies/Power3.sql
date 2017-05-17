with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select 
	  h.hierarchy_level
	, to_char(h.customer_id)	as customer_id
	, h.vol_1+h.vol_4			as pv
	, h.vol_2 					as pv_lrp
	, h.vol_14					as tv
	, p.lvl_id
	, p.paid_lvl_id
	, (select count(*)
	   from commissions.payout_02
	   where period_id = 13
	   and batch_id = 0
	   and (lvl_id >= 1 
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)	as po3_lvl_1
	, (select count(*)
	   from commissions.payout_02
	   where period_id = 13
	   and batch_id = 0
	   and (lvl_id >= 2 
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)	as po3_lvl_2
	, (select count(*)
	   from commissions.payout_02
	   where period_id = 13
	   and batch_id = 0
	   and (lvl_id >= 3
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)	as po3_lvl_3
	, p.to_currency
	, p.exchange_rate
	, p.bonus
	, p.bonus_exchanged
	, b.bnc3
	, b.bnc3-p.bonus_exchanged	as diff
from lc_period z 
	,commissions.payout_02 p 
	,HIERARCHY ( 
	 	SOURCE ( select a.customer_id AS node_id, a.sponsor_id AS parent_id, a.*
	             from commissions.customer_history a, lc_period z
	             where a.period_id = z.period_id
	             and a.batch_id = z.batch_id
	             order by a.customer_id)
		Start where sponsor_id = 3) h
	,commissions.orabwt b
where p.customer_id = h.customer_id
and p.customer_id = b.dist_id
and p.period_id = z.period_id
and p.batch_id = z.batch_id
and ifnull(b.bnc3,0)<>p.bonus_exchanged;

/*
select *
   from commissions.payout_power3
   where period_id = 13
   and batch_id = 0
   and (lvl_id >= 1 
    or paid_lvl_id > 1)
   and sponsor_id = 2767228;
*/
