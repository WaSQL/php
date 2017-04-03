drop procedure Commissions.Payout_Unilevel_Clear;
create procedure Commissions.Payout_Unilevel_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	delete
	from Payout_Unilevel
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;
