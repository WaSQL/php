with l_Cust as (
	select a.*, a.vol_1 as a_pv, b.pv as b_pv, round(a.vol_13,2) as a_ov, round(b.ov,2) as b_ov 
	from commissions.customer_history a, commissions.orabwt b
	where a.customer_id = b.dist_id 
	and a.period_id = 13
)
, l_Qual as (
select * from commissions.customer_history_qual_leg where period_id = 13
)
, l_Waiver as (
select * from commissions.customer_history_flag where flag_type_id = 3 and period_id = 13
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
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 4)  as leg_4
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 5)  as leg_5
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 6)  as leg_6
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 7)  as leg_7
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 8)  as leg_8
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 9)  as leg_9
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 10) as leg_10
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 11) as leg_11
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 12) as leg_12
from l_Cust c
	left outer join l_Waiver w
	on c.customer_id = w.customer_id
where c.rank_id<>c.rank_high_type_id
order by c.rank_high_type_id desc;
