drop procedure Commissions.sp_Period_Set;
create procedure Commissions.sp_Period_Set(pn_Period_Type_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_id		integer;
	declare ln_Count			integer;
	
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
			
			-- Close Current Open Period 
			-- Open New and create batch zero
			call sp_Period_Close(:ln_Period_id);
			call sp_Period_Open(:ln_Period_id);
			call sp_Period_Batch_Set(:ln_Period_id);
		
			-- Special Maintenance For Primary Bonus Type
			if :pn_Period_Type_id = 1 then
				call sp_Customer_Clear(:ln_Period_id, gl_Period_Viewable(:ln_Period_id));
			end if;
			
			commit;
		end if;
	end if;

end;
