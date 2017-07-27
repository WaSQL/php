DROP FUNCTION COMMISSIONS.FN_CUSTOMER_VALIDATE;
CREATE FUNCTION commissions.FN_CUSTOMER_VALIDATE 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/12/2017
*
* @describe     validates a tree move, returns a table of problems
*
* @param		integer pn_customer_id
* @param		integer pn_enroller_id
* @param		integer pn_sponsor_id
* @param		integer pn_rollup_downline
* @param		integer pn_swap_customer
* @param		integer [pn_copy_to_open]
* @param		varchar [ps_locale]
*
* @returns 		table
*				integer customer_id
*				integer period_id
*				varchar description
*
* @example      select * from commissions.fn_customer_validate(1001, 1, 1, 0, 1, 0)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_enroller_id 		integer
		, pn_sponsor_id 		integer
		, pn_rollup_downline 	integer
		, pn_swap_customer 		integer
		, pn_copy_to_open 		integer default null
		, ls_locale				varchar(20) default 'en-US'
	)
	returns table (
		customer_id 		integer
		, period_id 		integer
		, description 		varchar(50))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions	
as
BEGIN
	declare ln_open_monthly_period integer;
	declare ln_open_weekly_period integer;
	declare ln_log_check integer;
	declare ln_curr_sponsor integer;
	declare ln_curr_enroller integer;
	declare ln_hist_sponsor_monthly integer;
	declare ln_hist_enroller_monthly integer;
	declare ln_hist_sponsor_weekly integer;
	declare ln_hist_enroller_weekly integer;
	
	select max(p.period_id)
	into ln_open_monthly_period
	from period p
	where p.closed_date is null
		and p.period_type_id = 1;
	
	select max(p.period_id)
	into ln_open_weekly_period
	from period p
	where p.closed_date is null
		and p.period_type_id = 2;

	select count(*) 
	into ln_log_check
	from fn_customer_upline(:pn_customer_id, 0) u
		, customer_log l
	where l.customer_id = u.customer_id
		or l.enroller_id = u.customer_id
		or l.sponsor_id = u.customer_id;
	
	if ln_log_check > 0 then
		return select :pn_customer_id customer_id, null period_id, 'outstanding change conflict' description from dummy;
	end if;
	
	select enroller_id
		, sponsor_id
	into ln_curr_enroller
		, ln_curr_sponsor
	from customer
	where customer_id = :pn_customer_id;
	
	select max(enroller_id)
		, max(sponsor_id)
	into ln_hist_enroller_monthly
		, ln_hist_sponsor_monthly
	from customer_history
	where customer_id = :pn_customer_id
		and period_id = :ln_open_monthly_period and batch_id = gl_period_viewable(:ln_open_monthly_period);
	
	select max(enroller_id)
		, max(sponsor_id)
	into ln_hist_enroller_weekly
		, ln_hist_sponsor_weekly
	from customer_history
	where customer_id = :pn_customer_id
		and period_id = :ln_open_weekly_period and batch_id = gl_period_viewable(:ln_open_weekly_period);

	if :pn_swap_customer = 1 then
		if :ln_curr_enroller != :pn_enroller_id or :ln_curr_sponsor != :pn_sponsor_id then
			return select :pn_customer_id customer_id, null period_id, 'cannot change tree on swap' description from dummy;
		end if;
		if :ln_hist_enroller_monthly != :pn_enroller_id or :ln_hist_sponsor_monthly != :pn_sponsor_id then
			return select :pn_customer_id customer_id, null period_id, 'cannot change tree on swap' description from dummy;
		end if;
		if :ln_hist_enroller_weekly != :pn_enroller_id or :ln_hist_sponsor_weekly != :pn_sponsor_id then
			return select :pn_customer_id customer_id, null period_id, 'cannot change tree on swap' description from dummy;
		end if;
	end if;
	
	if (ifnull(pn_copy_to_open, 0) = 0) then
		return 
			select * 
			from fn_customer_validate_main(:pn_customer_id
				, :pn_enroller_id
				, :pn_sponsor_id
				, :pn_rollup_downline
				, :pn_swap_customer);
	else 
		return 
			select * 
			from fn_customer_validate_main(:pn_customer_id
				, :pn_enroller_id
				, :pn_sponsor_id
				, :pn_rollup_downline
				, :pn_swap_customer)
			union
			select * 
			from fn_customer_validate_history(:pn_customer_id
				, :pn_enroller_id
				, :pn_sponsor_id
				, :pn_rollup_downline
				, :pn_swap_customer
				, to_integer(:ln_open_monthly_period))
			union
			select * 
			from fn_customer_validate_history(:pn_customer_id
				, :pn_enroller_id
				, :pn_sponsor_id
				, :pn_rollup_downline
				, :pn_swap_customer
				, to_integer(:ln_open_weekly_period));
	end if;
END;