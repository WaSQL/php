drop function Commissions.gl_Locale;
CREATE function Commissions.gl_Locale()
returns table (
			 locale				varchar(7) 
			,display_name		nvarchar(100))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		17-May-2017

Purpose:	Returns a resultset of Locales

-------------------------------------------------------------------------------- */

begin
	return
	select 
		 locale
		,description_local	as display_name
	from (
		select 
			 locale
			,description
			,description_local
			,case
				when locale_id in (6) then 1
				else 99 end	as orderby
		from locale
		order by orderby, description);
	
end;
