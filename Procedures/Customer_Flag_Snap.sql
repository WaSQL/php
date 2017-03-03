drop procedure Commissions.Customer_Flag_Snap;
create procedure Commissions.Customer_Flag_Snap(
					  pn_Period_id		int
					 ,pn_Batch_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	insert into customer_history_flag
	select
		 :pn_Period_id				as period_id
		,:pn_Batch_id				as batch_id
		,customer_id				as customer
		,flag_type_id				as flag_type_id
		,flag_value					as flag_value
		,beg_date					as beg_date
		,end_date					as end_date
	from customer_flag;
	  
	commit;
	
	
end;