delete from commissions.orawvs
where "$rowid$" in ( select ROW_ID from
(SELECT
  ROW_NUMBER() OVER (PARTITION BY dist_id, action_id) as RN,  "$rowid$" as ROW_ID,  dist_id,  action_id
  FROM commissions.orawvs
  ORDER BY 3,  2,  1)
where RN>1);

delete
from commissions.customer_flag
where flag_type_id = 3;

insert into commissions.customer_flag
select 
	 commissions.customer_flag_id.nextval
	,dist_id
	,3
	,action_value
	,null
	,null
from commissions.orawvs
where action_id = 'MINRANK'
