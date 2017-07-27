select dist_id, action_id, action_field, action_value
from admin.cqtdb
where period_type = 'PB'
and begin_pv_date <= 201705
and end_pv_date >= 201705
and retired_f != 'T'