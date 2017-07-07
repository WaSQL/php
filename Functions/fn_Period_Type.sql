drop function commissions.fn_Period_Type;
create function commissions.fn_Period_Type(ps_Locale varchar(10) default 'EN-US')
	returns table (PERIOD_TYPE_ID integer
		, DISPLAY_NAME nvarchar(20))
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
