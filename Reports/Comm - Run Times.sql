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
	   ,floor(Level_id/60) || ':' || lpad(mod(Level_id,60),2,0)						as Level_id
	   ,floor(PV/60) || ':' || lpad(mod(PV,60),2,0)									as PV
	   ,floor(PV_Retail/60) || ':' || lpad(mod(PV_Retail,60),2,0)					as PV_Retail
	   ,floor(PV_LRP/60) || ':' || lpad(mod(PV_LRP,60),2,0)							as PV_LRP
	   ,floor(PV_FS/60) || ':' || lpad(mod(PV_FS,60),2,0)							as PV_FS
	   ,floor(EGV/60) || ':' || lpad(mod(EGV,60),2,0)								as EGV
	   ,floor(TV/60) || ':' || lpad(mod(TV,60),2,0)									as TV
	   ,floor(TW_CV/60) || ':' || lpad(mod(TW_CV,60),2,0)							as TW_CV
	   ,floor(OV/60) || ':' || lpad(mod(OV,60),2,0)									as OV
	   ,floor(Rank/60) || ':' || lpad(mod(Rank,60),2,0)								as Rank
	   ,floor(Earning_1/60) || ':' || lpad(mod(Earning_1,60),2,0)					as Earning_1
	   ,floor(Earning_2/60) || ':' || lpad(mod(Earning_2,60),2,0)					as Earning_2
	   ,floor(Earning_3/60) || ':' || lpad(mod(Earning_3,60),2,0)					as Earning_3
	   ,floor(Earning_4/60) || ':' || lpad(mod(Earning_4,60),2,0)					as Earning_4
	   ,floor(Earning_5/60) || ':' || lpad(mod(Earning_5,60),2,0)					as Earning_5
	   ,floor(Earning_6/60) || ':' || lpad(mod(Earning_6,60),2,0)					as Earning_6
	   ,floor(Earning_7/60) || ':' || lpad(mod(Earning_7,60),2,0)					as Earning_7
	   ,floor(Earning_8/60) || ':' || lpad(mod(Earning_8,60),2,0)					as Earning_8
	   ,floor(Earning_9/60) || ':' || lpad(mod(Earning_9,60),2,0)					as Earning_9
	   ,floor(Earning_10/60) || ':' || lpad(mod(Earning_10,60),2,0)					as Earning_10
	   ,floor(Earning_11/60) || ':' || lpad(mod(Earning_11,60),2,0)					as Earning_11
	   ,floor(Earning_12/60) || ':' || lpad(mod(Earning_12,60),2,0)					as Earning_12
	   ,floor(Earning_13/60) || ':' || lpad(mod(Earning_13,60),2,0)					as Earning_13
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
	       to_seconddate(b.beg_date_level, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_level,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 				as Level_id
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
	       to_seconddate(b.beg_date_Earning_1, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_1,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_1
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_2, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_2,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_2
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_3, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_3,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_3
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_4, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_4,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_4
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_5, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_5,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_5
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_6, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_6,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_6
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_7, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_7,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_7
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_8, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_8,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_8
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_9, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_9,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 			as Earning_9
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_10, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_10,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Earning_10
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_11, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_11,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Earning_11
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_12, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_12,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Earning_12
	      ,seconds_between(
	       to_seconddate(b.beg_date_Earning_13, 'yyyy-mm-dd hh24:mi:ss.ff7'),
	       to_seconddate(ifnull(b.end_date_Earning_13,current_timestamp), 'yyyy-mm-dd hh24:mi:ss.ff7')) 		as Earning_13
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

-- Unilevel Earning ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Earning Unilevel
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
from commissions.Earning_01 u, lc_period z
where u.period_id = z.period_id
and u.batch_id = z.batch_id
and u.qual_flag = 1
group by u.period_id, u.batch_id, u.lvl_paid
order by u.period_id, u.batch_id, u.lvl_paid;

-- Power of 3 Earning ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Earning Power of 3
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
from commissions.Earning_02 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
group by p.period_id, p.batch_id, p.lvl_id, p.paid_lvl_id
order by p.period_id, p.batch_id, p.paid_lvl_id, p.lvl_id;

-- Retail Earning ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Earning Retail
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
from commissions.Earning_03 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
and p.qual_flag = 1
group by p.period_id, p.batch_id, p.qual_flag, p.to_currency;

-- Preferred Earning ----------------------------------------------------------------------------------------------------
with lc_period as ( -- Earning Preferred
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
from commissions.Earning_04 p, lc_period z
where p.period_id = z.period_id
and p.batch_id = z.batch_id
and p.qual_flag = 1
group by p.period_id, p.batch_id, p.qual_flag, p.to_currency;
