drop procedure commissions.sp_customer_type_update;
create procedure commissions.sp_customer_type_update
/*------------------------------------------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/26/2017
*
* @describe     inserts a type change into the customer_log table
*
* @param		integer pn_customer_id customer ID whose frontline is to be returned
* @param		integer pn_customer_status_id
* @out_param	varchar ps_result
* @param		integer pn_apply_to_history
---------------------------------------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_customer_type_id 	integer
		, out ps_result 		varchar(50)
		, pn_apply_to_history 	integer default 0
	)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_has_downline integer;
	declare ln_can_downline integer;
	
	
	select count(*) into ln_has_downline from customer where sponsor_id = :pn_customer_id;
	select has_downline into ln_can_downline from customer_type where type_id = :pn_customer_type_id;
	
	if (:ln_has_downline > 0 and :ln_can_downline = 0) then 
		ps_result := 'invalid type - customer has downline';
	else
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
			,6
			,customer_id
			,source_key_id
			,source_id
			,:pn_customer_type_id
			,status_id
			,Sponsor_id
			,Enroller_id
			,country
			,comm_status_date
			,entry_date
			,current_timestamp
			,termination_date
			,null
			,ifnull(:pn_apply_to_history, 0)				
		from customer
		where customer_id = :pn_customer_id;
		commit;
		ps_result = 'success';
	end if;
END;
