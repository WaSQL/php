drop function Commissions.gl_Translate;
CREATE function Commissions.gl_Translate
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Global Function
* @date			12-May-2017
*
* @describe		Returns a resultset of Translations
*
* @param		nvarchar	ps_Table_Name 		Table Name for Translating
* @param		nvarchar	ps_Column_Name 		Column Name for Translating
* @param		varchar		ps_Locale 			Locale to Translate to
*
* @return		table
*					integer		translate_id
*					varchar		locale
*					nvarchar	table_name
*					nvarchar	column_Name
*					integer		foreign_key_id
*					nvarchar	translation
*
* @example		select ct.*, ifnull(t.translation, ct.description) as translated
*				from customer_type ct
*	 				 left outer join gl_Translate('customer_type', 'description', 'ja-JP') t
*						on ct.type_id = t.foreign_key_id;
-------------------------------------------------------*/
(ps_Table_Name	nvarchar(100)
,ps_Column_Name	nvarchar(100)
,ps_Locale		varchar(7))
returns table (
			 translate_id 		integer
			,locale				varchar(7) 
			,table_name			nvarchar(100)
			,column_Name		nvarchar(100)
			,foreign_key_id		integer
			,translation		nvarchar(500))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	return
	select *
	from translate
	where lower(table_name) = lower(:ps_Table_Name)
	and lower(column_name) = lower(:ps_Column_Name)
	and lower(locale) = lower(:ps_Locale);
	
end;
