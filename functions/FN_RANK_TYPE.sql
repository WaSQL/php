DROP FUNCTION COMMISSIONS.FN_RANK_TYPE;
create function commissions.fn_rank_type
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/14/2017
*
* @describe     returns the rank_type table
*
* @param		varchar [locale]
*
* @returns 		table
*				integer rank_type_id
*				nvarchar display_name
*
* @example      select * from commissions.fn_rank_type()
-------------------------------------------------------*/
	(locale 					varchar(10) default 'EN-US')
	returns table (
		RANK_TYPE_ID 		integer
		, DISPLAY_NAME 		nvarchar(20))
	LANGUAGE SQLSCRIPT
	sql security invoker
	default schema commissions
as
BEGIN
	return
	select rank_type_id
		, description as display_name 
	from rank_type
	order by rank_type_id;
END;