select 
	 procedure_name	as object_name
	,definition
	,is_valid
	,owner_name
	,create_time
from sys.procedures
where schema_name = upper('commissions')
and is_valid = 'FALSE'
union all
select
	 function_name	as object_name
	,definition
	,is_valid
	,owner_name
	,create_time
from sys.functions
where schema_name = upper('commissions')
and is_valid = 'FALSE'
order by 1;

select * --definition
from sys.procedures
where schema_name = upper('commissions')
and lower(definition) like lower('%fn_Validate_Enroller_Org%')
--and owner_name = 'LCARDON'
order by procedure_name;

select *
from sys.functions
where schema_name = upper('commissions')
and lower(definition) like lower('%fn_Validate_Enroller_Org%')
and lower(function_name) <> lower('fn_Validate_Enroller_Org')
--and owner_name = 'LCARDON'
order by function_name;
