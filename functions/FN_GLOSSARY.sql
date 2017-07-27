drop function Commissions.fn_Glossary;
CREATE function Commissions.fn_Glossary
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Function
* @date			8-May-2017
*
* @describe		Returns a resultset of glossary definitions
*
* @param		integer		[pn_Glossary_id] 	Table Name for Translating
* @param		varchar		[ps_Locale]			Locale to Translate to
*
* @return		table
*					integer		glossary_id
*					nvarchar	term 
*					clob		definition
*
* @example		select * from fn_Glossary();
-------------------------------------------------------*/
(pn_Glossary_id	integer default 0
,ps_Locale 		varchar(10) default 'EN-US')
returns table (
			 glossary_id 		integer
			,term				nvarchar(200) 
			,definition			clob)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	return
	select
		 g.glossary_id
		,g.term
		,ifnull(t.translation,g.definition) as definition
	from glossary g
		left outer join gl_Translate('glossary', 'definition', ifnull(:ps_Locale,'EN-US')) t
		on g.glossary_id = t.foreign_key_id
	where glossary_id = map(ifnull(pn_Glossary_id,0),0,glossary_id,pn_Glossary_id);
	
end;
