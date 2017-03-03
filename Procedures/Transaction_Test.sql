drop procedure Commissions.Transaction_Test;
create procedure Commissions.Transaction_Test(
					 pn_Customer_id	integer
					,pn_Period_id	integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ls_PV_date	varchar(20);
	declare cursor lc_Trans for
	select t.*
	from transaction_log_test t,
		HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*
		             from customer a
		           )
			Start where customer_id in (:pn_Customer_id)) c
	where t.customer_id = c.customer_id
	and t.period_id = :pn_Period_id
	and t.transaction_type_id <> 0
	order by c.hierarchy_level;
	
	update transaction_log
	set processed_date = null
	where processed_date is not null;
	
	delete from transaction_log
	where transaction_log_id in (
	select t.transaction_log_id
	from transaction_log t,
		HIERARCHY ( 
		 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, a.*
		             from customer a
		           )
			Start where customer_id in (:pn_Customer_id)) c
	where t.customer_id = c.customer_id
	and t.period_id = :pn_Period_id
	and t.transaction_type_id <> 0);
	
	commit;
	
	for lr_Trans as lc_Trans do
		call Transaction_Add(
			  :lr_Trans.Customer_id
			, :lr_Trans.Transaction_Log_Ref_id
			, :lr_Trans.Source_Key_id
			, :lr_Trans.Source_id
			, :lr_Trans.Period_id
			, :lr_Trans.Transaction_date
			, :lr_Trans.Transaction_type_id
			, :lr_Trans.Transaction_Category_id
			, :lr_Trans.Currency_Code
			, :lr_Trans.Value_1
			, :lr_Trans.Value_2	
			, :lr_Trans.Value_3
			, :lr_Trans.Value_4
			, :lr_Trans.Value_5
			, :lr_Trans.Value_6
			, :lr_Trans.Value_7
			, :lr_Trans.Value_8
			, :lr_Trans.Value_9
			, :lr_Trans.Value_10
			, :lr_Trans.Value_11
			, :lr_Trans.Value_12
			, :lr_Trans.Value_13
			, :lr_Trans.Value_14
			, :lr_Trans.Value_15
			, :lr_Trans.Flag_1
			, :lr_Trans.Flag_2
			, :lr_Trans.Flag_3
			, :lr_Trans.Flag_4
			, :lr_Trans.Flag_5
			, :lr_Trans.Note);
	end for;

end;
