drop procedure commissions.CUSTOMER_UPDATE;
create procedure commissions.CUSTOMER_UPDATE
/*--------------------------------------------------
* @author       Del Stirling
* @category     stored procedure
* @date			4/21/2017
*
* @describe     inserts an update request into the customer_log table
*
* @param		integer pn_customer_id
* @param		integer pn_sponsor_id
* @param		integer pn_enroller_id
* @out_param	integer pn_result
* @param		integer [pn_downline_rollup]
* @param		integer [pn_swap_id]
* @param		integer [pn_apply_to_history]
* @param		itneger [pn_log]
*
* @example      call commissions.customer_update(4219710, 1772050, 1772050,?)
-------------------------------------------------------*/
	(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer
					, out pn_result			varchar(50)
					, pn_Downline_Rollup	integer default 0
					, pn_Swap_id			integer default 0
					, pn_apply_to_history	integer default 0		
					, pn_Log				integer default 1)
	LANGUAGE SQLSCRIPT 
	SQL SECURITY INVOKER
	DEFAULT SCHEMA Commissions
AS
BEGIN
	declare ln_log_type integer;
	declare ln_curr_sponsor integer;
	declare ln_curr_enroller integer;
	declare ln_validate integer;
	
	-- Validate Move Request
	select count(*) into ln_validate from fn_Customer_Validate(:pn_Customer_id, :pn_Enroller_id, :pn_Sponsor_id, :pn_Downline_Rollup, case when :pn_swap_id > 0 then 1 else 0 end);
	
	if :ln_validate	= 0 then
	
		--determine log type
		select sponsor_id, enroller_id 
		into ln_curr_sponsor, ln_curr_enroller 
		from customer 
		where customer_id = :pn_customer_id;
		--swap
		if :pn_swap_id > 0 then
			ln_log_type := 3;
		end if;
		--downline rollup
		if :pn_downline_rollup > 0 
			then ln_log_type := 4;
		end if;
		--move
		if :ln_curr_sponsor != :pn_sponsor_id and :pn_sponsor_id > 0 then
			--move with downline rollup
			if :pn_downline_rollup > 0 then 
				ln_log_type := 2;
			--move without downline rollup
			else
				ln_log_type := 1;
			end if;
		end if;
		
		insert into customer_log
			(CUSTOMER_LOG_ID
			,CUSTOMER_LOG_TYPE_ID
			,CUSTOMER_ID
			,SOURCE_KEY_ID
			,SOURCE_ID
			,TYPE_ID
			,STATUS_ID
			,SPONSOR_ID
			,ENROLLER_ID
			,COUNTRY
			,COMM_STATUS_DATE
			,SOURCE_ENTRY_DATE
			,ENTRY_DATE
			,TERMINATION_DATE
			,PROCESSED_DATE
			,APPLY_TO_CLOSED)
		select
			 customer_log_id.nextval
			,:ln_log_type
			,customer_id
			,source_key_id
			,source_id
			,type_id
			,status_id
			,:pn_Sponsor_id
			,:pn_Enroller_id
			,country
			,comm_status_date
			,entry_date
			,current_timestamp
			,termination_date
			,null
			,ifnull(:pn_apply_to_history, 0)
		from customer
		where customer_id = :pn_Customer_id;
		commit;
		pn_result := 'success';
	else
		pn_result := 'invalid move';
	end if;
END;
