drop procedure Commissions.sp_Cap_Req_Snap;
create procedure Commissions.sp_Cap_Req_Snap(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer;
	declare ln_Period_Type_id	integer;
	
	select period_type_id
	into ln_Period_Type_id
	from Period
	where period_id = :pn_Period_id;
	
	-- Only Take a snapshot for Primary periods
	if :ln_Period_Type_id = 1 then
	
		select max(batch_id)
		into ln_Batch_id
		from period_batch 
		where period_id = :pn_Period_id;
		
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
