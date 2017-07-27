DROP PROCEDURE SP_CUSTOMER_HIER_SET;
create procedure Commissions.sp_Customer_Hier_Set
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			20-Jul-2017
*
* @describe		Sets all Customer Hierarchy values
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @example		call Commissions.sp_Customer_Hier_Set(10, 0);
-------------------------------------------------------*/
(pn_Period_id		int
,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_level = current_timestamp
      ,end_date_level = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		/*replace customer (customer_id, hier_level)
		select
			 node_id 			as customer_id
			,hierarchy_level	as hier_level
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id
			             from customer)
	    		Start where customer_id = 1)
	    order by node_id;*/
	else
	   	replace customer_history (period_id, batch_id, customer_id, hier_level, hier_rank)
		select
			 period_id
			,batch_id
			,node_id 			as customer_id
			,hierarchy_level	as hier_level
			,hierarchy_rank		as hier_rank
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, period_id, batch_id
			             from customer_history
			             where period_id = :pn_Period_id
			             and batch_id = :pn_Period_Batch_id
			             order by customer_id)
	    		Start where customer_id = 3);
	end if;
	
	commit;

   	Update period_batch
   	Set end_date_level = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;