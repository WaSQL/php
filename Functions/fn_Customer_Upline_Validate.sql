drop function Commissions.fn_Customer_Upline_Validate;
create function Commissions.fn_Customer_Upline_Validate(
					  pn_Customer_id 		integer
					, pn_Sponsor_id 		integer
					, pn_Enroller_id 		integer)
returns result	integer
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Validate				integer = 0;
	declare ln_Validate_Spon_Enroll	integer;
	declare ln_Validate_Enroll_Org	integer;
	declare ld_Entry_date			timestamp;
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
			result = 0;
		end;
		
	-- Get Customer Info
	select  entry_date
	into ld_Entry_date
	from customer
	where customer_id = :pn_Customer_id;
	
	-- Customer CAN NOT be their own sponsor or enroller
	if :pn_Customer_id != :pn_Sponsor_id and :pn_Customer_id != :pn_Enroller_id then
		-- 14 Day Validation
		if days_between(:ld_Entry_date,current_date) <= 14 then
			-- Enroller MUST be in Sponsor's Upline
			ln_Validate_Spon_Enroll = gl_Validate_Spon_Enroll(:pn_Sponsor_id, :pn_Enroller_id);
			
			-- All Enrollers MUST be Members of this Organizatoin
			select case when count(*) = 0 then 1 else 0 end
			into ln_Validate_Enroll_Org
			from gl_Validate_Enroller_Org(:pn_Customer_id);
			
			if :ln_Validate_Spon_Enroll = 1 and :ln_Validate_Enroll_Org = 1 then
				ln_Validate = 1;
			end if;
		end if;
	end if;
	
	result = :ln_Validate;

end;
