update transaction_log
set transaction_type_id = 0
where transaction_log_id = 7526601;

--update transaction_log
--set period_id = 9
--where transaction_log_id = 7526601;

select to_varchar(t.transaction_log_id) as transaction_log_id,b.transaction_number,t.period_id,t.transaction_type_id,t.value_2
from transaction_log t
	left outer join orabth b
		on t.source_key_id = b.record_number
where t.customer_id = 169266
and t.period_id = 9;