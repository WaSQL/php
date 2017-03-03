drop trigger Commissions.Trg_BI_Customer_Log;
create trigger Commissions.Trg_BI_Customer_Log 
BEFORE INSERT ON Commissions.Customer_Log 
REFERENCING NEW ROW TRG_NEW FOR EACH ROW 
begin
	declare ln_Realtime_Trans 	integer;
	declare ln_Old_Sponsor_id	integer;
	declare ln_Old_Enroller_id	integer;
	declare ln_Old_Vol_12		double;
 	declare le_Error 			nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
		end;

	select realtime_trans 
	into ln_Realtime_Trans 
	from period 
	where period_id = 0;

	if :ln_Realtime_Trans = 1 then 
		--===================================================================================================
		-- Single Move with Downline
		if :trg_new.customer_log_type_id = 1 then
			-- Get Old Info
			select Sponsor_id, Enroller_id, vol_12
			into ln_Old_Sponsor_id, ln_Old_Enroller_id, ln_Old_Vol_12
			from customer
			where customer_id = :trg_new.customer_id;
			
			-- Remove Org Volume from Old upline
			call Commissions.Customer_Rollup_Volume_Org(
				:ln_Old_Sponsor_id,
				(:ln_Old_Vol_12 * -1));
			
			-- Add Org Volume to New upline
			call Commissions.Customer_Rollup_Volume_Org(
				:trg_new.sponsor_id,
				:ln_Old_Vol_12);
				
			-- Update Customer
			update customer
			set Sponsor_id = :trg_new.sponsor_id
			   ,Enroller_id = :trg_new.enroller_id
			where customer_id = :trg_new.customer_id;
			
			trg_new.processed_date = current_timestamp;
		end if;
		
		--===================================================================================================
		-- Single Move without Downline
		if :trg_new.customer_log_type_id = 2 then
			-- Get Old Info
			select Sponsor_id, Enroller_id, vol_1
			into ln_Old_Sponsor_id, ln_Old_Enroller_id, ln_Old_Vol_12
			from customer
			where customer_id = :trg_new.customer_id;
			
			-- Remove Org Volume from Old upline
			call Commissions.Customer_Rollup_Volume_Org(
				:ln_Old_Sponsor_id,
				(:ln_Old_Vol_12 * -1));
			
			-- Add Org Volume to New upline
			call Commissions.Customer_Rollup_Volume_Org(
				:trg_new.sponsor_id,
				:ln_Old_Vol_12);
				
			-- Update Customer
			update customer
			set Sponsor_id = :trg_new.sponsor_id
			   ,Enroller_id = :trg_new.enroller_id
			where customer_id = :trg_new.customer_id;
			
			-- Rollup Downline
			update customer
			set Sponsor_id = :ln_Old_Sponsor_id
			   ,Enroller_id = :ln_Old_Enroller_id
			where Sponsor_id = :trg_new.customer_id;
			
			trg_new.processed_date = current_timestamp;
		end if;
		
		--===================================================================================================
		if :trg_new.customer_log_type_id = 3 then
		
		end if;
		
		--===================================================================================================
		if :trg_new.customer_log_type_id = 4 then
			trg_new.processed_date = current_timestamp;
		end if;
	end if;

end;