drop procedure Commissions.Payout_Power3_Clear;
create procedure Commissions.Payout_Power3_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer_history
	set payout_2 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from payout_power3
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;
