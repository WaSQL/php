drop procedure Commissions.Payout_Retail_Clear;
create procedure Commissions.Payout_Retail_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin

	update customer_history
	set payout_3 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	delete
	from payout_retail
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;

end;
