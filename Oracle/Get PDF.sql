select 
	dist_id
	,pv_date
	,effective_date
	,rank_index as rank_id
	,entry_date
	,bonus_flag
from admin.pdfdb
where dist_id <> 999999999999
