drop function Commissions.fn_Glossary;
CREATE function Commissions.fn_Glossary(
						pn_Glossary_id	integer default 0)
returns table (
			 glossary_id 		integer
			,term				nvarchar(200) 
			,definition			clob)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		8-May-2017

Purpose:	Returns a resultset of glossary definitions

-------------------------------------------------------------------------------- */

begin
	return
	select
		 glossary_id
		,term
		,definition
	from glossary
	where glossary_id = map(ifnull(pn_Glossary_id,0),0,glossary_id,pn_Glossary_id);
	
end;
