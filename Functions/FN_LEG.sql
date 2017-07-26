DROP FUNCTION COMMISSIONS.FN_LEG;
create function commissions.FN_LEG
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/23/2017
*
* @describe     returns the rank and rank description of each leg
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer rank_id
*				varchar rank_description
*				integer leg_count
*
* @example      select * from commissions.fn_leg(1001, 14)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		RANK_ID 				integer
		, RANK_DESCRIPTION 		varchar(50)
		, LEG_COUNT 			integer)
	LANGUAGE SQLSCRIPT
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_rank_id integer;
	
	select ifnull(max(rank_id),1)
	into ln_rank_id
	from customer_history 
	where customer_id = :pn_customer_id 
		and period_id = :pn_period_id
		and batch_id = gl_period_viewable(:pn_period_id);
		
	return
	select l.rank_id
		, r.description rank_description
		, count(*) leg_count
	from fn_unilevel_leg(:pn_customer_id, :ln_rank_id, :pn_period_id) l
		inner join rank r on r.rank_id = l.rank_id
	group by l.rank_id
		, r.description;
END;