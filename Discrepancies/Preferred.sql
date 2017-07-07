select 
	  to_char(c.customer_id) 				as customer_id
	, c.customer_name
	, c.enroller_id
	, c.sponsor_id
	, c.type_id
	, c.status_id
	, c.hier_level
	, c.rank_id
	, c.country
	, c.currency
	, round(c.Earning_4,2)					as Earning_4
	, round(b.bnc12,2) 						as bnc12
	, round(b.bnc12,2)-round(c.Earning_4,2)	as diff
from commissions.orabwt b
	left outer join commissions.customer_history c
	on c.customer_id = b.dist_id
	and c.hier_level <> 0
	and c.period_id = 15
	and c.batch_id = 0
where (c.Earning_4 <> 0 or ifnull(b.bnc12,0) <> 0)
and abs(round(ifnull(b.bnc12,0),2)-round(c.Earning_4,2)) > 1
order by c.customer_id;


select p.*
from commissions.Earning_04 p, commissions.transaction t
where p.transaction_id = t.transaction_id
and p.customer_id = 148271;

select *
from commissions.transaction
where customer_id = 148271;

select t.*, c.*
from commissions.Earning_04 p, commissions.transaction t, commissions.customer_history c
where p.transaction_id = t.transaction_id
and t.period_id = 15
and t.period_id = c.period_id
and t.customer_id = c.customer_id 
and p.customer_id = 148271;

select *
from commissions.transaction
where period_id = 15
and customer_id in (
	select customer_id
	from commissions.customer_history
	where period_id = 15
	and sponsor_id = 148271
	and type_id in (2,3));
