drop procedure Commissions.Req_Qual_Leg_Snap;
create procedure Commissions.Req_Qual_Leg_Snap(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer;
	declare ln_Period_Type_id	integer;
	
	select period_id
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
			insert into req_qual_leg_history
			select
				 :pn_Period_id			as period_id
				,:ln_Batch_id			as batch_id
				,version_id
				,rank_id
				,leg_rank_id
				,leg_rank_count
				,vol_1
				,vol_2
				,vol_3
				,vol_4
			from req_qual_leg;
		else
			insert into req_qual_leg_history
			select
				 :pn_Period_id			as period_id
				,:ln_Batch_id			as batch_id
				,version_id
				,rank_id
				,leg_rank_id
				,leg_rank_count
				,vol_1
				,vol_2
				,vol_3
				,vol_4
			from req_qual_leg_history
			where period_id = :pn_Period_id
			and batch_id = 0;
		end if;
				
		commit;
	end if;

end;
