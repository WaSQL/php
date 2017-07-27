truncate table customer_history_qa;

insert into customer_history_qa
select h.*
from customer_history h, (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null) z
where h.period_id = z.period_id
and h.batch_id = z.batch_id;