select 
	 dist_id
	,dist_bus_ctr
	,pv_date
	,transaction_date
	,transaction_number
	,country_code
	,price_1
	,price_2
	,price_3
	,price_4
	,price_5
	,price_6
	,price_7
	,price_8
	,price_9
	,order_type
	,order_source
	,dist_status
	,entry_date
	,record_number
	,rma_record_number
	,null				as modifiy_date
from admin.bth1_file_1
where dist_bus_ctr = 1
and pv_date >= 201604
and pv_date <= 201701
union
select 
	 dist_id
	,dist_bus_ctr
	,pv_date
	,transaction_date
	,transaction_number
	,country_code
	,price_1
	,price_2
	,price_3
	,price_4
	,price_5
	,price_6
	,price_7
	,price_8
	,price_9
	,order_type
	,order_source
	,dist_status
	,entry_date
	,record_number
	,rma_record_number
	,null				as modifiy_date
from admin.bth2_file_1
where dist_bus_ctr = 1
and pv_date >= 201604
and pv_date <= 201701
union
select 
	 dist_id
	,dist_bus_ctr
	,pv_date
	,transaction_date
	,transaction_number
	,country_code
	,price_1
	,price_2
	,price_3
	,price_4
	,price_5
	,price_6
	,price_7
	,price_8
	,price_9
	,order_type
	,order_source
	,dist_status
	,entry_date
	,record_number
	,rma_record_number
	,null				as modifiy_date
from admin.bth3_file_1
where dist_bus_ctr = 1
and pv_date >= 201604
and pv_date <= 201701
union
select 
	 dist_id
	,dist_bus_ctr
	,pv_date
	,transaction_date
	,transaction_number
	,country_code
	,price_1
	,price_2
	,price_3
	,price_4
	,price_5
	,price_6
	,price_7
	,price_8
	,price_9
	,order_type
	,order_source
	,dist_status
	,entry_date
	,record_number
	,rma_record_number
	,null				as modifiy_date
from admin.bth4_file_1
where dist_bus_ctr = 1
and pv_date >= 201604
and pv_date <= 201701