select 
	201703	as commperiod
	,dist_id
	,sold_dist_id
	,bonus_type
	,target_country
	,source_country
	,"percent" as percent_amt
	,bonus
	,conversion_rate
	,converted_bonus
	,level
	,paid_level
	,amount_1
from admin.btr201703
where dist_bus_ctr = 1
and dist_id < 2000000000
