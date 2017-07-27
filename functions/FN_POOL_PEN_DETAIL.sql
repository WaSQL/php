DROP FUNCTION COMMISSIONS.FN_POOL_PEN_DETAIL;
create function commissions.fn_pool_pen_detail
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/23/2017
*
* @describe     returns list of customers, enrolled by the given customer, who have acheived the given rank 
*				for the first time in the given comm period
*
* @returns 		table
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_rank_id
* @param		varchar [locale]
*
* @example      select * from commissions.fn_pool_pen(48403, 13)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, pn_rank_id			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		customer_id			integer
		, customer_name		nvarchar(900)
		, rank				varchar(50))
	language sqlscript
	default schema commissions
as 
begin
	return
	select e.customer_id
		, e.customer_name
		, r.rank_id || ' - ' || r.description rank
	from (
		select customer_id
			, customer_name
			, period_id
			, rank_id
			, rank_high_id
			, lag(rank_high_id) over (partition by customer_id order by period_id) prev_rank
		from commissions.customer_history
		where enroller_id = :pn_customer_id
			and rank_id = :pn_rank_id
		order by customer_id
			, period_id
			) e
		left join rank r 
			on r.rank_id = e.rank_id
	where ifnull(e.prev_rank, 0) < e.rank_id
		and e.period_id = :pn_period_id;
end;