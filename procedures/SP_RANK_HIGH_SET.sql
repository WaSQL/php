DROP PROCEDURE SP_RANK_HIGH_SET;
create procedure Commissions.sp_Rank_High_Set(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		6-Jul-2017

Purpose:	Saves the customer's Highest Rank and logs it into their history

-------------------------------------------------------------------------------- */

begin
	declare ln_Period_Batch_id	integer = gl_Period_Viewable(:pn_Period_id);
	declare ln_Close			integer;
	declare ln_Lock				integer;
	declare ln_Final			integer;
	
	select map(closed_date, null, 0, 1), map(locked_date, null, 0, 1), map(final_date, null, 0, 1)
	into ln_Close, ln_Lock, ln_Final
	from period
	where period_id = :pn_Period_id;
	
	-- Only closed and locked periods can be processed
	if :ln_Close = 1 and :ln_Lock = 1 then --and :ln_Final = 0 then
		lc_Cust = 
			select 
				  d.customer_id
				, d.period_id
				, d.rank_id			as Curr_Rank_id
				, h.rank_id			as Hist_Rank_id
			from gl_customer(:pn_Period_id, :ln_Period_Batch_id) d, (select customer_id, max(rank_id) as rank_id from customer_rank_history group by customer_id) h
			where d.customer_id = h.customer_id
		   	and d.rank_id > h.rank_id;
		
		-- Set high ranks
		replace customer (customer_id, rank_high_id)
		select 
			  customer_id 
			, Curr_Rank_id
		from :lc_Cust;
		
		replace customer_rank_history (customer_id, rank_id, period_id, entry_date, customer_rank_type_id)
		select 
			  d.customer_id
			, r.rank_id
			, d.period_id			as period_id
			, current_timestamp		as entry_date
			, 1						as customer_rank_type_id
		from :lc_Cust d, rank r
	   	where d.hist_rank_id < r.rank_id
	   	and d.curr_rank_id >= r.rank_id;
	   	
	   	commit;
	end if;

end;