DROP PROCEDURE SP_CUSTOMER_QUAL_LEG_CLEAR;
create procedure Commissions.sp_Customer_Qual_Leg_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		delete
		from customer_qual_leg;
	else
		delete
		from customer_history_qual_leg
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
	end if;
		  
	commit;
	
end;