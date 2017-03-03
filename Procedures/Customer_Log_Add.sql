drop procedure Commissions.Customer_Log_Add;
create procedure Commissions.Customer_Log_Add(
						 		 pn_Customer_id					integer
						 		,pn_Customer_Log_Type_id		integer
								,pn_Source_Key_id				integer
								,pn_Source_id					integer
								,pn_Type_id						integer
								,pn_Status_id					integer
								,pn_Sponsor_id					integer
								,pn_Enroller_id					integer
								,ps_Country						varchar(5)
								,pd_Comm_status_date			timestamp
								,pd_Entry_date					timestamp
								,pd_Termination_date			timestamp
								,pd_Current_Timestamp			timestamp
								,pd_Processed_date				timestamp)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	insert into customer_log
		(customer_log_id
		,customer_log_type_id
		,customer_id
		,source_key_id
		,source_id
		,type_id
		,status_id
		,sponsor_id
		,enroller_id
		,country
		,comm_status_date
		,source_entry_date
		,termination_date
		,entry_date
		,processed_date)
	values
		(customer_log_id.nextval
		,:pn_Customer_Log_Type_id
		,:pn_Customer_id
		,:pn_Source_Key_id
		,:pn_Source_id
		,:pn_Type_id
		,:pn_Status_id
		,:pn_Sponsor_id
		,:pn_Enroller_id
		,:ps_Country
		,:pd_Comm_status_date
		,:pd_Entry_date
		,:pd_Termination_date
		,:pd_Current_Timestamp
		,:pd_Processed_date);
		
	commit;
end;
