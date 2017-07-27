DROP PROCEDURE SP_CUSTOMER_HIER_CLEAR;
create procedure Commissions.sp_Customer_Hier_Clear
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			20-Jul-2017
*
* @describe		Clears all Customer Hierarchy values
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @example		call Commissions.sp_Customer_Hier_Clear(10, 0);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	--if gl_Period_isOpen(:pn_Period_id) = 1 then
		--update customer
		--set level_id = 0;
	--else
		update customer_history
		set  hier_level = 0
			,hier_rank = 0
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
	--end if;
		
	commit;
	
end;