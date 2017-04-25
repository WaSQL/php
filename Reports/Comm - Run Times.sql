select 
	   b.period_id || ' - ' || b.batch_id 											as Period_id
	  ,to_char(p.beg_date,'yyyy Mon')												as Period
	  ,(select count(*) 
	    from commissions.customer_history 
	    where period_id = b.period_id 
	    and batch_id = b.batch_id 
	    and type_id = 1
	    and sponsor_id <> 4)
	  || '/' ||(select count(*)
	    from commissions.customer_history 
	    where period_id = b.period_id 
	    and batch_id = b.batch_id 
	    and type_id = 1
	    and rank_id<>rank_high_type_id
	    and sponsor_id <> 4)														as Cust_Count
	  ,to_char(b.beg_date_run,'dd-Mon-yyyy hh:mi')									as Run_date
      --,b.end_date_run
      ,seconds_between(
       to_seconddate(b.beg_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7')) 					as Run_Sec
      ,round(seconds_between(
       to_seconddate(b.beg_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'))/60,2) 			as Run_Min
      ,seconds_between(
       to_seconddate(b.beg_date_volume, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume, 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Vol_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_lrp, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_lrp, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Vol_Lrp_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_retail, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_retail, 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Vol_Retail_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_fs, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_fs, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Vol_FS_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_egv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_egv, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Vol_EGV_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_tv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_tv, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Vol_TV_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_volume_org, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_volume_org, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Vol_Org_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_rank, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_rank, 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Rank_Sec
      ,seconds_between(
       to_seconddate(b.beg_date_payout_1, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_payout_1, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_1
      ,seconds_between(
       to_seconddate(b.beg_date_payout_2, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_payout_2, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_2
      ,seconds_between(
       to_seconddate(b.beg_date_payout_3, 'yyyy-mm-dd hh24:mi:ss.ff7'),
       to_seconddate(b.end_date_payout_3, 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_3
from commissions.period p, commissions.period_batch b
where p.period_id = b.period_id
and p.period_type_id = 1
and b.viewable = 1
order by b.period_id desc, b.batch_id desc, p.beg_date;

with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
, l_Cust as (
	select * 
	from commissions.customer_history
)
select 
	  c.period_id || ' - ' || c.batch_id as period
	, c.rank_id
	, c.rank_qual
	, count(*) as count
from lc_period z, l_Cust c
where c.period_id = z.period_id
and c.batch_id = z.batch_id
and c.type_id = 1
group by c.period_id, c.batch_id, c.rank_id, c.rank_qual
order by c.period_id, c.batch_id, c.rank_id, c.rank_qual;

with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select 
	  u.period_id || ' - ' || u.batch_id as period
	, u.lvl_paid
	, count(*)
from commissions.payout_unilevel u, lc_period z
where u.period_id = z.period_id
and u.batch_id = z.batch_id
group by u.period_id, u.batch_id, u.lvl_paid
order by u.period_id, u.batch_id, u.lvl_paid;

with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select 
	  p.period_id || ' - ' || p.batch_id 	as period
	, p.paid_lvl_id							as paid_lvl_id
	, p.lvl_id
	, count(*)
from commissions.payout_power3 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
group by p.period_id, p.batch_id, p.lvl_id, p.paid_lvl_id
order by p.period_id, p.batch_id, p.paid_lvl_id, p.lvl_id;
