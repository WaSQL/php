-- Earnings Total --------------------------------------------------------------------------------------
select p.to_currency, sum(p.bonus_exchanged) as bonus_exchanged_total
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 12
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = 1001) c
where p.transaction_customer_id = c.customer_id
and p.qual_flag = 1
and p.customer_id = 1001 --331056 --1990268
group by p.to_currency;

-- Paid to Detail --------------------------------------------------------------------------------------
select p.customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id, p.from_currency, p.to_currency, p.percentage, p.exchange_rate, sum(p.pv) as pv, sum(p.cv) as cv, round(sum(p.bonus),2), round(sum(p.bonus_exchanged),2) as bonus_exchanged
from commissions.payout_unilevel p, commissions.customer_history c
where p.customer_id = c.customer_id
and c.period_id = p.period_id
and c.batch_id = p.batch_id
and c.period_id = 12
and c.batch_id = 0
and p.transaction_customer_id = 1016
group by p.customer_id,c.customer_name, p.lvl,p.lvl_paid,c.rank_id, p.from_currency, p.to_currency,p.percentage, p.exchange_rate
order by p.lvl_paid,p.percentage;

-- Paid to Transaction Detail --------------------------------------------------------------------------------------
select p.transaction_id, p.customer_id, c.customer_name, c.country, p.lvl_paid, p.percentage, p.from_currency, p.to_currency, p.exchange_rate, p.pv, p.cv, p.bonus, p.bonus_exchanged
from commissions.payout_unilevel p, commissions.customer_history c
where p.customer_id = c.customer_id
and c.period_id = 12
and c.batch_id = 0
and p.transaction_customer_id = 1001
order by p.lvl_paid,p.percentage;

-- Earnings Detail --------------------------------------------------------------------------------------
select p.transaction_customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id,p.from_currency,p.to_currency, p.percentage, p.exchange_rate, sum(p.pv) as pv, sum(p.cv) as cv, round(sum(p.bonus),2) as bonus, round(sum(p.bonus_exchanged),2) as bonus_exchanged
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 12
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = 1001) c
where p.transaction_customer_id = c.customer_id
and p.customer_id = 1001
group by c.hierarchy_rank, p.transaction_customer_id,c.customer_name,c.rank_id, p.lvl,p.lvl_paid,p.from_currency,p.to_currency,p.percentage,p.lvl_paid, p.exchange_rate
order by c.hierarchy_rank, p.lvl_paid,p.percentage,p.from_currency;

-- Earnings Transaction Detail --------------------------------------------------------------------------------------
select p.transaction_id, p.transaction_customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id,p.from_currency,p.to_currency, p.percentage, p.exchange_rate, p.pv, p.cv, p.bonus, p.bonus_exchanged
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 12
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = 1001) c
where p.transaction_customer_id = c.customer_id
and p.customer_id = 1001
--and p.transaction_customer_id = 206859
order by c.hierarchy_rank, p.lvl_paid,p.percentage,p.from_currency;

-- Customer Diff --------------------------------------------------------------------------------------
with lc_Hana as (
	select c.hierarchy_rank, p.transaction_customer_id, c.customer_name, p.from_currency, p.to_currency, p.exchange_rate, p.percentage, round(sum(p.pv),2) as pv, round(sum(p.cv),2) as cv, round(sum(p.bonus),2) as bonus, round(sum(p.bonus_exchanged),2) as bonus_exchanged
	from commissions.payout_unilevel p, HIERARCHY ( 
				 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
				             from commissions.customer_history
				             where period_id = 12
				             and batch_id = 0
				             order by customer_id)
		    		Start where sponsor_id = 1001) c
	where p.transaction_customer_id = c.customer_id
	and p.customer_id = 1001
	and p.qual_flag = 1
	group by c.hierarchy_rank, p.transaction_customer_id,c.customer_name, p.from_currency, p.to_currency, p.exchange_rate, p.percentage
)
, lc_Ora as (
	select sold_dist_id, source_country, target_country, conversion_rate, round(sum(converted_bonus),2) as converted_bonus
	from commissions.orabtr
	where dist_id = 1001
	group by sold_dist_id, source_country, target_country, conversion_rate
)
select ifnull(h.transaction_customer_id,o.sold_dist_id) as customer_id, h.customer_name, h.from_currency, h.to_currency, h.exchange_rate, o.source_country, o.target_country, o.conversion_rate, h.pv, h.cv, h.percentage, h.bonus, h.bonus_exchanged, o.converted_bonus, h.bonus_exchanged-o.converted_bonus as diff
from lc_Ora o
	left outer join lc_Hana h
		on h.transaction_customer_id = o.sold_dist_id
where abs(ifnull(h.bonus_exchanged,0)-o.converted_bonus) > .01
--and h.bonus_exchanged <> o.converted_bonus
order by ifnull(h.transaction_customer_id,o.sold_dist_id), abs(h.bonus_exchanged) desc;

-- Customers with diffs --------------------------------------------------------------------------------------
with lc_Hana as (
	select customer_id, country, payout_1
	from commissions.customer_history
	where period_id = 12
	and batch_id = 0
)
,lc_Ora as (
	select dist_id, round(sum(converted_bonus),2) as converted_bonus
	from commissions.orabtr
	group by dist_id
)
select to_char(o.dist_id) as customer_id, h.country, ifnull(h.payout_1,0) as payout_1,0, ifnull(o.converted_bonus,0) as converted_bonus, ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0) as diff
from lc_Ora o
	left outer join lc_Hana h
		on h.customer_id = o.dist_id
where abs(ifnull(h.payout_1,0)-ifnull(o.converted_bonus,0)) > 1
order by abs(h.payout_1-o.converted_bonus) desc;
