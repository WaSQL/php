drop function Commissions.gl_JSON;
CREATE function Commissions.gl_JSON
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			11-May-2017
*
* @describe		Returns all key/value pairs from a JSON string
*
* @param		clob		ps_Json		JSON String
* @param		varchar		ps_Key		JSON Key
*
* @return		table
*					Integer		ID
*			  		nvarchar	Key
*			  		nvarchar	Value
*
* @example		select * from gl_JSON('[{"source_id":1,"source_key_id":1,"source_ref_id":40664882,"entry_date":2017-01-01,"bonus_date":2017-01-02,"customer_id":2356263,"customer_type_id":1,"period_id":1,"order_number":123456789,"type_id":1,"category_id":1,"country":USA,"currency":USD,"value_1":1.00,"value_2":2.00,"value_3":3.00,"value_4":4.00,"value_5":5.00}
										{"source_id":1,"source_key_id":1,"source_ref_id":40664882,"entry_date":2017-01-01,"bonus_date":2017-01-02,"customer_id":1001,"customer_type_id":1,"period_id":1,"order_number":123456789,"type_id":1,"category_id":1,"country":USA,"currency":USD,"value_1":1.00,"value_2":2.00,"value_3":3.00,"value_4":4.00,"value_5":5.00}]'
									, 'customer_id');
-------------------------------------------------------*/
(ps_Json			clob
,ps_Key				varchar(50))
returns table (ID		Integer
			  ,Key 		nvarchar(500)
			  ,Value	nvarchar(500))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	declare la_Occur 	integer array;
	declare la_Key 		nvarchar(500) array;
	declare la_Val 		nvarchar(500) array;
	declare ls_Val 		nvarchar(500);
	declare ln_Occur	integer = 1;
	
	while 1=1 do
		select substr_regexpr('("' || :ps_Key || '"[:]["]*)([^"^,^}]*)' FLAG 'i' in :ps_Json occurrence :ln_Occur group 2) Value
		into ls_Val
		from dummy;
		
		if :ls_Val is not null then
			la_Occur[:ln_Occur] = :ln_Occur;
			la_Key[:ln_Occur] = :ps_Key;
			la_Val[:ln_Occur] = map(:ls_Val,'null',null,:ls_Val);
		else
			break;
		end if;
		
		ln_Occur = :ln_Occur + 1;
	end while;
	
	lc_Out = unnest(:la_Occur, :la_Key, :la_Val) as (ID, Key, Value);
	
	return
	select *
	from :lc_Out;
	
end;
