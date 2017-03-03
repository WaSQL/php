--truncate table orawvs;

delete from orawvs
where "$rowid$" in ( select ROW_ID from
(SELECT
  ROW_NUMBER() OVER (PARTITION BY dist_id, action_id) as RN,  "$rowid$" as ROW_ID,  dist_id,  action_id
  FROM orawvs
  ORDER BY 3,  2,  1)
where RN>1);

insert into req_waiver_history
select 
	 8 						as period_id
	,1						as batch_id
	,dist_id				as customer_id
	,1						as req_waiver_type_id
	,action_value			as value_1
from orawvs
where action_id = 'MINRANK';

truncate table orawvs;

--delete from req_waiver_history where period_id = 1

select period_id, count(*)
from req_waiver_history
group by period_id
order by period_id;