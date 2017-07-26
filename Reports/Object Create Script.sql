select 
	 procedure_name	as object_name
	--,definition
	,is_valid
	,owner_name
	,create_time
from sys.procedures
where schema_name = upper('commissions')
and is_valid = 'FALSE'
union all
select
	 function_name	as object_name
	--,definition
	,is_valid
	,owner_name
	,create_time
from sys.functions
where schema_name = upper('commissions')
and is_valid = 'FALSE'
order by 1;

select 
	 procedure_name	as object_name
	--,definition
	,is_valid
	,owner_name
	,create_time
from sys.procedures
where schema_name = upper('commissions')
and lower(definition) like lower('%REQ_PREFERRED%')
union all
select 
	 function_name	as object_name
	--,definition
	,is_valid
	,owner_name
	,create_time
from sys.functions
where schema_name = upper('commissions')
and lower(definition) like lower('%REQ_PREFERRED%')
and lower(function_name) <> lower('REQ_PREFERRED')
order by 1;
