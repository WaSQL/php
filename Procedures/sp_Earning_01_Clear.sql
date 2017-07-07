drop procedure Commissions.sp_Earning_01_Clear;
create procedure Commissions.sp_Earning_01_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer_history
	set Earning_1 = 0
	,Earning_1_cap = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from Earning_01
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;
