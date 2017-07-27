with lc_Period_Tree as (
		select *
		from HIERARCHY ( 
			 	SOURCE ( select c.customer_id AS node_id, sponsor_id AS parent_id, c.*
			             from customer_history c
						 where c.period_id = 9
						 and c.batch_id = 1
			           )
				Start where sponsor_id = 3)
)
select HIERARCHY_level, to_varchar(c.dist_id) as dist_id, c.sponsor_id,h.type_id, h.status_id, c.vol_1, h.vol_1, c.vol_1-h.vol_1 as diff_1, c.vol_12, h.vol_12, c.vol_12-h.vol_12 as diff_12, vol_5
from orabwt c 
	left outer join lc_Period_Tree h
	on c.dist_id = h.customer_id
where c.dist_id = h.customer_id
and round(c.vol_12,2) <> round(h.vol_12,2)
order by HIERARCHY_level;