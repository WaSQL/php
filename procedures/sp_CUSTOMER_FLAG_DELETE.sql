DROP PROCEDURE CUSTOMER_FLAG_DELETE;
create procedure commissions.CUSTOMER_FLAG_DELETE
/*--------------------------------------------------
* @author       Del Stirling
* @category     stored procedure
* @date			5/2/2017
*
* @describe     Deletes records in the customer_flag table based on JSON input
*
* @param		nvarchar pn_json
* @out_param	varchar ps_result
* @example      call customer_flag_delete('[{"pn_Customer_id":1247,"pn_Period_id":13,"pn_flag_type_id":2,"pn_flag_value":"USA","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}{"pn_Customer_id":1248,"pn_Period_id":14,"pn_flag_type_id":1,"pn_flag_value":"CHN","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}]', ?);
-------------------------------------------------------*/
	(
	pn_json 			nvarchar(5000)
	, out ps_result 	varchar(100))
	
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
	
	declare la_customer_id integer array;
	declare la_flag_type_id integer array;
	declare la_flag_value varchar(100) array;
	declare la_beg_date date array;
	declare la_end_date date array;
	
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
	  ps_result = 'Error ' || ::SQL_ERROR_CODE || ' - ' || ::SQL_ERROR_MESSAGE;
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
	end while;
	value_tab = UNNEST(:la_customer_id,:la_flag_type_id,:la_flag_value,:la_beg_date,:la_end_date) 
		AS ("CUSTOMER_ID","FLAG_TYPE_ID","FLAG_VALUE","BEG_DATE","END_DATE");

	delete 
	from customer_flag
	where exists (select customer_id, flag_type_id from :value_tab t where t.customer_id = customer_flag.customer_id and t.flag_type_id = customer_flag.flag_type_id);
	
	ps_result :='success';
END;