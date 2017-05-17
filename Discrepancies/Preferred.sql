select 
	  to_char(c.customer_id) 				as customer_id
	, c.customer_name
	, c.type_id
	, c.status_id
	, c.rank_id
	, c.country
	, c.currency
	, round(c.payout_4,2)					as payout_4
	, round(b.bnc12,2) 						as bnc12
	, round(b.bnc12,2)-round(c.payout_4,2)	as diff
from commissions.orabwt b
	left outer join commissions.customer_history c
	on c.customer_id = b.dist_id
where c.period_id = 13
and c.batch_id = 0
and (c.payout_4 <> 0 or ifnull(b.bnc12,0) <> 0)
and abs(round(ifnull(b.bnc12,0),2)-round(c.payout_4,2)) > 1
order by c.customer_id;


select p.*
from commissions.payout_04 p, commissions.transaction t
where p.transaction_id = t.transaction_id
and p.customer_id = 3779065;

select t.*, c.*
from commissions.payout_04 p, commissions.transaction t, commissions.customer_history c
where p.transaction_id = t.transaction_id
and t.period_id = 13
and t.period_id = c.period_id
and t.customer_id = c.customer_id 
and p.customer_id = 3779065
