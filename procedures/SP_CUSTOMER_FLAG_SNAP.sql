DROP PROCEDURE SP_CUSTOMER_FLAG_SNAP;
create procedure Commissions.sp_Customer_Flag_Snap
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			20-Jul-2017
*
* @describe		Makes a point in time copy of the Customer_Flag table for the period specified
*
* @param		integer		pn_Period_id 		Commission Period
*
* @example		call Commissions.sp_Customer_Flag_Snap(10);
-------------------------------------------------------*/
(pn_Period_id		integer)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer = gl_period_viewable(:pn_Period_id);
	
	if :ln_Batch_id = 0 then
		insert into customer_history_flag
		select
			 customer_history_flag_id.nextval	as customer_history_flag_id
			,:pn_Period_id						as period_id
			,:ln_Batch_id						as batch_id
			,customer_id						as customer
			,flag_type_id						as flag_type_id
			,flag_value							as flag_value
		from gl_Customer_Flag(0, 0, 0);
	else
		insert into customer_history_flag
		select
			 customer_history_flag_id.nextval	as customer_history_flag_id
			,:pn_Period_id						as period_id
			,:ln_Batch_id						as batch_id
			,customer_id						as customer
			,flag_type_id						as flag_type_id
			,flag_value							as flag_value
		from customer_history_flag
		where period_id = :pn_Period_id
		and batch_id = 0;
	end if;
	  
	commit;
	
	
end;