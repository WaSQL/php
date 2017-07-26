DROP FUNCTION COMMISSIONS.FN_PERIOD_TYPE;
create function commissions.fn_Period_Type
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/15/2017
*
* @describe     returns the period type table
*
* @param		varchar ps_locale
*
* @returns 		table
*				integer period_type_id
*				nvarchar display_name
*
* @example      select * from commissions.fn_period_type()
-------------------------------------------------------*/
	(ps_Locale varchar(10) default 'EN-US')
	returns table (
		PERIOD_TYPE_ID 	integer
		, DISPLAY_NAME 	nvarchar(500))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
as
BEGIN
	return 
	select 
		  p.period_type_id
		, ifnull(t.translation,p.description) 	as display_name
	from period_type p
		left outer join gl_Translate('period_type', 'description', ifnull(:ps_Locale,'EN-US')) t
		on p.period_type_id = t.foreign_key_id;
END;