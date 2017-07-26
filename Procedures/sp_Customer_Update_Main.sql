drop procedure Commissions.sp_Customer_Update_Main;
create procedure Commissions.sp_Customer_Update_Main(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer
					, pn_Downline_Rollup	integer default 0
					, pn_Swap_id			integer default 0
					, pn_Log				integer default 1)
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions
AS

begin
	declare ld_Current_Timestamp	timestamp = current_timestamp;
	declare ln_Count				integer;
	declare ln_Validate				integer = 0;
	declare ln_Customer_log_type_id	integer;
	declare ln_Source_Key_id		integer;
	declare ln_S_Source_Key_id		integer;
	declare ln_Source_id			integer;
	declare ln_Type_id				integer;
	declare ln_Status_id			integer;
	declare ln_Sponsor_id			integer;
	declare ln_Enroller_id			integer;
	declare ln_Country				varchar(5);
	declare ld_Comm_status_date		timestamp;
	declare ld_Termination_date		timestamp;
	declare ld_Entry_date			timestamp;
	declare ln_Vol_1				double;
	declare ln_Vol_4				double;
	declare ln_Vol_13				double;
	declare ln_S_Source_id			integer;
	declare ln_S_Type_id			integer;
	declare ln_S_Status_id			integer;
	declare ln_S_Sponsor_id			integer = :pn_Sponsor_id;
	declare ln_S_Enroller_id		integer = :pn_Enroller_id;
	declare ln_S_Country			varchar(5);
	declare ld_S_Comm_status_date	timestamp;
	declare ld_S_Termination_date	timestamp;
	declare ld_S_Entry_date			timestamp;
	declare ln_S_Vol_1				double;
	declare ln_S_Vol_4				double;
	declare ln_S_Vol_13				double;
	declare ld_Processed_date		timestamp;
		
	-- Get Main Customer Info
	select  source_key_id,    source_id,    type_id,    status_id,    sponsor_id,    enroller_id,    country,    comm_status_date,    entry_date,    termination_date,    vol_1,    vol_4,    vol_13
	into ln_Source_Key_id, ln_Source_id, ln_Type_id, ln_Status_id, ln_Sponsor_id, ln_Enroller_id, ln_Country, ld_Comm_status_date, ld_Entry_date, ld_Termination_date, ln_Vol_1, ln_Vol_4, ln_Vol_13
	from customer
	where customer_id = :pn_Customer_id;
	
	-- Get Swap Customer Info
	if ifnull(:pn_Swap_id,0) <> 0 then
		select    source_key_id,      source_id,      type_id,      status_id,      sponsor_id,      enroller_id,      country,      comm_status_date,      entry_date,      termination_date,      vol_1,    vol_4,      vol_13
		into ln_S_Source_Key_id, ln_S_Source_id, ln_S_Type_id, ln_S_Status_id, ln_S_Sponsor_id, ln_S_Enroller_id, ln_S_Country, ld_S_Comm_status_date, ld_S_Entry_date, ld_S_Termination_date, ln_S_Vol_1, ln_Vol_4, ln_S_Vol_13
		from customer
		where customer_id = :pn_Swap_id;
	end if;
	
	--===================================================================================================
	-- Single Move with Downline
	if ifnull(:pn_Downline_Rollup,0) = 0 and ifnull(:pn_Swap_id,0) = 0 then
		ln_Customer_log_type_id = 1;
			
		-- Update Customer
		update customer
		set Sponsor_id = :ln_S_Sponsor_id
		   ,Enroller_id = :ln_S_Enroller_id
		where customer_id = :pn_Customer_id;
		
		-- Retail Customer
		if :ln_Type_id = 2 or :ln_Type_id = 3 then
			update customer
			set vol_4 = vol_4 - :ln_Vol_1
			where customer_id = :ln_Sponsor_id;
			
			update customer
			set vol_4 = vol_4 + :ln_Vol_1
			where customer_id = :ln_S_Sponsor_id;
		end if;
			
		-- Remove Org Volume from Old upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_Sponsor_id,
			(:ln_Vol_13 * -1));
		
		-- Add Org Volume to New upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_S_Sponsor_id,
			:ln_Vol_13);
	end if;
	
	--===================================================================================================
	-- Single Move without Downline
	if ifnull(:pn_Downline_Rollup,0) = 1 and ifnull(:pn_Swap_id,0) = 0 then
		ln_Customer_log_type_id = 2;
			
		-- Update Customer
		update customer
		set Sponsor_id = :ln_S_Sponsor_id
		   ,Enroller_id = :ln_S_Enroller_id
		   ,vol_12 = vol_1
		where customer_id = :pn_Customer_id;
		
		-- Retail Customer
		if :ln_Type_id = 2 or :ln_Type_id = 3 then
			update customer
			set vol_4 = vol_4 - :ln_Vol_1
			where customer_id = :ln_Sponsor_id;
			
			update customer
			set vol_4 = vol_4 + :ln_Vol_1
			where customer_id = :ln_S_Sponsor_id;
		end if;
		
		-- Update Downline
		update customer
		set Sponsor_id = :ln_Sponsor_id
		   ,Enroller_id = :ln_Enroller_id
		where Sponsor_id = :pn_Customer_id;
		
		-- Remove PV Volume from Old upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_Sponsor_id,
			(:ln_Vol_1 * -1));
		
		-- Add PV Volume to New upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_S_Sponsor_id,
			:ln_Vol_1);
			
		-- Log Downline Rollup
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
			,PROCESSED_DATE)
		select
			 customer_id.nextval
			,4
			,customer_id
			,source_key_id
			,source_id
			,type_id
			,status_id
			,:ln_Sponsor_id
			,:ln_Enroller_id
			,country
			,comm_status_date
			,entry_date
			,termination_date
			,:ld_Current_Timestamp
			,:ld_Processed_date
		from customer
		where Sponsor_id = :pn_Customer_id;
	end if;
	
	--===================================================================================================
	-- Swap
	if ifnull(:pn_Swap_id,0) <> 0 then
		ln_Customer_log_type_id = 3;
			
		-- Update Customers
		update customer
		set Sponsor_id = :ln_S_Sponsor_id
		   ,Enroller_id = :ln_S_Enroller_id
		   ,vol_4 = ln_S_Vol_4
		where customer_id = :pn_Customer_id;
		
		update customer
		set Sponsor_id = :ln_Sponsor_id
		   ,Enroller_id = :ln_Enroller_id
		   ,vol_4 = ln_Vol_4
		where customer_id = :pn_Swap_id;
		
		select vol_4
		into ln_Vol_4
		from customer
		where customer_id = :ln_Sponsor_id;
		
		select vol_4
		into ln_S_Vol_4
		from customer
		where customer_id = :ln_S_Sponsor_id;
		
		update customer
		set vol_4 = ln_S_Vol_4
		where customer_id = :ln_Sponsor_id;
		
		update customer
		set vol_4 = ln_Vol_4
		where customer_id = :ln_S_Sponsor_id;
		
		-- Swap Downlines
		update customer
		set Sponsor_id = :ln_S_Sponsor_id
		   ,Enroller_id = :ln_S_Enroller_id
		where Sponsor_id = :pn_Customer_id;
		
		update customer
		set Sponsor_id = :ln_Sponsor_id
		   ,Enroller_id = :ln_Enroller_id
		where Sponsor_id = :pn_Swap_id;
		
		-- Remove PV Volume from Old upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_Sponsor_id,
			(:ln_Vol_1 * -1));
			
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_S_Sponsor_id,
			(:ln_S_Vol_1 * -1));
		
		-- Add PV Volume to New upline
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_Sponsor_id,
			:ln_S_Vol_1);
			
		call Commissions.sp_Customer_Rollup_Volume_Org(
			:ln_S_Sponsor_id,
			:ln_Vol_1);
			
		-- Log Downline Swap
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
			,PROCESSED_DATE)
		select
			 customer_id.nextval
			,4
			,customer_id
			,source_key_id
			,source_id
			,type_id
			,status_id
			,:ln_S_Sponsor_id
			,:ln_S_Enroller_id
			,country
			,comm_status_date
			,entry_date
			,termination_date
			,:ld_Current_Timestamp
			,:ld_Processed_date
		from customer
		where Sponsor_id = :pn_Customer_id;
		
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
			,PROCESSED_DATE)
		select
			 customer_id.nextval
			,4
			,customer_id
			,source_key_id
			,source_id
			,type_id
			,status_id
			,:ln_Sponsor_id
			,:ln_Enroller_id
			,country
			,comm_status_date
			,entry_date
			,termination_date
			,:ld_Current_Timestamp
			,:ld_Processed_date
		from customer
		where Sponsor_id = :pn_Swap_id;
		
		
		-- Log Swap Request
		if ifnull(:pn_Log,1) = 1 then
			select count(*)
			into ln_Count
			from customer_log
			where customer_id = :pn_Swap_id;
			
			if :ln_Count = 0 then
				call sp_Customer_Log_Add(
							 :pn_Swap_id
							,5
							,:ln_S_Source_Key_id
							,:ln_S_Source_id
							,:ln_S_Type_id
							,:ln_S_Status_id
							,:ln_S_Sponsor_id
							,:ln_S_Enroller_id
							,:ln_S_Country
							,:ld_S_Comm_status_date
							,:ld_S_Entry_date
							,:ld_S_Termination_date
							,:ld_Current_Timestamp
							,:ld_Processed_date);
			end if;
			
			call sp_Customer_Log_Add(
						 :pn_Swap_id
						,:ln_Customer_log_type_id
						,:ln_Source_Key_id
						,:ln_Source_id
						,:ln_Type_id
						,:ln_Status_id
						,:ln_Sponsor_id
						,:ln_Enroller_id
						,:ln_Country
						,:ld_Comm_status_date
						,:ld_Entry_date
						,:ld_Termination_date
						,:ld_Current_Timestamp
						,:ld_Processed_date);
		end if;
	end if;
		
	--===================================================================================================
	-- Log Request
	if ifnull(:pn_Log,1) = 1 then
		select count(*)
		into ln_Count
		from customer_log
		where customer_id = :pn_Customer_id;
		
		if :ln_Count = 0 then
			call sp_Customer_Log_Add(
						 :pn_Customer_id
						,5
						,:ln_Source_Key_id
						,:ln_Source_id
						,:ln_Type_id
						,:ln_Status_id
						,:ln_Sponsor_id
						,:ln_Enroller_id
						,:ln_Country
						,:ld_Comm_status_date
						,:ld_Entry_date
						,:ld_Termination_date
						,:ld_Current_Timestamp
						,:ld_Processed_date);
		end if;
		
		call sp_Customer_Log_Add(
					 :pn_Customer_id
					,:ln_Customer_log_type_id
					,:ln_Source_Key_id
					,:ln_Source_id
					,:ln_Type_id
					,:ln_Status_id
					,:ln_S_Sponsor_id
					,:ln_S_Enroller_id
					,:ln_Country
					,:ld_Comm_status_date
					,:ld_Entry_date
					,:ld_Termination_date
					,:ld_Current_Timestamp
					,:ld_Processed_date);
	end if;
		
	commit;
		
	pt_Result = select :ln_Validate as validate from dummy;

end;
