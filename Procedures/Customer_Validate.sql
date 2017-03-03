drop procedure Commissions.Customer_Validate;
create procedure Commissions.Customer_Validate(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer
					, out pt_Result 		table (pn_Validate	integer))
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions 
	READS SQL DATA
AS

begin
	declare ln_Validate				integer = 0;
	declare ln_Source_Key_id		integer;
	declare ln_Source_id			integer;
	declare ln_Type_id				integer;
	declare ln_Status_id			integer;
	declare ln_Sponsor_id			integer;
	declare ln_Enroller_id			integer;
	declare ls_Country				varchar(5);
	declare ld_Comm_status_date		timestamp;
	declare ld_Entry_date			timestamp;
	declare ld_Termination_date		timestamp;
	declare ln_Vol_1				double;
	declare ln_Vol_12				double;
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
			pt_Result = select 0 as pn_validate from dummy;
		end;
		
	-- Get Customer Info
	select  source_key_id,    source_id,    type_id,    status_id,    sponsor_id,    enroller_id,    country,    comm_status_date,    entry_date,    termination_date,    vol_1,    vol_12
	into ln_Source_Key_id, ln_Source_id, ln_Type_id, ln_Status_id, ln_Sponsor_id, ln_Enroller_id, ls_Country, ld_Comm_status_date, ld_Entry_date, ld_Termination_date, ln_Vol_1, ln_Vol_12
	from customer
	where customer_id = :pn_Customer_id;
	
	-- Customer CAN NOT be their own sponsor or enroller
	if :pn_Customer_id != :pn_Sponsor_id and :pn_Customer_id != :pn_Enroller_id then
		-- 14 Day Validation
		if days_between(:ld_Entry_date,current_date) <= 14 then
			-- Enroller MUST be in sponsor's upline
			ln_Validate = Validate_Spon_Enroll(:pn_Sponsor_id, :pn_Enroller_id);
		end if;
	end if;
	
	pt_Result = select :ln_Validate as pn_validate from dummy;

end;
