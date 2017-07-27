DROP PROCEDURE SP_EARNING_04_CLEAR;
create procedure Commissions.sp_Earning_04_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer_history
	set Earning_4 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from Earning_04
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;