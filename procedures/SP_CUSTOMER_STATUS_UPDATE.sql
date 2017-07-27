DROP PROCEDURE SP_CUSTOMER_STATUS_UPDATE;
create procedure commissions.sp_customer_status_update
/*------------------------------------------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/26/2017
*
* @describe     inserts a status change into the customer_log table
*
* @param		integer pn_customer_id 
* @param		integer pn_customer_status_id
* @out_param	varchar ps_result
* @param		integer pn_apply_to_history
---------------------------------------------------------------------------------------*/
	(
		pn_customer_id 				integer
		, pn_customer_status_id 	integer
		, out ps_result 			varchar(50)
		, pn_apply_to_history 		integer default 0
	)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
as
BEGIN
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
		,7
		,customer_id
		,source_key_id
		,source_id
		,type_id
		,:pn_customer_status_id
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
END;