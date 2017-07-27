drop function commissions.fn_customer_type;
create function commissions.FN_CUSTOMER_TYPE
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/15/2017
*
* @describe     returns a list of types
*
* @param		varchar [ps_locale]
*
* @returns 		table
*				integer type_id
*				nvarchar description
* @example      select * from commissions.fn_customer_type
-------------------------------------------------------*/
	(
		ps_locale varchar(50) default 'US-en')
	returns table (
		TYPE_ID 		integer
		, DESCRIPTION 	nvarchar(20))
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions

AS
BEGIN
	return 
	select type_id
		, description
	from customer_type
	order by type_id;
END;