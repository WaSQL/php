-- DEPRICATED ----------------------------------------------------------------
drop function commissions.FN_UNILEVEL_LEG;
create function commissions.FN_UNILEVEL_LEG (
	pn_customer_id 		integer
	, pn_rank_id 		integer
	, pn_period_id 		integer
	, ls_locale			varchar(20) default 'en-US'
	)
	returns table (
		QUALIFIER_FLAG 		integer
		, CUSTOMER_NAME		nvarchar(900)
		, CUSTOMER_ID 		integer
		, leg_customer_id	integer
		, leg_customer_name	nvarchar(900)
		, rank_id			integer
		)
	LANGUAGE SQLSCRIPT
	DEFAULT SCHEMA commissions
/*--------------------------------------------------------------------------------------------------------
Author: Del Stirling
Date: 5/18/2017
description:  Gets data for each leg of a unilevel commission and tells whether that leg qualifies the sponsor for the given rank
example call:  SELECT * from commissions.fn_unilevel_leg(1001, 12, 14)
DEPRICATED
-------------------------------------------------------------------------------------------------------------*/
as
BEGIN
	declare ln_batch_id integer = gl_period_viewable(:pn_period_id);

	if (gl_period_isopen(:pn_period_id) = 1) then
		return
		select case when req.rank_id is not null then 1 else 0 end as qualifier_flag
			, c.customer_name
			, c.customer_id
			, q.leg_customer_id
			, leg.customer_name as leg_customer_name
			, q.leg_rank_id as rank_id
		from customer_qual_leg q
			inner join customer c on c.customer_id = q.customer_id
			inner join customer leg on leg.customer_id = q.leg_customer_id
			inner join customer_type t on t.type_id = c.type_id
			left join version v on v.country = c.country
			left join rank_req_template req 
				on req.rank_id = :pn_rank_id
					and req.leg_rank_id <= q.leg_rank_id
					and req.version_id = ifnull(v.version_id, 1)
		where q.sponsor_id = :pn_customer_id
			and exists (
				select l.customer_id
					, max(l.leg_rank_id)
				from commissions.customer_qual_leg l
				where l.customer_id = q.customer_id
					and l.sponsor_id = q.sponsor_id
				group by l.customer_id
				having max(l.leg_rank_id) = q.leg_rank_id
				);
	else
		return
		select case when req.rank_id is not null then 1 else 0 end as qualifier_flag
			, c.customer_name
			, c.customer_id
			, q.leg_customer_id
			, leg.customer_name as leg_customer_name
			, q.leg_rank_id as rank_id
		from customer_history_qual_leg q
			inner join customer_history c on c.customer_id = q.customer_id
				and c.period_id = q.period_id
				and c.batch_id = q.batch_id
			inner join customer_history leg on leg.customer_id = q.leg_customer_id
				and leg.period_id = q.period_id
				and leg.batch_id = q.batch_id
			inner join customer_type t on t.type_id = c.type_id
			left join version v on v.country = c.country
			left join rank_req req 
				on req.rank_id = :pn_rank_id
					and req.leg_rank_id <= q.leg_rank_id
					and req.version_id = ifnull(v.version_id, 1)
					and req.period_id = q.period_id
					and req.batch_id = q.batch_id
		where q.sponsor_id = :pn_customer_id
			and q.period_id = :pn_period_id
			and q.batch_id = :ln_batch_id
			and exists (
				select l.customer_id
					, max(l.leg_rank_id)
				from commissions.customer_history_qual_leg l
				where l.customer_id = q.customer_id
					and l.sponsor_id = q.sponsor_id
					and l.period_id = q.period_id
					and l.batch_id = q.batch_id
				group by l.customer_id
				having max(l.leg_rank_id) = q.leg_rank_id
				);
	end if;
END;