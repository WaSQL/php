select *
from transaction_log
where customer_id = 3927067
--and period_id = 8
;

select *
from customer_history
where customer_id in (3927067)
--and period_id = 8
;

Select 
	      c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction_log t, customer_history c
	Where t.customer_id = c.customer_id
	And t.period_id = c.period_id
	And c.period_id = 8
   	and c.batch_id = 1
   	and ifnull(t.transaction_type_id,4) <> 0
   	and c.customer_id = 3927067
Group By c.period_id, c.batch_id, c.customer_id
having (Sum(ifnull(t.value_2,0)) != 0
	or  Sum(ifnull(t.value_4,0)) != 0);