drop function Commissions.gl_JSON;
CREATE function Commissions.gl_JSON(
					 ps_Json			clob
					,ps_Key				varchar(50))
returns table (ID		Integer
			  ,Key 		nvarchar(500)
			  ,Value	nvarchar(500))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
   	--READS SQL DATA
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		11-May-2017

Purpose:	Returns all key/value pairs from a JSON string

-------------------------------------------------------------------------------- */

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
