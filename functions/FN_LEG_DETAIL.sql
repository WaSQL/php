DROP FUNCTION COMMISSIONS.FN_LEG_DETAIL;
create function commissions.FN_LEG_DETAIL 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/30/2017
*
* @describe     Gets data for each leg of a unilevel commission 
*
* @param		integer pn_customer_id
* @param		integer pn_rank_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer customer_id
*				nvarchar customer_name
*				integer rank_id
*				varchar rank_description
*
* @example      select * from commissions.fn_leg_detail(1001, 12, 14)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_rank_id 			integer
		, pn_period_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		CUSTOMER_ID 		integer
		, CUSTOMER_NAME 	nvarchar(900)
		, RANK_ID 			integer
		, RANK_DESCRIPTION 	varchar(50)
		)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ld_period_date date;
	declare ln_batch_id integer;
	select max(p.closed_date)
		, ifnull(max(b.batch_id), 0)
	into ld_period_date
		, ln_batch_id
	from period p
		left join period_batch b
			on b.period_id = p.period_id
				and b.viewable = 1
	where p.period_id = :pn_period_id;
	
	if (:ld_period_date is null) then
		return
		select c.customer_id
			, c.customer_name
			, c.rank_id as rank_id
			, r.description as rank_description
		from customer c 
			inner join customer_type t on t.type_id = c.type_id
			inner join rank r on r.rank_id = c.rank_id
			left join version v on v.country = c.country
		where c.enroller_id = :pn_customer_id
		order by rank_id desc;
	else
		return
		select c.customer_id
			, c.customer_name
			, c.rank_id as rank_id
			, r.description as rank_description
		from customer_history c 
			inner join customer_type t on t.type_id = c.type_id
			inner join rank r on r.rank_id = c.rank_id
			left join version v on v.country = c.country
		where c.enroller_id = :pn_customer_id
			and c.period_id = :pn_period_id
			and c.batch_id = :ln_batch_id
		order by rank_id desc;
	end if;
END;