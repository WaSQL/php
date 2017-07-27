DROP PROCEDURE SP_VOLUME_ORG_SET;
create Procedure Commissions.sp_Volume_Org_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS
    
Begin
	declare ln_max      	integer;
    declare ln_x        	integer;
    
	Update period_batch
	Set beg_date_volume_org = current_timestamp
      ,end_date_volume_org = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		-- Get Period Tree Snapshot
	   	lc_Period_Tree =
			select customer_id, sponsor_id, round(vol_1+vol_4,2) as vol_1
			from customer;
	    
	    -- Add Tree Level to Snaphot
	    lc_dist = 
			select
				 node_id 			as customer_id
				,vol_1				as vol_1
				,hierarchy_level	as hier_level
			from HIERARCHY ( 
				 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, vol_1
				             from :lc_Period_Tree)
		    		Start where customer_id = 1)
		    order by node_id;
	        
	    -- Get Max Level
	    select max(hier_level)
	    into ln_max
	    from :lc_dist;
	    
	    -- Loop through all tree levels from bottom to top
	    for ln_x in reverse 0..:ln_max do
	    	-- Update Org Volume by rolling up PV
	    	replace customer (customer_id, vol_13)
	        select
	            d.customer_id            					as customer_id
	           ,sum(ifnull(o.vol_13,0)) + ifnull(d.vol_1,0)	as vol_13
	        from :lc_dist d
				left outer join customer o 
				on d.customer_id = o.sponsor_id
	        where d.hier_level = :ln_x
	        group by d.customer_id,d.vol_1;
	        
	        commit;
	        
	    end for;
	else
	   	-- Add Tree Level to Snaphot
	    lc_dist_hist = 
			select 
				  customer_id
				, period_id
				, batch_id
				, round(vol_1+vol_4,2) as vol_1
				, hier_level
			from customer_history
			where period_id = :pn_Period_id
			and batch_id = :pn_Period_Batch_id;
		 
		/*
	    lc_dist_hist = 
			select
				 customer_id		as customer_id
				,period_id    		as period_id
				,batch_id 			as batch_id
				,vol_1				as vol_1
				,hierarchy_level	as hier_level
			from HIERARCHY ( 
				 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, sponsor_id, period_id, batch_id, round(vol_1+vol_4,2) as vol_1
				             from customer_history
							 where period_id = :pn_Period_id
							 and batch_id = :pn_Period_Batch_id)
		    		Start where customer_id = 1)
		    order by node_id;
		*/
	        
	    -- Get Max Level
	    select max(hier_level)
	    into ln_max
	    from :lc_dist_hist;
	    
	    -- Loop through all tree levels from bottom to top
	    for ln_x in reverse 1..:ln_max do
	    	-- Update Org Volume by rolling up PV
	    	replace customer_history (period_id, batch_id, customer_id, vol_13)
	        select
	            d.period_id             							as period_id
	           ,d.batch_id 			 								as batch_id
	           ,d.customer_id            							as customer_id
	           ,sum(ifnull(o.vol_13,0)) + ifnull(d.vol_1,0)			as vol_13
	        from :lc_dist_hist d
				left outer join customer_history o 
				on d.customer_id = o.sponsor_id 
				and d.period_id = o.period_id 
				and d.batch_id = o.batch_id
	        where d.hier_level = :ln_x
	        group by d.period_id,d.batch_id,d.customer_id,d.vol_1;
	        
	        commit;
	        
	    end for;
	    
	    -- Delete Non Distributor Tree Org Volume
		replace customer_history (period_id, batch_id, customer_id, vol_13)
		select period_id, batch_id, customer_id, 0
		from customer_history
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id
		--and customer_id <> 1
		and hier_level = 0;
		
		commit;
		
		-- Set Entire Org Volume
		replace customer_history (period_id, batch_id, customer_id, vol_13)
		select period_id, batch_id, 1 as customer_id, round(sum(vol_1+vol_4),2)
		from customer_history
		where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id
		group by period_id, batch_id;
		    		
		/*
		replace customer_history (period_id, batch_id, customer_id, vol_13)
			select period_id, batch_id, customer_id, 0
			from customer_history
			where period_id = :pn_Period_id
			and batch_id = :pn_Period_Batch_id
			and customer_id <> 1
			minus
			select period_id, batch_id, customer_id, 0
			from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, customer_id, period_id, batch_id
					             from customer_history
								 where period_id = :pn_Period_id
								 and batch_id = :pn_Period_Batch_id
					             order by customer_id)
			    		Start where customer_id = 3);
		*/

	end if;
	
	commit;
   
   	Update period_batch
   	Set end_date_volume_org = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   	
End;