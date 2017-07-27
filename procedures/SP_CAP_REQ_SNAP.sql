drop procedure Commissions.sp_Cap_Req_Snap;
create procedure Commissions.sp_Cap_Req_Snap
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			20-Jul-2017
*
* @describe		Makes a point in time copy of the cap_req_template for the period specified
*
* @param		integer		pn_Period_id 		Commission Period
*
* @example		call Commissions.sp_Cap_Req_Snap(10);
-------------------------------------------------------*/
(pn_Period_id		integer)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer = gl_period_viewable(:pn_Period_id);
	declare ln_Period_Type_id	integer	= gl_period_type(:pn_Period_id);
	
	-- Only Take a snapshot for Primary periods
	if :ln_Period_Type_id = 1 then
		if :ln_Batch_id = 0 then
			insert into cap_req
			select
				 :pn_Period_id			as period_id
				,:ln_Batch_id			as batch_id
				,rank_id
				,cap_type_id
				,value_1
				,value_2
			from cap_req_template;
		else
			insert into cap_req
			select
				 :pn_Period_id			as period_id
				,:ln_Batch_id			as batch_id
				,rank_id
				,cap_type_id
				,value_1
				,value_2
			from cap_req
			where period_id = :pn_Period_id
			and batch_id = 0;
		end if;
				
		commit;
	end if;
end;
