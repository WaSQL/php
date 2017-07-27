drop procedure commissions.CUSTOMER_ENROLLMENT_DATE_UPDATE;
create procedure commissions.CUSTOMER_ENROLLMENT_DATE_UPDATE
/*-------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/26/2017
*
* @describe		Creates an enrollment date change in the customer_log table
*
* @param		integer pn_customer_id
* @param		timestamp pd_enrollment_date
* @out_param	varchar(50) ps_result
* @param		integer pn_apply_to_history
------------------------------------------------------*/
	(
		pn_customer_id 				integer
		, pd_enrollment_date 		timestamp
		, out ps_result 			varchar(50)
		, pn_apply_to_history 		integer default 0
		)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions

as
BEGIN
	declare ld_curr_date date;
	select max(entry_date)
	into ld_curr_date
	from customer
	where customer_id = :pn_customer_id;
	
	if (:ld_curr_date < :pd_enrollment_date) then
		ps_result = 'ERROR: new date cannot precede entry date';
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
			,8
			,customer_id
			,source_key_id
			,source_id
			,type_id
			,status_id
			,Sponsor_id
			,Enroller_id
			,country
			,:pd_enrollment_date
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
