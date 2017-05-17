drop procedure Commissions.Customer_Ranks_History_Set;
create procedure Commissions.Customer_Ranks_History_Set(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id 	integer;
	declare ln_Count		integer;
	
	select batch_id
	into ln_Batch_id
	from period_batch
	where period_id = :pn_Period_id
	and viewable = 1;

	lc_Cust = 
		select 
			  d.customer_id
			, d.period_id
			, d.rank_id			as Curr_Rank_id
			, h.rank_id			as Hist_Rank_id
		from customer_history d, customer_rank_history h
		where d.customer_id = h.customer_id
		and d.period_id = :pn_Period_id
	   	and d.batch_id = :ln_Batch_id
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

end;