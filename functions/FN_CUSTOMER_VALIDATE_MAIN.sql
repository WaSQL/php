DROP FUNCTION COMMISSIONS.FN_CUSTOMER_VALIDATE_MAIN;
CREATE FUNCTION commissions.FN_CUSTOMER_VALIDATE_MAIN 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/12/2017
*
* @describe     validates a tree move in the customer table
*
* @param		integer pn_customer_id
* @param		integer pn_enroller_id
* @param		integer pn_sponsor_id
* @param		integer pn_rollup_downline
* @param		integer pn_swap_customer
*
* @returns 		table
*				integer customer_id
*				integer period_id
*				varchar description
*
* @example      select * from commissions.fn_customer_validate_main(1001, 1, 1, 0, 1)
-------------------------------------------------------*/
	(
		pn_customer_id integer
		, pn_enroller_id integer
		, pn_sponsor_id integer
		, pn_rollup_downline integer
		, pn_swap_customer integer
	)
	
	returns table (
		customer_id 	integer
		, period_id 	integer
		, description 	varchar(50)
		)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions	
as
BEGIN
	--need to make sure the customer isn't the current enroller for anyone
	if (:pn_rollup_downline = 1) then 
		return 
		select customer_id
			, null period_id
			, 'cannot move enroller' as description
		from customer
		where enroller_id = :pn_customer_id
		union
		select :pn_customer_id customer_id 
			, null as period_id
			, 'new enroller not in upline' as description
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;
	end if;
	
	--only need to check the new enroller and sponsor
	if (:pn_swap_customer = 1) then 
		return
		select :pn_customer_id customer_id 
			, null as period_id
			, 'new enroller not in upline' as description
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;
	end if;

	--validate sponsor, enroller, and make sure the new sponsor upline contains the enrollers that aren't in the customer's organization
	return 
		select customer_id
			, null as period_id
			, 'enroller not in upline' as description
		from gl_validate_enroller_org(:pn_customer_id) prob
		where enroller_id not in (select customer_id from fn_Customer_upline(:pn_sponsor_id, 0))
		union
		select :pn_customer_id customer_id 
			, null
			, 'new enroller not in upline'
		from dummy 
		where gl_validate_spon_enroll(:pn_sponsor_id, :pn_enroller_id) = 0;			
END;