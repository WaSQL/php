drop procedure Commissions.Period_Set;
create procedure Commissions.Period_Set(pn_Period_Type_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_id		int;
	declare ln_Count			int;
	
	-- Can Not Close The Current Period
	select count(*)
	into ln_Count
	from Period
	where current_date >= beg_date
	and current_date <= end_date
	and closed_date is null
	and period_type_id = :pn_Period_Type_id;
	
	if :ln_Count = 0 then
		-- Must Have An Open Period
		select count(*)
		into ln_Count
		from Period
		where period_type_id = :pn_Period_Type_id
		and closed_date is null;
		
		if :ln_Count = 1 then
			select period_id
			into ln_Period_id
			from Period
			where period_type_id = :pn_Period_Type_id
			and closed_date is null;
			
			-- Close Current Open Period and create batch zero
			call Period_Close(:ln_Period_id);
			call Period_Batch_Set(:ln_Period_id);
			
			-- Snapshot Customer and all supporting tables
			call Customer_Snap(:ln_Period_id);
			call Customer_Flag_Snap(:ln_Period_id);
		
			-- Special Maintenance For Primary Bonus Type
			if :pn_Period_Type_id = 1 then
				call Req_Qual_Leg_History_Set(:ln_Period_id);
				call Customer_Clear();
			end if;
				
			-- Create The New Period
			call Period_Open(:ln_Period_id);
		end if;
	end if;

end;
