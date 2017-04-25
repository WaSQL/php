-- Earnings Total ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
select p.to_currency, sum(p.bonus_exchanged) as bonus_exchanged_total
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 13
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = /*  Customer_id                                                                                      */ 355900) c
where p.transaction_customer_id = c.customer_id
and p.qual_flag = 1
and p.customer_id = /*  Customer_id                                                                                                           */ 355900
group by p.to_currency;

-- Paid to Detail ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
select p.customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id, p.from_currency, p.to_currency, p.percentage, p.exchange_rate
    , sum(p.pv) as pv, sum(p.cv) as cv, round(sum(p.bonus),2), round(sum(p.bonus_exchanged),2) as bonus_exchanged
from commissions.payout_unilevel p, commissions.customer_history c
where p.customer_id = c.customer_id
and c.period_id = p.period_id
and c.batch_id = p.batch_id
and c.period_id = 13
and c.batch_id = 0
and p.transaction_customer_id = /*  Customer_id                                                                                                */ 355900
group by p.customer_id,c.customer_name, p.lvl,p.lvl_paid,c.rank_id, p.from_currency, p.to_currency,p.percentage, p.exchange_rate
order by p.from_currency, p.lvl_paid,p.percentage;

-- Earnings Detail ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
select p.transaction_customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id,p.from_currency,p.to_currency, p.percentage, p.exchange_rate
     , sum(p.pv) as pv, sum(p.cv) as cv, round(sum(p.bonus),2) as bonus, round(sum(p.bonus_exchanged),2) as bonus_exchanged
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 13
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = /*  Customer_id                                                                                      */ 355900) c
where p.transaction_customer_id = c.customer_id
and p.customer_id = /*  Customer_id                                                                                                           */ 355900
group by c.hierarchy_rank, p.transaction_customer_id,c.customer_name,c.rank_id
       , p.lvl,p.lvl_paid,p.from_currency,p.to_currency,p.percentage,p.lvl_paid, p.exchange_rate
order by c.hierarchy_rank, p.lvl_paid,p.percentage,p.from_currency;

-- Customer Diff ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
with lc_Hana as (
	select c.hierarchy_rank, p.transaction_customer_id, c.customer_name, p.from_currency, p.to_currency, p.exchange_rate, p.percentage
	     , round(sum(p.pv),2) as pv, round(sum(p.cv),2) as cv, round(sum(p.bonus),2) as bonus, round(sum(p.bonus_exchanged),2) as bonus_exchanged
	from commissions.payout_unilevel p, HIERARCHY ( 
				 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
				             from commissions.customer_history
				             where period_id = 13
				             and batch_id = 0
				             order by customer_id)
		    		Start where sponsor_id = /*  Customer_id                                                                                   */ 355900) c
	where p.transaction_customer_id = c.customer_id
	and  p.customer_id = /*  Customer_id                                                                                                       */ 355900
	and p.qual_flag = 1
	group by c.hierarchy_rank, p.transaction_customer_id,c.customer_name, p.from_currency, p.to_currency, p.exchange_rate, p.percentage
)
, lc_Ora as (
	select b.sold_dist_id, sc.currency as from_currency, tc.currency as to_currency, b.conversion_rate
	     , round(sum(b.converted_bonus),2) as converted_bonus
	from commissions.orabtr b, commissions.currency_mapping sc, commissions.currency_mapping tc
	where b.source_country = sc.currency_legacy
	and b.target_country = tc.currency_legacy
	and b.bonus_type = 2
	and b.dist_id = /*  Customer_id                                                                                                             */ 355900
	group by b.sold_dist_id, sc.currency, tc.currency, b.conversion_rate
)
select to_char(ifnull(h.transaction_customer_id,o.sold_dist_id)) as customer_id, h.customer_name, h.from_currency, h.to_currency
     , h.exchange_rate, o.from_currency, o.to_currency, o.conversion_rate, h.pv, h.cv, h.percentage, ifnull(h.bonus,0) as bonus, ifnull(h.bonus_exchanged,0) as bonus_exchanged
     , o.converted_bonus, ifnull(h.bonus_exchanged,0)-o.converted_bonus as diff
from lc_Ora o
	left outer join lc_Hana h
		on h.transaction_customer_id = o.sold_dist_id
		and h.from_currency = o.from_currency
where abs(ifnull(h.bonus_exchanged,0)-o.converted_bonus) > .01
--where ifnull(h.bonus_exchanged,0) <> o.converted_bonus
order by ifnull(h.transaction_customer_id,o.sold_dist_id), abs(h.bonus_exchanged) desc;

-- Paid to Transaction Detail ---------------------------------------------------------------------------------------------------------------------------------------------------------------
/*
select p.transaction_id, p.customer_id, c.customer_name, c.country, p.lvl_paid, p.percentage, p.from_currency, p.to_currency
     , p.exchange_rate, p.pv, p.cv, p.bonus, p.bonus_exchanged
from commissions.payout_unilevel p, commissions.customer_history c
where p.customer_id = c.customer_id
and c.period_id = 12
and c.batch_id = 0
and p.transaction_customer_id = 140123
order by p.lvl_paid,p.percentage;
*/

-- Earnings Transaction Detail ---------------------------------------------------------------------------------------------------------------------------------------------------------------
/*
select p.transaction_id, p.transaction_customer_id, c.customer_name, p.lvl,p.lvl_paid,c.rank_id,p.from_currency
      ,p.to_currency, p.percentage, p.exchange_rate, p.pv, p.cv, p.bonus, p.bonus_exchanged
from commissions.payout_unilevel p, HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, customer_name, rank_id, sponsor_id
			             from commissions.customer_history
			             where period_id = 12
			             and batch_id = 0
			             order by customer_id)
	    		Start where sponsor_id = 140123) c
where p.transaction_customer_id = c.customer_id
and p.customer_id = 140123
order by c.hierarchy_rank, p.lvl_paid,p.percentage,p.from_currency;
*/
