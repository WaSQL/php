call commissions.commission_history_run(9,1);

select lvl, count(*)
from commissions.payout_unilevel
group by lvl;

select lvl_paid, percentage, count(*)
from commissions.payout_unilevel
group by lvl_paid, percentage;

select p.*, c.rank_id, c.payout_1
from commissions.payout_unilevel p, commissions.customer_history c
where p.customer_id = c.customer_id
and c.period_id = p.period_id
and c.batch_id = p.batch_id
and p.lvl = 65;

select count(*)
from commissions.payout_unilevel;