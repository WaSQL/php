select 
	  to_char(c.customer_id) 				as customer_id
	, c.customer_name
	, c.type_id
	, c.status_id
	, c.rank_id
	, c.country
	, c.currency
	, round(c.Earning_12,2)					as Earning_12
	, round(b.bnc14,2) 						as bnc14
	, round(b.bnc14,2)-round(c.Earning_12,2)	as diff
from commissions.orabwt b
	left outer join commissions.customer_history c
	on c.customer_id = b.dist_id
where c.period_id = 15
and c.batch_id = 0
and (c.Earning_12 <> 0 or b.bnc14 <> 0)
and round(b.bnc14,2)-round(c.Earning_12,2) <> 0
order by c.customer_id;
