DROP FUNCTION COMMISSIONS.FN_POOL_SHARE_LEG;
create function commissions.fn_pool_share_leg
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/22/2017
*
* @describe     Returns the shares earned from each leg
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_pool_id
* @param		varchar locale
*
* @returns 		table
*				integer customer_id
*				nvarchar customer_name
*				integer shares
*				integer shares_extra
*				integer previous_rank_id
*				integer current_rank_id
*
* @example      select * from commissions.fn_pool_share_leg(1004, 13, 9)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, pn_pool_id 			integer
		, locale				varchar(20) default 'en-US')
	returns table (
		CUSTOMER_ID 		integer
		, CUSTOMER_NAME 	nvarchar(900)
		, SHARES 			integer
		, SHARES_EXTRA 		integer
		, PREVIOUS_RANK_ID 	integer
		, CURRENT_RANK_ID 	integer)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
AS
BEGIN
	declare ln_batch_id integer;
	select max(b.batch_id)
	into ln_batch_id
	from period_batch b
	where b.period_id = :pn_period_id
		and b.viewable = 1;
		
	if :pn_pool_id = 5 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_05 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 6 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_06 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 7 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_07 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 8 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_08 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 9 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_09 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 10 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_10 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	elseif :pn_pool_id = 11 then
		return 
		select c.customer_id
			, c.customer_name
			, p.shares
			, p.shares_extra
			, (select ifnull(max(rank_id), 0) from customer_rank_history where customer_id = :pn_customer_id and period_id < :pn_period_id) previous_rank_id
			, p.rank_id current_rank_id
		from customer c, earning_11 p
		where p.customer_id = c.customer_id
			and c.customer_id = :pn_customer_id
			and p.period_id = :pn_period_id
			and p.batch_id = :ln_batch_id;
	end if;
END;