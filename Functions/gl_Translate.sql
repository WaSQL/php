drop function Commissions.gl_Translate;
CREATE function Commissions.gl_Translate(
						 ps_Table_Name	nvarchar(100)
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
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of Translations

-------------------------------------------------------------------------------- */

begin
	return
	select *
	from translate
	where lower(table_name) = lower(:ps_Table_Name)
	and lower(column_name) = lower(:ps_Column_Name)
	and lower(locale) = lower(:ps_Locale);
	
end;
