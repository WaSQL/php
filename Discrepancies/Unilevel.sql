with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
, lc_Hana as (
	select c.period_id, c.batch_id, c.customer_id, c.country, c.payout_1, 1/e.rate as rate
	from commissions.customer_history c, commissions.fn_exchange(13) e
	where c.currency = e.currency
)
,lc_Ora as (
	select dist_id, round(sum(converted_bonus),2) as converted_bonus
	from commissions.orabtr
	where bonus_type = 2
	group by dist_id
)
select to_char(o.dist_id) as customer_id, h.country, ifnull(h.payout_1,0) as payout_1
     , ifnull(o.converted_bonus,0) as converted_bonus, ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0) as diff
     , round(abs((ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0)) * h.rate),2) as usd
from lc_period z
	,lc_Ora o
	left outer join lc_Hana h
		on h.customer_id = o.dist_id
where h.period_id = z.period_id
and h.batch_id = z.batch_id
and round(abs((ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0)) * h.rate),2) >= 1
order by round(abs((ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0)) * h.rate),2) desc;
