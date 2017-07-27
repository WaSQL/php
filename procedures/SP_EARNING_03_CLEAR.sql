drop procedure Commissions.sp_Earning_03_Clear;
create procedure Commissions.sp_Earning_03_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin

	update customer_history
	set Earning_3 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from Earning_03
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;

end;
