drop procedure Commissions.sp_Earning_02_Clear;
create procedure Commissions.sp_Earning_02_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer_history
	set Earning_2 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from Earning_02
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;
