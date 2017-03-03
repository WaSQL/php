select 
	 dist_id
	,sponsor_dist_id	as sponsor_id
	,enroller_dist_id	as enroller_id
	,status
	,country_code		as country
	,paid_rank			as rank_id
	,end_rank			as high_rank_id
	,to_char(entry_date,'dd-Mon-yyyy') as entry_date
	,null				as comm_status_date
	,null				as terminated_date
	,vol1				as vol_1
	,vol3				as vol_12
from admin.bwt201609
where dist_bus_ctr = 1;

select 
	 b.dist_id
	,b.sponsor_dist_id	as sponsor_id
	,b.enroller_dist_id	as enroller_id
	,b.status
	,b.country_code		as country
	,b.paid_rank			as rank_id
	,b.end_rank			as high_rank_id
	,b.entry_date       as entry_date
	,to_char(d.COMM_STATUS_DATE,'dd-Mon-yyyy')	as comm_status_date
	,to_char(d.TERMINATE_DATE,'dd-Mon-yyyy')				as terminated_date
	,b.vol1				as vol_1
	,b.vol3				as vol_12
	,decode(b.qflg2,null,0,1)	as qflg2
from bwtprv b, dst d
where b.dist_id = d.dist_id(+)
and b.dist_bus_ctr = 1
and b.commperiod = 201604; 