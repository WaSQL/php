drop procedure Commissions.Commission_History_Clear;
create procedure Commissions.Commission_History_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	call period_batch_clear(:pn_Period_id, :pn_Period_Batch_id);
	call customer_history_clear(:pn_Period_id, :pn_Period_Batch_id);
	call customer_history_qual_leg_clear(:pn_Period_id, :pn_Period_Batch_id);
	
end;
