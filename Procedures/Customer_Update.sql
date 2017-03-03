drop procedure Commissions.Customer_Update;
create procedure Commissions.Customer_Update(
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
	declare ln_Realtime_Trans 		integer;
	declare ln_Realtime_Rank 		integer;
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
	
	-- Validate Move Request
	ln_Validate = fn_Customer_Validate(:pn_Customer_id, :pn_Sponsor_id, :pn_Enroller_id);
	
	-- Change is Valid
	if :ln_Validate = 1 then
		select realtime_trans, realtime_rank
		into ln_Realtime_Trans, ln_Realtime_Rank
		from period 
		where period_id = 0;
		
		ld_Processed_date = null;
		if :ln_Realtime_Trans = 1 then
			ld_Processed_date = :ld_Current_Timestamp;
		end if;
		
		-- Get Customer Info
		select  source_key_id,    source_id,    type_id,    status_id,    sponsor_id,    enroller_id,    country,    comm_status_date,    entry_date,    termination_date,    vol_1,    vol_12
		into ln_Source_Key_id, ln_Source_id, ln_Type_id, ln_Status_id, ln_Sponsor_id, ln_Enroller_id, ln_Country, ld_Comm_status_date, ld_Entry_date, ld_Termination_date, ln_Vol_1, ln_Vol_12
		from customer
		where customer_id = :pn_Customer_id;
		
		-- Remove Qual Legs Entries
		delete from customer_qual_leg
		where customer_id = :pn_Customer_id;
		
		commit;
		
		--===================================================================================================
		-- Single Move with Downline
		if ifnull(:pn_Downline_Rollup,0) = 0 and ifnull(:pn_Swap_id,0) = 0 then
			ln_Customer_log_type_id = 1;
				
			if :ln_Realtime_Trans = 1 then
				-- Update Customer
				update customer
				set Sponsor_id = :pn_Sponsor_id
				   ,Enroller_id = :pn_Enroller_id
				where customer_id = :pn_Customer_id;
				
				-- Retail Customer
				if :ln_Type_id = 2 or :ln_Type_id = 3 then
					update customer
					set vol_4 = vol_4 - :ln_Vol_1
					where customer_id = :ln_Sponsor_id;
					
					update customer
					set vol_4 = vol_4 + :ln_Vol_1
					where customer_id = :pn_Sponsor_id;
				end if;
					
				-- Remove Org Volume from Old upline
				call Commissions.Customer_Rollup_Volume_Org(
					:ln_Sponsor_id,
					(:ln_Vol_12 * -1));
				
				-- Add Org Volume to New upline
				call Commissions.Customer_Rollup_Volume_Org(
					:pn_Sponsor_id,
					:ln_Vol_12);
			end if;
		end if;
		
		--===================================================================================================
		-- Single Move without Downline
		if ifnull(:pn_Downline_Rollup,0) = 1 and ifnull(:pn_Swap_id,0) = 0 then
			ln_Customer_log_type_id = 2;
				
			if :ln_Realtime_Trans = 1 then
				-- Update Customer
				update customer
				set Sponsor_id = :pn_Sponsor_id
				   ,Enroller_id = :pn_Enroller_id
				   ,vol_12 = vol_1
				where customer_id = :pn_Customer_id;
				
				-- Retail Customer
				if :ln_Type_id = 2 or :ln_Type_id = 3 then
					update customer
					set vol_4 = vol_4 - :ln_Vol_1
					where customer_id = :ln_Sponsor_id;
					
					update customer
					set vol_4 = vol_4 + :ln_Vol_1
					where customer_id = :pn_Sponsor_id;
				end if;
				
				-- Update Downline
				update customer
				set Sponsor_id = :ln_Sponsor_id
				   ,Enroller_id = :ln_Enroller_id
				where Sponsor_id = :pn_Customer_id;
				
				-- Remove PV Volume from Old upline
				call Commissions.Customer_Rollup_Volume_Org(
					:ln_Sponsor_id,
					(:ln_Vol_1 * -1));
				
				-- Add PV Volume to New upline
				call Commissions.Customer_Rollup_Volume_Org(
					:pn_Sponsor_id,
					:ln_Vol_1);
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
		-- Log Request
		if ifnull(:pn_Log,1) = 1 then
			select count(*)
			into ln_Count
			from customer_log
			where customer_id = :pn_Customer_id;
			
			if :ln_Count = 0 then
				call Customer_Log_Add(
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
			
			call Customer_Log_Add(
						 :pn_Customer_id
						,:ln_Customer_log_type_id
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
		end if;
			
		commit;
		
	end if;
	
	pt_Result = select :ln_Validate as validate from dummy;

end;
