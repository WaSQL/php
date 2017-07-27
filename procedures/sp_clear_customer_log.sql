drop procedure clear_customer_log;
create procedure clear_customer_log()
/*--------------------------------------------------
* @author       Del Stirling
* @category     stored procedure
* @date			6/7/2017
*
* @describe     clears the customer log table DO NOT USE IN PRODUCTION
*
* @example      call clear_customer_log()
-------------------------------------------------------*/
	language sqlscript
	sql security invoker
	default schema commissions
as
begin
	truncate table customer_log;
end;

select * from customer_log
call clear_customer_log()
