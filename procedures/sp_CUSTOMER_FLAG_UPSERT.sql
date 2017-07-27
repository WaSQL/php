DROP PROCEDURE CUSTOMER_FLAG_UPSERT;
create procedure commissions.CUSTOMER_FLAG_UPSERT
/*--------------------------------------------------
* @author       Del Stirling
* @category     stored procedure
* @date			4/28/2017
*
* @describe     updates or inserts records into the customer_flag table based on JSON input
*
* @param		nvarchar pn_json
* @out_param	varchar result
* @example      call customer_flag_delete('[{"pn_Customer_id":1247,"pn_Period_id":13,"pn_flag_type_id":2,"pn_flag_value":"USA","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}{"pn_Customer_id":1248,"pn_Period_id":14,"pn_flag_type_id":1,"pn_flag_value":"CHN","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}]', ?);
-------------------------------------------------------*/
	(
	pn_json 		nvarchar(8388607)
	, out result 	varchar(100))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_record_num integer = 0;
	declare ln_column_num integer;
	declare ls_record varchar(5000) = '';
	declare ls_column_name varchar(5000);
	declare ls_column_val varchar(5000);
	declare valid integer = 1;
	declare currcount integer = 0;
	
	declare la_customer_id integer array;
	declare la_customer_flag_id integer array;
	declare la_flag_type_id integer array;
	declare la_flag_value varchar(100) array;
	declare la_beg_date date array;
	declare la_end_date date array;
	
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
	  result = 'Error ' || ::SQL_ERROR_CODE || ' - ' || ::SQL_ERROR_MESSAGE;
	END;
	
	while :ls_record is not null do
		ln_record_num = ln_record_num + 1;
		ln_column_num = 1;
		ls_column_name = '';
		select substr_regexpr('({[^{}]*})' in :pn_json occurrence :ln_record_num)
		into ls_record 
		from dummy;

		while (:ls_column_name is not null) do 
			select substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 1) 
				, substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 2)
			into ls_column_name
				, ls_column_val
			from dummy;
			ln_column_num = :ln_column_num + 1;
			if (:ls_column_name is not null) then
				if lower(:ls_column_val) = 'null' then 
					ls_column_val = null; 
				end if;
				if lower(:ls_column_name) = 'pn_customer_id' then
					la_customer_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'pn_customer_flag_id' then
					la_customer_flag_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'pn_flag_type_id' then
					la_flag_type_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'pn_flag_value' then
					la_flag_value[:ln_record_num] = :ls_column_val;
				elseif lower(:ls_column_name) = 'pn_beg_date' then
					la_beg_date[:ln_record_num] = to_date(ls_column_val);
				elseif lower(:ls_column_name) = 'pn_end_date' then
					la_end_date[:ln_record_num] = to_date(ls_column_val);
				end if;
			end if;
		end while;
		if (:la_customer_flag_id[:ln_record_num] is null) then
			select count(*)
			into currcount
			from customer_flag
			where customer_id = :la_customer_id[:ln_record_num]
				and flag_type_id = :la_flag_type_id[:ln_record_num]
				and ifnull(beg_date, '2000-01-01') <= current_date
				and ifnull(end_date, '2100-01-01') >= current_date;
		end if;
		if currcount > 0 then
			result = 'ERROR - New record conflicts with current flag';
			valid = 0;
		end if;
		
	end while;
	
	if (valid > 0) then 
		value_tab = UNNEST(:la_customer_id,:la_flag_type_id,:la_flag_value,:la_beg_date,:la_end_date) 
			AS ("CUSTOMER_ID","FLAG_TYPE_ID","FLAG_VALUE","BEG_DATE","END_DATE");
			
		replace customer_flag (customer_flag_id, customer_id, flag_type_id, flag_value, beg_date, end_date)
		select ifnull((select curr.customer_flag_id from customer_flag curr where curr.customer_id = val.customer_id and curr.flag_type_id = val.flag_type_id), customer_flag_id.nextval)
			, val.customer_id
			, val.flag_type_id
			, val.flag_value
			, val.beg_date
			, val.end_date
		from :value_tab val
		where customer_id = val.customer_id
			and flag_type_id = val.flag_type_id;
		result = 'success';
	end if;
END;