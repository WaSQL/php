drop procedure Commissions.sp_Customer_Rank_Clear;
create procedure Commissions.sp_Customer_Rank_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		update customer
		set rank_id = 0
		  , rank_qual = 0;
	else
		update customer_history
		set rank_id = 0
		  , rank_qual = 0
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
	end if;
		
	commit;
	
end;
