select 
    	  c.period_id
    	, c.batch_id
    	, c.customer_id
    	, c.type_id
    	, c.status_id
    	, r.value_1			as waiver_rank_id
    	, c.rank_id
		,r.value_1-c.rank_id as diff
	from commissions.req_waiver_history r, commissions.customer_history c
	where r.customer_id = c.customer_id
	and r.period_id = 12
	and r.batch_id = 0
	and c.period_id = r.period_id
	and r.batch_id = c.batch_id
	and r.req_waiver_type_id in (1, 2)
	--And c.type_id = 1
	--and c.status_id in (1, 4)
	and c.customer_id = 556803
	--and r.value_1-c.rank_id > 0
	order by r.value_1 desc, c.customer_id;
	
select *
from commissions.req_qual_leg_history
where period_id = 12
order by version_id, rank_id;
	
with l_Cust as (
	select a.*, a.vol_1 as a_vol_1, b.vol_1 as b_vol_1, round(a.vol_13,2) as a_vol_13, round(b.vol_12,2) as b_vol_13 from commissions.customer_history a, commissions.orabwt b where a.customer_id = b.dist_id and a.period_id = 12
)
, l_Qual as (
select * from commissions.customer_history_qual_leg where period_id = 12
)
, l_Waiver as (
select * from commissions.req_waiver_history where period_id = 12
)
select 
	  to_varchar(c.customer_id) as customer_id
	, c.type_id
	, c.status_id
	, c.country
	, c.rank_id
	, c.rank_high_type_id
	, c.rank_id-c.rank_high_type_id as diff
	, ifnull(w.value_1,0) as MinRank
	, c.a_vol_1, c.a_vol_1-c.b_vol_1 as diff_1, c.vol_10, c.a_vol_13, c.a_vol_13-c.b_vol_13 as diff_13
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
