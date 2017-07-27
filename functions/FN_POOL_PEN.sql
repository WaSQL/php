DROP FUNCTION COMMISSIONS.FN_POOL_PEN;
create function commissions.fn_pool_pen
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/22/2017
*
* @describe     returns a count of the new elites (5), premiers (6) and silvers(7) of a
*				customer's personal enrollments
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [locale]
*
* @returns 		table
*				integer rank_id
*				varchar rank_description
*				integer count
*
* @example      select * from commissions.fn_pool_pen(697139, 13)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		rank_id					integer
		, rank_description		varchar(50)
		, count					integer)
	language sqlscript
	default schema commissions
as 
begin
	return
	select e.rank_id	
		, r.description rank_description
		, count(*) count
	from (
		select customer_id
			, period_id
			, rank_id
			, rank_high_id
			, lag(rank_high_id) over (partition by customer_id order by period_id) prev_rank
		from commissions.customer_history
		where enroller_id = :pn_customer_id
		order by customer_id
			, period_id
			) e
		inner join rank r
			on r.rank_id= e.rank_id
	where ifnull(e.prev_rank, 0) < e.rank_id
		and e.period_id = :pn_period_id
		and e.rank_id in (select distinct value_1 from pool_req where type_id = 4)
	group by e.rank_id	
		, r.description;
end;