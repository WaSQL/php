drop procedure Commissions.Tree_Update;
create procedure Commissions.Tree_Update(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer
					, pn_Downline_Rollup	integer default 0
					, pn_Swap_id			integer default 0
					, ps_User				varchar(100)
					, out pt_Result 		table (pn_Validate	integer))
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions 
	--READS SQL DATA
AS

begin
	declare ld_Current_Timestamp	timestamp = current_timestamp;
	declare ln_Validate				integer = 0;
	declare ln_Val_Spon				integer = 0;
	declare ln_Val_Enroll			integer = 0;
	declare ln_Realtime_Trans 		integer;
	declare ln_Customer_log_type_id	integer;
	declare ln_Source_Key_id		integer;
	declare ln_Source_id			integer;
	declare ln_Type_id				integer;
	declare ln_Status_id			integer;
	declare ln_Sponsor_id			integer;
	declare ln_Enroller_id			integer;
	declare ln_Country				varchar(5);
	declare ld_Comm_status_date		timestamp;
	declare ld_Termination_date		timestamp;
	declare ld_Entry_date			timestamp;
	declare ld_Processed_date		timestamp;
	declare ln_Vol_1				double;
	declare ln_Vol_12				double;
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
			pt_Result = select 0 as pn_validate from dummy;
		end;
	
	-- Customer CAN NOT be their own sponsor or enroller
	if :pn_Customer_id != :pn_Sponsor_id and :pn_Customer_id != :pn_Enroller_id then
		-- Both sponsor and enroller MUST be in the customer's upline
		call Validate_Spon_Enroll(:pn_Customer_id, :pn_Enroller_id, :ln_Val_Enroll);
		call Validate_Spon_Enroll(:pn_Customer_id, :pn_Sponsor_id, :ln_Val_Spon);
		
		if :ln_Val_Spon = 1 and :ln_Val_Enroll = 1 then
			-- Enroller MUST be in sponsor's upline
			call Validate_Spon_Enroll(:pn_Sponsor_id, :pn_Enroller_id, :ln_Validate);
		end if;
	end if;
	
	if :ln_Validate = 1 then
		select realtime_trans 
		into ln_Realtime_Trans 
		from period 
		where period_id = 0;
		
		ld_Processed_date = null;
		if :ln_Realtime_Trans = 1 then
			ld_Processed_date = :ld_Current_Timestamp;
		end if;
			
		-- Get Old Info
		select  source_key_id,    source_id,    type_id,    status_id,    sponsor_id,    enroller_id,    country,    comm_status_date,    entry_date,    termination_date,    vol_1,    vol_12
		into ln_Source_Key_id, ln_Source_id, ln_Type_id, ln_Status_id, ln_Sponsor_id, ln_Enroller_id, ln_Country, ld_Comm_status_date, ld_Entry_date, ld_Termination_date, ln_Vol_1, ln_Vol_12
		from customer
		where customer_id = :pn_Customer_id;
		
		--===================================================================================================
		-- Single Move with Downline
		if ifnull(:pn_Downline_Rollup,0) = 0 and ifnull(:pn_Swap_id,0) = 0 then
			ln_Customer_log_type_id = 1;
				
			if :ln_Realtime_Trans = 1 then
				-- Remove Org Volume from Old upline
				call Commissions.Customer_Rollup_Volume_Org(
					:ln_Sponsor_id,
					(:ln_Vol_12 * -1));
				
				-- Add Org Volume to New upline
				call Commissions.Customer_Rollup_Volume_Org(
					:pn_Sponsor_id,
					:ln_Vol_12);
					
				-- Update Customer
				update customer
				set Sponsor_id = :pn_Sponsor_id
				   ,Enroller_id = :pn_Enroller_id
				where customer_id = :pn_Customer_id;
			end if;
		end if;
		
		--===================================================================================================
		-- Single Move without Downline
		if ifnull(:pn_Downline_Rollup,0) = 1 and ifnull(:pn_Swap_id,0) = 0 then
			ln_Customer_log_type_id = 2;
				
			if :ln_Realtime_Trans = 1 then
				-- Remove PV Volume from Old upline
				call Commissions.Customer_Rollup_Volume_Org(
					:ln_Sponsor_id,
					(:ln_Vol_1 * -1));
				
				-- Add PV Volume to New upline
				call Commissions.Customer_Rollup_Volume_Org(
					:pn_Sponsor_id,
					:ln_Vol_1);
			
				-- Update Customer
				update customer
				set Sponsor_id = :pn_Sponsor_id
				   ,Enroller_id = :pn_Enroller_id
				where customer_id = :pn_Customer_id;
					
				-- Update Downline
				update customer
				set Sponsor_id = :ln_Sponsor_id
				   ,Enroller_id = :ln_Enroller_id
				where Sponsor_id = :pn_Customer_id;
			end if;
				
			-- Log Downline Rollup
			insert into customer_log
			select
				 customer_log_id.nextval
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
			
		-- Log Customer Change
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
			,:ln_Customer_log_type_id
			,:pn_Customer_id
			,:ln_Source_Key_id
			,:ln_Source_id
			,:ln_Type_id
			,:ln_Status_id
			,:pn_Sponsor_id
			,:pn_Enroller_id
			,:ln_Country
			,:ld_Comm_status_date
			,:ld_Entry_date
			,:ld_Termination_date
			,:ld_Current_Timestamp
			,:ld_Processed_date);
			
		commit;
		
	end if;
	
	pt_Result = select :ln_Validate as pn_validate from dummy;

end;