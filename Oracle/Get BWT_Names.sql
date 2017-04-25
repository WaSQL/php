select 
	 b.dist_id
	,replace(replace(replace(nvl(roman_name_1,native_name_1),'"',''''),'/',''),'\','') as customer_name
	,to_char(d.COMM_STATUS_DATE,'dd-Mon-yyyy')	as comm_status_date
	,to_char(d.TERMINATE_DATE,'dd-Mon-yyyy')	as terminate_date
	,decode(upper(b.lf33),null,0,1)	           	as lf33
from bwtprv b, dst d
where b.dist_id = d.dist_id(+)
and b.dist_bus_ctr = 1
and b.dist_id < 2000000000
and b.commperiod = 201703;
