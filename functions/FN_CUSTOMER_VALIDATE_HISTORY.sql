DROP FUNCTION COMMISSIONS.FN_CUSTOMER_VALIDATE_HISTORY;
CREATE FUNCTION commissions.FN_CUSTOMER_VALIDATE_history /*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/12/2017
*
* @describe     validates move in the customer_history table
*
* @param		integer pn_customer_id
* @param		integer pn_enroller_id
* @param		integer pn_sponsor_id
* @param		integer pn_rollup_downline
* @param		integer pn_swap_customer
* @param		integer pn_period_id
*
* @returns 		table
*				integer pn_customer_id
*				integer pn_period_id
*				varchar description
* @example      select * from commissions.FN_CUSTOMER_VALIDATE_history(1001, 1, 1, 0, 1, 14)
-------------------------------------------------------*/
	(
		pn_customer_id integer
		, pn_enroller_id integer
		, pn_sponsor_id integer
		, pn_rollup_downline integer
		, pn_swap_customer integer
		, pn_period_id integer
	)
	
	returns table (
		customer_id 	integer
		, period_id 	integer
		, description 	varchar(50)
		)
	LANGUAGE SQLSCRIPT
	sql security invoker
   	DEFAULT SCHEMA Commissions	
/*----------------------------------------------------
by Del Stirling
validates move in the customer_history table
---------------------------------------------------*/
as
BEGIN
	--need to make sure the customer isn't the current enroller for anyone
	if (:pn_rollup_downline = 1) then 
		return 
		select customer_id
			, :pn_period_id as period_id
			, 'cannot move enroller' as description
		from customer_history
		where enroller_id = :pn_customer_id
			and period_id = :pn_period_id
			and batch_id = (select max(batch_id) from period_batch where period_id = :pn_period_id and viewable = 1)
		union
		select :pn_customer_id customer_id 
			, :pn_period_id as period_id
			, 'new enroller not in upline'
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;
	end if;
	
	--only need to check the new enroller and sponsor
	if (:pn_swap_customer = 1) then 
		return
		select :pn_customer_id customer_id 
			, :pn_period_id as period_id
			, 'new enroller not in upline' as description
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;
	end if;
	
	--validate sponsor, enroller, and make sure the new sponsor upline contains the enrollers that aren't in the customer's organization
	return 
		select customer_id
			, :pn_period_id as period_id
			, 'enroller not in upline' as description
		from gl_validate_enroller_org(:pn_customer_id) prob
		where enroller_id not in (select customer_id from fn_Customer_upline(:pn_sponsor_id, 0))
		union
		select :pn_customer_id customer_id 	
			, :pn_period_id as period_id
			, 'new enroller not in upline' as description
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;
END;