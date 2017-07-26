drop function commissions.FN_UNILEVEL_LEG_DETAIL;
create function commissions.FN_UNILEVEL_LEG_DETAIL(
	pn_customer_id 			integer
	, pn_leg_customer_id 	integer
	, pn_period_id 			integer
	, pn_target_rank_id		integer
	, ls_locale				varchar(20) default 'en-US'
	)
	returns table(
		CUSTOMER_ID 		integer
		, CUSTOMER_NAME 	nvarchar(900)
		, PV 				decimal (18,2)
		, OV 				decimal (18,2)
		, "LEVEL" 			integer
		, RANK_ID 			integer
		, COUNTRY 			varchar(50)
		, rank_description	varchar(50)
		, qualifier_flag	integer
	)
	LANGUAGE SQLSCRIPT
	DEFAULT SCHEMA commissions
/*--------------------------------------------------------------------------------------------
Author: Del Stirling
Date: 5/18/2017
returns the qualifiers and potential qualifiers for the specified customer in their specified leg

example call:
select * from commissions.fn_unilevel_leg_detail(1001, 1004, 14, 12)
-----------------------------------------------------------------------------------------------*/
as
BEGIN
	declare ln_batch_id integer = gl_period_viewable(:pn_period_id);
	declare ln_req_rank integer;
	
	if (gl_period_isopen(:pn_period_id) = 1) then
		--get required leg rank
		select max(leg_rank_id)
		into ln_req_rank
		from rank_req
		where period_id = :pn_period_id
			and version_id = (
				select ifnull(version_id, 1) 
				from version 
				where country = (
					select country 
					from customer 
					where customer_id = :pn_customer_id
				)
			)
			and rank_id = :pn_target_rank_id;
			
		return
		select h.customer_id
			, h.customer_name
			, h.vol_1 as pv
			, h.vol_13 as ov
			, h.hierarchy_level "LEVEL"
			, h.rank_id
			, h.country
			, r.description rank_description
			, case when :ln_req_rank <= h.rank_id then 1 else 0 end as qualifier_flag
		from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id
		 				, sponsor_id AS parent_id
		 				, customer_id
		 				, customer_name
		 				, sponsor_id
		 				, enroller_id
		 				, vol_1
		 				, vol_13
		 				, rank_id
		 				, rank_high_id
		 				, country
		             from commissions.customer
		             order by customer_id)
				Start where customer_id = :pn_leg_customer_id) h
			left join rank r
				on r.rank_id = h.rank_id
		where enroller_id = :pn_customer_id
		order by rank_id desc;
	else
		--get required leg rank
		select max(leg_rank_id)
		into ln_req_rank
		from rank_req
		where period_id = :pn_period_id
			and version_id = (
				select ifnull(version_id, 1) 
				from version 
				where country = (
					select country 
					from customer_history 
					where customer_id = :pn_customer_id 
						and period_id = :pn_period_id 
						and batch_id = :ln_batch_id
				)
			)
			and rank_id = :pn_target_rank_id;

		return
		select h.customer_id
			, h.customer_name
			, h.vol_1 as pv
			, h.vol_13 as ov
			, h.hierarchy_level "LEVEL"
			, h.rank_id
			, h.country
			, r.description rank_description
			, case when :ln_req_rank <= h.rank_id then 1 else 0 end as qualifier_flag
		from HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id
		 				, sponsor_id AS parent_id
		 				, customer_id
		 				, customer_name
		 				, sponsor_id
		 				, enroller_id
		 				, vol_1
		 				, vol_13
		 				, rank_id
		 				, rank_high_id
		 				, country
		             from commissions.customer_history
		             where period_id = :pn_period_id
		             	and batch_id = :ln_batch_id
		             order by customer_id)
				Start where customer_id = :pn_leg_customer_id
					and period_id = :pn_period_id
					and batch_id = :ln_batch_id) h
			left join rank r
				on r.rank_id = h.rank_id
		where enroller_id = :pn_customer_id
		order by rank_id desc;
	end if;
END;