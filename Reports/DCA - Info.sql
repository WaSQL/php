select * 
from transaction
where customer_id = 2767242
and period_id = 13;

select * 
from orabth
where dist_id = 2767242;

select *
from customer_history_flag
where customer_id = 2767242;

select *
from customer_history
where customer_id = 2767242;

select *
from fn_exchange(13);