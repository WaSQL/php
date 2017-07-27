with lc_period as (
	select a.period_id, b.batch_id
	from commissions.period a, commissions.period_batch b
	where a.period_id = b.period_id
	and b.viewable = 1
	and a.period_type_id = 1
	and a.closed_date is not null
	and a.final_date is null)
, l_Cust as (
	select a.*, a.vol_1 as a_pv, b.pv as b_pv, round(a.vol_13,2) as a_ov, round(b.ov,2) as b_ov 
	from commissions.orabwt b
		left outer join commissions.customer_history a
		on a.customer_id = b.dist_id
)
, l_Qual as (
	select * 
	from commissions.customer_history_qual_leg
)
, l_Waiver as (
	select * 
	from commissions.customer_history_flag 
	where flag_type_id = 3
)
select 
	  to_varchar(c.customer_id) as customer_id
	, c.type_id
	, c.status_id
	, c.country
	, c.rank_id
	, c.rank_high_type_id
	, c.rank_id-c.rank_high_type_id as diff
	, ifnull(w.flag_value,0) as MinRank
	, c.a_pv, c.a_pv-c.b_pv as pv_diff, c.vol_10, c.a_ov, c.a_ov-c.b_ov as ov_diff
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 4)  as leg_4
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 5)  as leg_5
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 6)  as leg_6
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 7)  as leg_7
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 8)  as leg_8
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 9)  as leg_9
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 10) as leg_10
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 11) as leg_11
	, (select count(*) from l_Qual q where q.period_id = z.period_id and q.sponsor_id = c.customer_id and q.leg_rank_id = 12) as leg_12
from lc_period z
	,l_Cust c
	left outer join l_Waiver w
	on c.customer_id = w.customer_id
	and w.period_id = c.period_id
	and w.batch_id = c.batch_id
where c.period_id = z.period_id
and c.batch_id = z.batch_id
and c.rank_id<>c.rank_high_type_id
order by c.rank_high_type_id desc;
