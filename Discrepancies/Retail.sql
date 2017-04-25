select 
	  to_char(c.customer_id) 				as customer_id
	, c.customer_name
	, c.type_id
	, c.status_id
	, c.rank_id
	, round(c.payout_3,2)					as payout_3
	, round(b.bnc1,2) 						as bnc1
	, round(b.bnc1,2)-round(c.payout_3,2)	as diff
from orabwt b
	left outer join customer_history c
	on c.customer_id = b.dist_id
where c.period_id = 13
and c.batch_id = 0
and (c.payout_3 <> 0 or b.bnc1 <> 0)
and round(b.bnc1,2)-round(c.payout_3,2) <> 0
order by c.customer_id;

select t.*
from transaction t, payout_retail p
where t.transaction_id = p.transaction_id
and p.period_id = 13
and p.batch_id = 0
and p.sponsor_id = 38920;

select *
from transaction t, customer_history c
where c.sponsor_id = t.customer_id
and t.period_id = c.period_id
and c.period_id = 13
and c.batch_id = 0
and (t.transaction_type_id = 3 or value_5 <> 0)
and c.sponsor_id = 38920