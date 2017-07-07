drop procedure Commissions.sp_Customer_Hier_Clear;
create procedure Commissions.sp_Customer_Hier_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	--if gl_Period_isOpen(:pn_Period_id) = 1 then
		--update customer
		--set level_id = 0;
	--else
		update customer_history
		set hier_level = 0
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
	--end if;
		
	commit;
	
end;
