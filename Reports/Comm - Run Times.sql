-- Commission Run Times ----------------------------------------------------------------------------------------------------
select
	   a.period_id || ' - ' || a.batch_id 											as Period_id
	  ,to_char(a.beg_date,'yyyy Mon')												as Period
	  ,(select count(*) 
	    from commissions.customer_history 
	    where period_id = a.period_id 
	    and batch_id = a.batch_id 
	    and type_id = 1
	    and sponsor_id <> 4)
	  || '/' ||(select count(*)
	    from commissions.customer_history 
	    where period_id = a.period_id 
	    and batch_id = a.batch_id 
	    and type_id = 1
	    and rank_id<>rank_high_type_id
	    and sponsor_id <> 4)														as Cust_Count
	   ,floor(Clear/60) || ':' || lpad(mod(Clear,60),2,0)							as Clear
	   ,floor(Run_Sec/60) || ':' || lpad(mod(Run_Sec,60),2,0)						as Run_Time
	   ,floor(PV/60) || ':' || lpad(mod(PV,60),2,0)									as PV
	   ,floor(PV_Retail/60) || ':' || lpad(mod(PV_Retail,60),2,0)					as PV_Retail
	   ,floor(PV_LRP/60) || ':' || lpad(mod(PV_LRP,60),2,0)							as PV_LRP
	   ,floor(PV_FS/60) || ':' || lpad(mod(PV_FS,60),2,0)							as PV_FS
	   ,floor(EGV/60) || ':' || lpad(mod(EGV,60),2,0)								as EGV
	   ,floor(TV/60) || ':' || lpad(mod(TV,60),2,0)									as TV
	   ,floor(TW_CV/60) || ':' || lpad(mod(TW_CV,60),2,0)							as TW_CV
	   ,floor(OV/60) || ':' || lpad(mod(OV,60),2,0)									as OV
	   ,floor(Rank/60) || ':' || lpad(mod(Rank,60),2,0)								as Rank
	   ,floor(Payout_1/60) || ':' || lpad(mod(Payout_1,60),2,0)						as Payout_1
	   ,floor(Payout_2/60) || ':' || lpad(mod(Payout_2,60),2,0)						as Payout_2
	   ,floor(Payout_3/60) || ':' || lpad(mod(Payout_3,60),2,0)						as Payout_3
	   ,floor(Payout_4/60) || ':' || lpad(mod(Payout_4,60),2,0)						as Payout_4
	   ,floor(Payout_5/60) || ':' || lpad(mod(Payout_5,60),2,0)						as Payout_5
	   ,floor(Payout_6/60) || ':' || lpad(mod(Payout_6,60),2,0)						as Payout_6
	   ,floor(Payout_7/60) || ':' || lpad(mod(Payout_7,60),2,0)						as Payout_7
	   ,floor(Payout_8/60) || ':' || lpad(mod(Payout_8,60),2,0)						as Payout_8
	   ,floor(Payout_9/60) || ':' || lpad(mod(Payout_9,60),2,0)						as Payout_9
	   ,floor(Payout_10/60) || ':' || lpad(mod(Payout_10,60),2,0)					as Payout_10
	   ,floor(Payout_11/60) || ':' || lpad(mod(Payout_11,60),2,0)					as Payout_11
from (
	select 
		   b.period_id
		  ,b.batch_id
		  ,p.beg_date
		  ,b.beg_date_run
	      ,seconds_between(
	       to_seconddate(b.beg_date_clear, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_clear,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Clear
	      ,seconds_between(
	       to_seconddate(b.beg_date_run, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_run,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Run_Sec
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as PV
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_retail, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_retail,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as PV_Retail
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_lrp, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_lrp,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as PV_LRP
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_fs, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_fs,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as PV_FS
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_egv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_egv,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as EGV
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_tv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_tv,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as TV
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_tw_cv, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_tw_cv,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as TW_CV
	      ,seconds_between(
	       to_seconddate(b.beg_date_volume_org, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_volume_org,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as OV
	      ,seconds_between(
	       to_seconddate(b.beg_date_rank, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_rank,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Rank
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_1, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_1,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_1
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_2, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_2,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_2
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_3, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_3,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_3
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_4, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_4,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_4
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_5, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_5,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_5
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_6, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_6,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_6
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_7, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_7,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_7
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_8, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_8,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_8
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_9, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_9,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_9
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_10, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_10,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_10
	      ,seconds_between(
	       to_seconddate(b.beg_date_payout_11, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_payout_11,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Payout_11
	from commissions.period p, commissions.period_batch b
	where p.period_id = b.period_id
	and p.period_type_id = 1
	and b.viewable = 1) a
order by a.period_id desc, a.batch_id desc, a.beg_date;

-- Ranks ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Ranks
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

-- Unilevel Payout ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Payout Unilevel
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
from commissions.payout_01 u, lc_period z
where u.period_id = z.period_id
and u.batch_id = z.batch_id
and u.qual_flag = 1
group by u.period_id, u.batch_id, u.lvl_paid
order by u.period_id, u.batch_id, u.lvl_paid;

-- Power of 3 Payout ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Payout Power of 3
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
from commissions.payout_02 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
group by p.period_id, p.batch_id, p.lvl_id, p.paid_lvl_id
order by p.period_id, p.batch_id, p.paid_lvl_id, p.lvl_id;

-- Retail Payout ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Payout Retail
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select
	  p.period_id || ' - ' || p.batch_id 	as period
	, p.qual_flag							as qual_flag
	, p.to_currency							as currency
	, count(*)								as count
from commissions.payout_03 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
and p.qual_flag = 1
group by p.period_id, p.batch_id, p.qual_flag, p.to_currency;

-- Preferred Payout ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Payout Preferred
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
select
	  p.period_id || ' - ' || p.batch_id 	as period
	, p.qual_flag							as qual_flag
	, p.to_currency							as currency
	, count(*)								as count
from commissions.payout_04 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
and p.qual_flag = 1
group by p.period_id, p.batch_id, p.qual_flag, p.to_currency;
