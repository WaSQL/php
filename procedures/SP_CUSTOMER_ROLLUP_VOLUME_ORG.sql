DROP PROCEDURE SP_CUSTOMER_ROLLUP_VOLUME_ORG;
create procedure Commissions.sp_Customer_Rollup_Volume_Org(
						 pn_Customer_id 		integer
						,pn_Org					double)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Customer_id		integer = :pn_Customer_id;
	declare ln_Realtime_Rank	integer;
	
	select realtime_rank
	into ln_Realtime_Rank
	from period
	where period_id = 0;
	
	-- Set OGV
	loop
		if :ln_Customer_id = 3 then
			break;
		end if;
		
		-- Update Org Volume by rolling up PV
    	replace customer (customer_id, vol_13)
        select
            customer_id       	as customer_id
           ,vol_13 + :pn_Org	as vol_13
        from customer c
		Where type_id = 1
		and customer_id = :ln_Customer_id;

		select sponsor_id
		into ln_Customer_id
		from customer
		where customer_id = :ln_Customer_id;
		
	end loop;

end;