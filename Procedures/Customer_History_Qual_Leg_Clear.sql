drop procedure Commissions.Customer_History_Qual_Leg_Clear;
create procedure Commissions.Customer_History_Qual_Leg_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	delete
	from customer_history_qual_leg
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;
