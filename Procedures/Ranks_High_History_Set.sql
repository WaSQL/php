drop procedure Commissions.Ranks_High_History_Set;
create procedure Commissions.Ranks_High_History_Set(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id 	integer;
	declare ln_Count		integer;
	
	select count(*)
	into ln_Count
	from period_batch
	where period_id = :pn_Period_id
	and promoted = 1;
	
	if ln_Count > 0 then
		select batch_id
		into ln_Batch_id
		from period_batch
		where period_id = :pn_Period_id
		and promoted = 1;
	
		lc_Cust = 
			select 
				  d.customer_id
				, d.rank_id
			from customer_history d, customer c
			where d.customer_id = c.customer_id
			and d.period_id = :pn_Period_id
		   	and d.batch_id = :ln_Batch_id
		   	and d.rank_id > c.rank_high_id;
		
		-- Set high ranks
		replace customer (customer_id, rank_high_id)
		select 
			  customer_id 
			, rank_id
		from :lc_Cust;
		
		replace customer_history_rank (customer_id, rank_id, period_id, entry_date, customer_rank_type_id)
		select
			  customer_id
			, rank_id
			, :pn_Period_id			as period_id
			, current_timestamp		as entry_date
			, 1						as customer_rank_type_id
		from :lc_Cust;
	   	
	   	commit;
	end if;

end;
