drop procedure Commissions.Transaction_Add;
create procedure Commissions.Transaction_Add(
					  pn_Customer_id 				integer
					, pn_Transaction_Log_Ref_id		integer
					, pn_Source_Key_id				integer
					, pn_Source_id 					integer
					, pn_Period_id					integer
					, pd_Transaction_date			timestamp
					, pn_Transaction_type_id		integer
					, pn_Transaction_Category_id	integer
					, ps_Currenct_Code				varchar(5)
					, pn_Value_1					double
					, pn_Value_2					double
					, pn_Value_3					double
					, pn_Value_4					double
					, pn_Value_5					double
					, pn_Value_6					double
					, pn_Value_7					double
					, pn_Value_8					double
					, pn_Value_9					double
					, pn_Value_10					double
					, pn_Value_11					double
					, pn_Value_12					double
					, pn_Value_13					double
					, pn_Value_14					double
					, pn_Value_15					double
					, pn_Flag_1						integer
					, pn_Flag_2						integer
					, pn_Flag_3						integer
					, pn_Flag_4						integer
					, pn_Flag_5						integer
					, ps_Note						varchar(500))
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions
AS

begin
	declare ld_Current_Timestamp	timestamp = current_timestamp;
	declare ln_Realtime_Trans 		integer;
	declare ld_Processed_date		timestamp = null;
 	declare le_Error 				nvarchar(200);
 	
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
		end;
 
	if 
		(ifnull(:pn_Value_2 ,0) <> 0 or ifnull(:pn_Value_4,0) <> 0) 
		and ifnull(:pn_Transaction_type_id,4) <> 0 
	then 
		select realtime_trans 
		into ln_Realtime_Trans 
		from period 
		where period_id = 0;
 
		if :ln_Realtime_Trans = 1 then
			call Commissions.Customer_Rollup_Volume(
				:pn_Customer_id,
				:pn_Value_2,
				:pn_Value_4);
				
			ld_Processed_date = :ld_Current_Timestamp;
		end if;
	else 
		ld_Processed_date = :ld_Current_Timestamp;
	end if;

	insert into transaction_log
	select
		 transaction_log_id.nextval							as transaction_log_id
		,:pn_Transaction_Log_Ref_id							as transaction_log_Ref_id
		,:pn_Customer_id									as customer_id
		,:pn_Source_Key_id									as source_Key_id
		,:pn_Source_id										as source_id
		,:pn_Period_id										as period_id
		,:pd_Transaction_date								as transaction_date
		,:pn_Transaction_type_id							as transaction_type_id
		,:pn_Transaction_Category_id						as transaction_category_id
		,:ps_Currenct_Code									as currenct_code
		,:pn_Value_1										as value_1
		,:pn_Value_2										as value_2
		,:pn_Value_3										as value_3
		,:pn_Value_4										as value_4
		,:pn_Value_5										as value_5
		,:pn_Value_6										as value_6
		,:pn_Value_7										as value_7
		,:pn_Value_8										as value_8
		,:pn_Value_9										as value_9
		,:pn_Value_10										as value_10
		,:pn_Value_11										as value_11
		,:pn_Value_12										as value_12
		,:pn_Value_13										as value_13
		,:pn_Value_14										as value_14
		,:pn_Value_15										as value_15
		,:pn_Flag_1											as flag_1
		,:pn_Flag_2											as flag_2
		,:pn_Flag_3											as flag_3
		,:pn_Flag_4											as flag_4
		,:pn_Flag_5											as flag_5
		,:ps_Note											as note
		,:ld_Processed_date									as processed_date
	from dummy;
	
	commit;
 
 end;