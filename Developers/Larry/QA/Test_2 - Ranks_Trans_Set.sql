select *
from commissions.customer
where rank_high_type_id = 4;

---------------------------------------------------------------------------------------------------------

select *
from commissions.req_qual_leg
order by version_id, rank_id;

---------------------------------------------------------------------------------------------------------

select count(*)
from commissions.transaction_log t,
	HIERARCHY ( 
	 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*
	             from commissions.customer a
	           )
		Start where customer_id in (1863229)) c --779431)) c --================
where t.customer_id = c.customer_id
and t.period_id = 9
and t.transaction_type_id <> 0;

---------------------------------------------------------------------------------------------------------

select z.*, rank_id-rank_high_type_id as diff
from (
select to_varchar(c.customer_id) as customer_id, c.status_id, case c.rank_id when 0 then 1 else c.rank_id end as rank_id, c.rank_high_type_id, c.country, c.hierarchy_level
from HIERARCHY ( 
	 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*
	             from commissions.customer a
	           )
		Start where customer_id in (779431)) c) z --779431)) z --================
where rank_id-rank_high_type_id <> 0
order by hierarchy_level;

----------------------------------------------------------------------------------------------------------

with l_Cust as (
	select a.*, ifnull(w.value_1,0) as value_1
	from commissions.customer a
	left outer join commissions.req_waiver w
		on a.customer_id = w.customer_id
	where a.customer_id in (442624,607513,779431) --================
)
, l_Qual as (
	select customer_id,sponsor_id, max(leg_rank_id) as leg_rank_id
	 from commissions.customer_qual_leg
	 where sponsor_id = leg_enroller_id
	 group by customer_id,sponsor_id
)
, l_Cust2 as (
	select a.*, ifnull(w.value_1,0) as value_1
	from commissions.customer_history a
	left outer join commissions.req_waiver w
		on a.customer_id = w.customer_id
	where a.customer_id in (442624,607513,779431) --================
	and a.period_id = 9
)
, l_Qual2 as (
	select customer_id,sponsor_id, max(leg_rank_id) as leg_rank_id
	 from commissions.customer_history_qual_leg
	 where sponsor_id = leg_enroller_id
	 and period_id = 9
	 group by customer_id,sponsor_id
)
select 
	  1 as rec_type
	, to_varchar(c.customer_id) as customer_id
	, to_varchar(c.sponsor_id) as sponsor_id
	, to_varchar(c.enroller_id) as enroller_id
	, c.type_id
	, c.status_id
	, c.country
	, c.rank_id
	, c.rank_high_type_id as DT_Rank_id
	, c.rank_id-c.rank_high_type_id as diff
	, c.value_1 as MinRank
	, c.vol_1, c.vol_4, c.vol_10, round(c.vol_12,2) as vol_12
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 0)  as leg_0
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 1)  as leg_1
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 2)  as leg_2
	, (select count(*) from l_Qual where sponsor_id = c.customer_id and leg_rank_id = 3)  as leg_3
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
union all
select 
	  2 as rec_type
	, to_varchar(c.customer_id) as customer_id
	, to_varchar(c.sponsor_id) as sponsor_id
	, to_varchar(c.enroller_id) as enroller_id
	, c.type_id
	, c.status_id
	, c.country
	, c.rank_id
	, c.rank_high_type_id as DT_Rank_id
	, c.rank_id-c.rank_high_type_id as diff
	, c.value_1 as MinRank
	, c.vol_1, c.vol_4, c.vol_10, round(c.vol_12,2) as vol_12
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 0)  as leg_0
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 1)  as leg_1
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 2)  as leg_2
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 3)  as leg_3
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 4)  as leg_4
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 5)  as leg_5
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 6)  as leg_6
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 7)  as leg_7
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 8)  as leg_8
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 9)  as leg_9
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 10) as leg_10
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 11) as leg_11
	, (select count(*) from l_Qual2 where sponsor_id = c.customer_id and leg_rank_id = 12) as leg_12
from l_Cust2 c
order by 2, 1;
