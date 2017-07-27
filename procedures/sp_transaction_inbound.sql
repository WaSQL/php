drop procedure commissions.sp_transaction_inbound;
create procedure commissions.sp_transaction_inbound
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/15/2017
*
* @describe     returns rank history
*
* @param		integer pn_customer_id
*
* @returns 		table
* @example      select * from commissions.customer_history_rank(1001)
-------------------------------------------------------*/
(
pn_json			varchar(8388607)
, out result 	table(transaction_id integer))
language sqlscript
default schema commissions
as 
begin
	declare ln_record_num 			integer = 0;
	declare ln_column_num 			integer;
	declare ls_record 				varchar(5000) = '';
	declare ls_column_name 			varchar(5000);
	declare ls_column_val 			varchar(5000);
	declare valid 					integer = 1;
	declare currcount 				integer = 0;
	declare ln_ref_order			integer;
	declare ln_transaction_id		integer;
	
	declare la_transaction_id				integer array;
	declare la_transaction_ref_id			integer array;
	declare la_transaction_entry_date		date array;
	declare la_transaction_processed_date	date array;
	declare la_source_key_id				integer array;
	declare la_source_id					integer array;
	declare la_entry_date					date array;
	declare la_bonus_date					date array;
	declare la_customer_id					integer array;
	declare la_customer_type_id				integer array;
	declare la_period_id					integer array;
	declare la_order_number					integer array;
	declare la_type_id						integer array;
	declare la_category_id					integer array;
	declare la_country						varchar(4) array;
	declare la_currency						varchar(4) array;
	declare la_value_1						decimal(18,8) array;
	declare la_value_2						decimal(18,8) array;
	declare la_value_3						decimal(18,8) array;
	declare la_value_4						decimal(18,8) array;
	declare la_value_5						decimal(18,8) array;
	
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
	END;

	while 1=1 do
		--loop through each record
		ln_record_num = ln_record_num + 1;
		ln_column_num = 1;
		ls_column_name = '';
		select substr_regexpr('({[^{}]*})' in :pn_json occurrence :ln_record_num)
		into ls_record 
		from dummy;
		if ls_record is null then 
			break;
		end if;
		select transaction_id.nextval
		into ln_transaction_id
		from dummy;
		la_transaction_id[:ln_record_num] = :ln_transaction_id;
		la_transaction_entry_date[:ln_record_num] = current_date;
		
		while (:ls_column_name is not null) do 
			--loop through each column
			select substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9.-]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 1) 
				, substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9.-]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 2)
			into ls_column_name
				, ls_column_val
			from dummy;
			ln_column_num = :ln_column_num + 1;
			if (:ls_column_name is not null) then
				if lower(:ls_column_val) = 'null' then 
					ls_column_val = null; 
				end if;
				if lower(:ls_column_name) = 'source_key_id' then
					la_source_key_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'source_id' then
					la_source_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'source_ref_id' then
					select max(transaction_id)
					into ln_ref_order
					from transaction
					where order_number = to_number(:ls_column_val);
					la_transaction_ref_id[:ln_record_num] = :ln_ref_order;
				elseif lower(:ls_column_name) = 'entry_date' then
					la_entry_date[:ln_record_num] = to_date(:ls_column_val);
				elseif lower(:ls_column_name) = 'bonus_date' then
					la_bonus_date[:ln_record_num] = to_date(:ls_column_val);
				elseif lower(:ls_column_name) = 'customer_id' then
					la_customer_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'customer_type_id' then
					la_customer_type_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'period_id' then
					la_period_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'order_number' then
					la_order_number[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'type_id' then
					la_type_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'category_id' then
					la_category_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'country' then
					la_country[:ln_record_num] = :ls_column_val;
				elseif lower(:ls_column_name) = 'currency' then
					la_currency[:ln_record_num] = :ls_column_val;
				elseif lower(:ls_column_name) = 'value_1' then
					la_value_1[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'value_2' then
					la_value_2[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'value_3' then
					la_value_3[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'value_4' then
					la_value_4[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'value_5' then
					la_value_5[:ln_record_num] = to_number(:ls_column_val);		
				end if;			
			end if;
		end while; 
		--columns
	end while; 
	--records

	value_tab = UNNEST(
		:la_transaction_id
		, :la_transaction_ref_id
		, :la_transaction_entry_date
		, :la_transaction_processed_date
		, :la_source_key_id
		, :la_source_id
		, :la_entry_date
		, :la_bonus_date
		, :la_customer_id
		, :la_customer_type_id
		, :la_period_id
		, :la_order_number
		, :la_type_id
		, :la_category_id
		, :la_country
		, :la_currency
		, :la_value_1
		, :la_value_2
		, :la_value_3
		, :la_value_4
		, :la_value_5)
		AS ("TRANSACTION_ID"
			,"TRANSACTION_REF_ID"
			,"TRANSACTION_ENTRY_DATE"
			,"TRANSACTION_PROCESSED_DATE"
			,"SOURCE_KEY_ID"
			,"SOURCE_ID"
			,"ENTRY_DATE"
			,"BONUS_DATE"
			,"CUSTOMER_ID"
			,"CUSTOMER_TYPE_ID"
			,"PERIOD_ID"
			,"ORDER_NUMBER"
			,"TYPE_ID"
			,"CATEGORY_ID"
			,"COUNTRY"
			,"CURRENCY"
			,"VALUE_1"
			,"VALUE_2"
			,"VALUE_3"
			,"VALUE_4"
			,"VALUE_5");
		
	insert into transaction_log
		(transaction_id
		, transaction_ref_id
		, transaction_entry_date
		, transaction_processed_date
		, source_key_id
		, source_id
		, entry_date
		, bonus_date
		, customer_id
		, customer_type_id
		, period_id
		, order_number
		, type_id
		, category_id
		, country
		, currency
		, value_1
		, value_2
		, value_3
		, value_4
		, value_5)
	select transaction_id
		, transaction_ref_id
		, transaction_entry_date
		, transaction_processed_date
		, source_key_id
		, source_id
		, entry_date
		, bonus_date
		, customer_id
		, customer_type_id
		, period_id
		, order_number
		, type_id
		, category_id
		, country
		, currency
		, value_1
		, value_2
		, value_3
		, value_4
		, value_5
	from :value_tab;
	commit;
	result = unnest(:la_transaction_id) as ("TRANSACTION_ID");
end;
