drop Procedure Commissions.Volume_Org_History_Set;
create Procedure Commissions.Volume_Org_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
    declare ln_max      integer;
    declare ln_x        integer;
    
	Update period_batch
	Set beg_date_volume_org = current_timestamp
      ,end_date_volume_org = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
   	-- Get Period Tree Snapshot
   	lc_Period_Tree =
		select customer_id, sponsor_id, period_id, batch_id, round(vol_1+vol_4,2) as vol_1
		from customer_history
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
    
    -- Add Tree Level to Snaphot
    lc_dist = 
		select
			 node_id 			as customer_id
			,period_id    		as period_id
			,batch_id 			as batch_id
			,vol_1				as vol_1
			,hierarchy_level	as level_id
		from HIERARCHY ( 
			 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, period_id, batch_id, vol_1
			             from :lc_Period_Tree
			             order by customer_id)
	    		Start where customer_id = 3);
        
    -- Get Max Level
    select max(level_id)
    into ln_max
    from :lc_dist;
    
    -- Loop through all tree levels from bottom to top
    for ln_x in reverse 0..:ln_max do
    	-- Update Org Volume by rolling up PV
    	replace customer_history (period_id, batch_id, customer_id, vol_13)
        select
            d.period_id             							as period_id
           ,d.batch_id 			 								as batch_id
           ,d.customer_id            							as customer_id
           ,sum(ifnull(o.vol_13,0)) + ifnull(d.vol_1,0)			as vol_13
        from :lc_dist d
			left outer join customer_history o 
			on d.customer_id = o.sponsor_id 
			and d.period_id = o.period_id 
			and d.batch_id = o.batch_id
        where d.level_id = :ln_x
        group by d.period_id,d.batch_id,d.customer_id,d.vol_1;
        
        commit;
        
    end for;
   
   	Update period_batch
   	Set end_date_volume_org = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;
