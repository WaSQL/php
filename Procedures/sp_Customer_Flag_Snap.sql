drop procedure Commissions.sp_Customer_Flag_Snap;
create procedure Commissions.sp_Customer_Flag_Snap(
					  pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer;
	
	select max(batch_id)
	into ln_Batch_id
	from period_batch 
	where period_id = :pn_Period_id;
	
	if :ln_Batch_id = 0 then
		insert into customer_history_flag
		select
			 customer_history_flag_id.nextval	as customer_history_flag_id
			,:pn_Period_id						as period_id
			,:ln_Batch_id						as batch_id
			,customer_id						as customer
			,flag_type_id						as flag_type_id
			,flag_value							as flag_value
		from gl_Customer_Flag(0, 0, 0);
	else
		insert into customer_history_flag
		select
			 customer_history_flag_id.nextval	as customer_history_flag_id
			,:pn_Period_id						as period_id
			,:ln_Batch_id						as batch_id
			,customer_id						as customer
			,flag_type_id						as flag_type_id
			,flag_value							as flag_value
		from customer_history_flag
		where period_id = :pn_Period_id
		and batch_id = 0;
	end if;
	  
	commit;
	
	
end;
