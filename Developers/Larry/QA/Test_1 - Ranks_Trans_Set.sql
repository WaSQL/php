call Commissions.Commission_Clear();
call Commissions.ranks_trans_set(8160749);

select *
from Commissions.customer
where customer_id = 1086738;

select t.*
from Commissions.customer c, Commissions.transaction_log t
where c.enroller_id <> c.sponsor_id
and c.customer_id = t.customer_id
and c.customer_id = 1086738;

select c.*
from Commissions.customer c, Commissions.transaction_log t
where c.customer_id = t.customer_id
and t.transaction_log_id = 8185169;

select -- Find Distributors matching requirments
		   c.customer_id
		 , c.sponsor_id
		 , c.enroller_id
		 , case w.req_waiver_type_id
		 		when 1 then	greatest(w.value_1,max(q.rank_id))
		 		when 2 then least(w.value_1,max(q.rank_id))
		 		when 3 then w.value_1
		 		else max(q.rank_id) end as new_rank_id
		 , 1 as rank_qual
	from Commissions.customer c
			left outer join Commissions.req_qual_leg_version v
				on c.country = v.country
			left outer join Commissions.req_waiver w
				on c.customer_id = w.customer_id
		, Commissions.req_qual_leg q
	where q.version_id = ifnull(v.version_id,1)
   	And c.type_id = 1
   	and c.status_id in (1, 4)
   	and c.customer_id = 1086738
	and (c.vol_1 >= q.vol_1 or (c.vol_10 >= q.vol_3 and v.version_id = 2))
	and c.vol_12 >= q.vol_2
	and (select count(*)
		 from (
			select *
			from (	
				select customer_id, leg_rank_id, row_number() over (partition by sponsor_id, customer_id order by entry_date desc, leg_rank_id desc) as row_num
				from Commissions.customer_history_qual_leg
				where period_id = 9
				and sponsor_id = c.customer_id)
			where row_num = 1
			and leg_rank_id >= 1
			and leg_rank_id >= q.leg_rank_id)) >= q.leg_rank_count
	group by c.customer_id, c.sponsor_id, c.enroller_id, w.req_waiver_type_id, w.value_1;
