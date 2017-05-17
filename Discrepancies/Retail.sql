select 
	  to_char(c.customer_id) 				as customer_id
	, c.customer_name
	, c.type_id
	, c.status_id
	, c.rank_id
	, c.country
	, c.currency
	, round(c.payout_3,2)					as payout_3
	, round(b.bnc1,2) 						as bnc1
	, round(b.bnc1,2)-round(c.payout_3,2)	as diff
from commissions.orabwt b
	left outer join commissions.customer_history c
	on c.customer_id = b.dist_id
where c.period_id = 13
and c.batch_id = 0
and (c.payout_3 <> 0 or b.bnc1 <> 0)
and round(b.bnc1,2)-round(c.payout_3,2) <> 0
order by c.customer_id;

/*
select t.*
from commissions.transaction t
where t.period_id = 13
and t.customer_id = 4306357;

select c.*
from commissions.customer_history c
where c.period_id = 13
and c.batch_id = 0
and (c.customer_id = 4306357 or c.sponsor_id = 4306357);

select *
from commissions.transaction
where transaction_id = 19838340 or transaction_ref_id = 19838340
*/