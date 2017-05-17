drop procedure Commissions.Customer_History_Rank_Clear;
create procedure Commissions.Customer_History_Rank_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer_history
	set rank_id = 0
	  , rank_qual = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	commit;
	
end;