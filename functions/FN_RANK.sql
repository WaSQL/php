DROP FUNCTION COMMISSIONS.FN_RANK;
create function commissions.FN_RANK
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/15/2017
*
* @describe     returns the rank table
*
* @param		varchar [locale]
*
* @returns 		table
*				integer rank_id
*				varchar display_name
*
* @example      select * from commissions.fn_rank()
-------------------------------------------------------*/
	(locale 					varchar(10) default 'EN-US')
	returns table (RANK_ID 	integer
		, DISPLAY_NAME 		varchar(25))
	LANGUAGE SQLSCRIPT
	sql security invoker
	default schema commissions
as
BEGIN
	return
	select rank_id
		, description as display_name
	from rank
	order by rank_id;
END;