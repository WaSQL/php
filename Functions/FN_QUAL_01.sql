DROP FUNCTION COMMISSIONS.FN_QUAL_01;
CREATE function commissions.FN_QUAL_01
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/17/2017
*
* @describe     gets the rank qualification requirements for the given rank, period, and country
*
* @param		integer pn_customer_id
* @param		integer pn_rank_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer req_legs
*				integer actual_legs
*				decimal req_pv
*				decimal req_ov
*				decimal req_egv
*				varchar rank_description
*				integer rank_id
*				decimal actual_pv
*				decimal actual_ov
*				decimal actual_egv
*
* @example      select * from commissions.FN_qual_01(1001, 12, 14)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_rank_id 			integer
		, pn_period_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		req_legs 				integer
		, ACTUAL_LEGS 			integer
		, REQ_PV 				decimal(18,2)
		, REQ_OV 				decimal(18,2)
		, req_egv				decimal(18,2)
		, RANK_DESCRIPTION 		varchar(50)
		, RANK_ID				integer
		, actual_pv				decimal(18,2)
		, actual_ov				decimal(18,2)
		, actual_egv			decimal(18,2)
	)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_country varchar(50);
	declare ln_batch_id integer = gl_period_viewable(:pn_period_id);

	if gl_period_isopen(:pn_period_id) = 1 then
		select country
		into ln_country
		from customer
		where customer_id = :pn_customer_id;
	
		return 
		select r.leg_rank_count req_legs
			, count(distinct l.customer_id) ACTUAL_LEGS
			, r.vol_1 as req_pv
			, r.vol_2 as req_ov
			, r.vol_3 as req_egv
			, ifnull((select description from rank where rank_id = r.leg_rank_id),'Not Required') rank_description
			, r.leg_rank_id as rank_id
			, c.vol_1 as actual_pv
			, c.vol_13 as actual_ov
			, c.vol_11 as actual_egv
		from rank_req_template r
			left join customer_qual_leg l
				on l.sponsor_id = :pn_customer_id
					and l.leg_rank_id >= r.leg_rank_id
			left join customer c 
				on c.customer_id = :pn_customer_id
		where r.version_id = ifnull((select max(version_id) from version where country = :ln_country), 1)
			and r.rank_id = :pn_rank_id
		group by r.leg_rank_id
			, r.leg_rank_count
			, r.vol_1 
			, r.vol_2
			, r.vol_3
			, c.vol_1 
			, c.vol_11 
			, c.vol_13;
	else 
		select max(country)
		into ln_country
		from customer_history
		where customer_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = :ln_batch_id;
	
		return 
		select r.leg_rank_count req_legs
			, count(distinct l.customer_id) ACTUAL_LEGS
			, r.vol_1 as req_pv
			, r.vol_2 as req_ov
			, r.vol_3 as req_egv
			, ifnull((select description from rank where rank_id = r.leg_rank_id),'Not Required') rank_description
			, r.leg_rank_id as rank_id
			, c.vol_1 as actual_pv
			, c.vol_13 as actual_ov
			, c.vol_11 as actual_egv
		from rank_req r
			left join customer_history_qual_leg l
				on l.sponsor_id = :pn_customer_id
					and l.period_id = :pn_period_id
					and l.batch_id = :ln_batch_id
					and l.leg_rank_id >= r.leg_rank_id
			left join customer_history c --gl_customer(:pn_period_id, :ln_batch_id, :pn_customer_id) c 
				on c.customer_id = :pn_customer_id
				and c.period_id = :pn_period_id
				and c.batch_id = :ln_batch_id
		where r.version_id = ifnull((select max(version_id) from version where country = :ln_country), 1)
			and r.period_id = :pn_period_id
			and r.batch_id = :ln_batch_id
			and r.rank_id = :pn_rank_id
		group by r.leg_rank_id
			, r.leg_rank_count
			, r.vol_1 
			, r.vol_2
			, r.vol_3
			, c.vol_1
			, c.vol_11
			, c.vol_13;
	end if;
END;