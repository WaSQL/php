drop procedure Commissions.Customer_Rollup_Volume;
create procedure Commissions.Customer_Rollup_Volume(
						 pn_Customer_id 		integer
						,pn_PV					double
						,pn_CV					double)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Customer_id		integer;
	declare ln_Rank_High_id		integer;
	declare ln_Type_id			integer;
	declare ls_Enroll_Country	varchar(5);
	declare ln_Realtime_Rank	integer;
	
	-- Only run if there is some volume
	if ifnull(:pn_PV,0) <> 0 or ifnull(:pn_CV,0) <> 0 then
		select realtime_rank
		into ln_Realtime_Rank
		from period
		where period_id = 0;
		
		select c.customer_id, c.rank_high_id, c.type_id, ifnull(e.country,'USA')
		into ln_Customer_id, ln_Rank_High_id, ln_Type_id, ls_Enroll_Country
		from customer c
			left outer join customer e
			on c.enroller_id = e.customer_id
		where c.customer_id = :pn_Customer_id;
	
		-- Set Retail
		if :ln_Type_id = 2 or :ln_Type_id = 3 then
			-- Set PV / CV
			replace customer (customer_id, vol_1, vol_6)
			Select 
			      customer_id
			     ,vol_1 + :pn_PV As pv
			     ,vol_6 + :pn_CV As cv
			From customer
			Where customer_id = :ln_Customer_id;
			
			select s.customer_id, s.rank_high_id, ifnull(e.country,'USA')
			into ln_Customer_id, ln_Rank_High_id, ls_Enroll_Country
			from customer a, customer s
				left outer join customer e
				on s.enroller_id = e.customer_id
			where a.sponsor_id = s.customer_id
			and a.customer_id = :ln_Customer_id;
			
			-- Set Retail
			replace customer (customer_id, vol_1, vol_6, vol_4, vol_9)
			Select 
				 customer_id
		     	,vol_1 + :pn_PV As pv
		     	,vol_6 + :pn_CV As cv
			    ,vol_4 + :pn_PV As pv_retail
			    ,vol_9 + :pn_CV As cv_retail
			From customer
			Where customer_id = :ln_Customer_id;
		else
			-- Set PV / CV
			replace customer (customer_id, vol_1, vol_6)
			Select 
			      customer_id
			     ,vol_1 + :pn_PV As pv
			     ,vol_6 + :pn_CV As cv
			From customer
			Where customer_id = :ln_Customer_id;
		end if;
		
		-- Set EGV
		if :ls_Enroll_Country = 'KOR' and :ln_Rank_High_id < 5 then
			replace customer (customer_id, vol_10)
			select 
				 e.customer_id
				,e.vol_10 + :pn_PV as vol_10
			from customer a, customer e
			where a.enroller_id = e.customer_id
			and a.customer_id = :ln_Customer_id;
		end if;
		
		-- Set OGV
		loop
			if :ln_Customer_id = 3 then
				break;
			end if;
			
			-- Update Org Volume by rolling up PV
	    	replace customer (customer_id, vol_12)
	        select
	            customer_id       	as customer_id
	           ,vol_12 + :pn_PV		as vol_12
	        from customer c
			Where type_id = 1
			and customer_id = :ln_Customer_id;
			
			-- Update Rank
			if ifnull(:ln_Realtime_Rank,1) = 1 then
				call Commissions.Customer_Rollup_Rank(:ln_Customer_id);
			end if;

			select sponsor_id
			into ln_Customer_id
			from customer
			where customer_id = :ln_Customer_id;
			
		end loop;
		
	end if;
end;
