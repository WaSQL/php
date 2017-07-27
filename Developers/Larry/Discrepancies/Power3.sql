with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.period_id = 15)
select 
	  h.period_id || ' - ' || h.batch_id					as period
	, h.hier_level
	, to_char(h.customer_id)								as customer_id
	, h.country
	--, to_char(round(h.vol_1+h.vol_4,0))						as pv
	, to_char(round(h.vol_2,0)) 							as pv_lrp
	, to_char(round(h.vol_12,0)) 							as egv_lrp
	, to_char(round(h.vol_14,0))							as tv
	, p.paid_lvl_id											as struct
	, p.lvl_id
	, (select count(*)
	   from commissions.Earning_02
	   where period_id = z.period_id
	   and batch_id = 0
	   and (lvl_id >= 1 
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)						as po3_lvl_1
	, (select count(*)
	   from commissions.Earning_02
	   where period_id = z.period_id
	   and batch_id = 0
	   and (lvl_id >= 2 
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)						as po3_lvl_2
	, (select count(*)
	   from commissions.Earning_02
	   where period_id = z.period_id
	   and batch_id = 0
	   and (lvl_id >= 3
	    or paid_lvl_id > 1)
	   and sponsor_id = p.customer_id)						as po3_lvl_3
	--, p.to_currency
	--, p.exchange_rate
	--, p.bonus_exchanged
	, to_char(round(p.bonus,0))								as bonus
	, to_char(round(b.bnc3/p.exchange_rate,0))				as bnc3
	, to_char(round(p.bonus-(b.bnc3/p.exchange_rate),0))	as diff
from lc_period z 
	,commissions.Earning_02 p 
	,commissions.customer_history h
	/*
	,HIERARCHY ( 
	 	SOURCE ( select a.customer_id AS node_id, a.sponsor_id AS parent_id, a.*
	             from commissions.customer_history a, lc_period z
	             where a.period_id = z.period_id
	             and a.batch_id = z.batch_id
	             order by a.customer_id)
		Start where sponsor_id = 3) h
	*/
	,commissions.orabwt b
where p.customer_id = h.customer_id
and p.customer_id = b.dist_id
and h.period_id = z.period_id
and h.batch_id = z.batch_id
and p.period_id = z.period_id
and p.batch_id = z.batch_id
and ifnull(b.bnc3,0)<>p.bonus_exchanged
order by round(p.bonus-(b.bnc3/p.exchange_rate),0), h.customer_id;

/*
select *
   from commissions.Earning_02
   where period_id = 14
   and batch_id = 0
   and (lvl_id >= 1 
    or paid_lvl_id > 1)
   and sponsor_id = 3628617;
*/
