select 
	   b.period_id
	  ,b.batch_id 
	  ,(select count(*) 
	    from Commissions.customer_history 
	    where period_id = b.period_id 
	    and batch_id = b.batch_id 
	    and type_id = 1)															as Cust_Count
	  ,(select count(*)
	    from Commissions.customer_history 
	    where period_id = b.period_id 
	    and batch_id = b.batch_id 
	    and type_id = 1
	    and rank_id<>rank_high_type_id)															as Rank_Diff
	  ,p.beg_date
	  ,b.beg_date_run
      --,b.end_date_run
      ,seconds_between(
       to_seconddate(b.beg_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7')) 					as Run_Sec
      ,round(seconds_between(
       to_seconddate(b.beg_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'))/60,2) 			as Run_Min
      ,seconds_between(
       to_seconddate(b.beg_date_volume, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume, 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Volume_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_retail, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_retail, 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Volume_Retail_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_fs, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_fs, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Volume_FS_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_egv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_egv, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Volume_EGV_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_org, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_org, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Volume_Org_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_rank, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_rank, 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Rank_Sec
from Commissions.period p, Commissions.period_batch b
where p.period_id = b.period_id
and b.beg_date_run is not null
order by b.period_id desc, b.batch_id desc, p.beg_date;

with l_Cust as (
	select * from Commissions.customer_history where period_id <> 0 and period_id in (select period_id from Commissions.period_batch where beg_date_run is not null and end_date_run is null)
)
select period_id, rank_id, rank_qual, count(*) as count
from l_Cust
where type_id = 1
group by period_id, rank_id, rank_qual
order by period_id, rank_id, rank_qual;

/*
with l_Cust as (
	select * from customer_history where period_id <> 0 and period_id in (select period_id from period_batch where beg_date_run is not null and end_date_run is null)
)
select customer_id, period_id, rank_id, rank_high_id, rank_id-rank_high_id as diff
from l_Cust
where rank_id <> rank_high_id
order by rank_high_id desc, customer_id, period_id;
*/

with l_Cust as (
	select * from Commissions.customer_history where period_id <> 0 and period_id in (select period_id from Commissions.period_batch where beg_date_run is not null and end_date_run is null)
)
select
	   c.period_id
	 , min(t.hierarchy_level)	as Level_id
	 , max(t.hierarchy_level)	as Max_Level_id
from l_Cust c
	, HIERARCHY ( 
	 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id
	             from l_Cust)
		Start where sponsor_id = 3
		cache force) t
where c.customer_id = t.node_id
and c.rank_qual <> 0
group by c.period_id;
