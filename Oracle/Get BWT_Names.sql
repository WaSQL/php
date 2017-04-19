select 
	 b.dist_id
	,replace(replace(replace(nvl(roman_name_1,native_name_1),'"',''''),'/',''),'\','') as customer_name
	--,b.sponsor_dist_id	as sponsor_id
	--,b.enroller_dist_id	as enroller_id
	--,b.status
	--,b.country_code		as country
	--,b.paid_rank		as rank_id
	--,b.end_rank			as high_rank_id
	--,to_char(b.entry_date,'dd-Mon-yyyy')        as entry_date
	,to_char(d.COMM_STATUS_DATE,'dd-Mon-yyyy')	as comm_status_date
	,to_char(d.TERMINATE_DATE,'dd-Mon-yyyy')	as terminate_date
	--,b.vol1				as pv
	--,b.vol4				as cv
	--,b.vol3				as ov
	--,decode(b.qflg2,null,0,1)	                as qflg2
from bwtprv b, dst d
where b.dist_id = d.dist_id(+)
and b.dist_bus_ctr = 1
and b.dist_id < 2000000000
and b.commperiod = 201702;
