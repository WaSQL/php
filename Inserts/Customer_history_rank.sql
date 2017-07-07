/*truncate table commissions.customer_rank_history
insert into commissions.customer_rank_history
select
	 o.dist_id									as customer_id
	,o.rank_id
	,ifnull(p.period_id,0) 						as period_id
	,to_date(o.effective_date,'yyyy-mm-dd hh:mi:ss.ff3')		as effective_date
	,to_date(o.entry_date,'yyyy-mm-dd hh:mi:ss.ff3')			as entry_date
	,map(o.bonus_flag,'X',1,2)					as customer_rank_type_id
from commissions.orapdf o
	left outer join period p
	on p.beg_date <= to_date(map(o.pv_date,0,'200001',o.pv_date),'yyyymm')
	and p.end_date >= to_date(map(o.pv_date,0,'200001',o.pv_date),'yyyymm')
	and period_type_id = 1
--where dist_id = 1173
order by o.dist_id,o.rank_id;
*/

select *
from commissions.orapdf
where dist_id = 2172231
order by rank_id;

select *
from commissions.orabrd
where dist_id = 2172231
order by rank_id;

select *
from commissions.customer_rank_history
where customer_id = 2172231
order by rank_id;

select customer_id, rank_id
from commissions.customer_history
where period_id = 13
and batch_id = 0
and customer_id = 2172231;

select customer_id, enroller_id, rank_id
,(select max(rank_id)
           from commissions.customer_rank_history
           where period_id < c.period_id
           and customer_id = c.customer_id) as max_rank_id
  from commissions.customer_history c
  where period_id = 13
  and batch_id = 0
  and enroller_id = 1612
  --and customer_id = 27425
  and rank_id = 5
  /*and 5 > ifnull((select max(rank_id)
           from customer_history_rank
           where period_id < c.period_id
           and customer_id = c.customer_id),1)*/
  order by customer_id;
  
