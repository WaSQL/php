drop procedure Commissions.sp_Period_Final;
create procedure Commissions.sp_Period_Final(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		6-Jul-2017

Purpose:	Finalizes a Period; Sets Dates, Sets High Rank Histories, Runs Inactive drop

-------------------------------------------------------------------------------- */

begin
	declare ln_Close	integer;
	declare ln_Lock		integer;
	declare ln_Final	integer;
	
	select map(closed_date, null, 0, 1), map(locked_date, null, 0, 1), map(final_date, null, 0, 1)
	into ln_Close, ln_Lock, ln_Final
	from period
	where period_id = :pn_Period_id;
	
	-- Only closed periods can be finalized
	if :ln_Close = 1 then
		-- Lock period
		if :ln_Lock = 0 then
			update period
			set locked_date = current_timestamp
			where period_id = :pn_Period_id;
		
			commit;
		end if;
		
		-- Run Final Period Cleanup
		call sp_Rank_High_Set(:pn_Period_id);
		-- PH call Inactive Drop
		
		-- Finalize Period
		if :ln_Final = 0 then
			update period
			set final_date = current_timestamp
			where period_id = :pn_Period_id;
		
			commit;
		end if;
	end if;
end;
