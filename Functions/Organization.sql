drop function Commissions.Organization;
create function Commissions.Organization(
					  pn_Customer_id 		integer
					, pn_Period_id 			integer
					, pn_Direction_id 		integer
					, pn_Type_id			integer
					, pn_Levels				integer)
returns table (Customer_Root_id	integer
			  ,Customer_id		integer
			  ,Customer_name	varchar(50)
			  ,Level_id			integer
			  ,Sponsor_id		integer
			  ,Sponsor_name		varchar(50)
			  ,Enroller_id		integer
			  ,Enroller_name	varchar(50)
			  ,PV				decimal(18,8)
			  ,OV				decimal(18,8)
			  ,Rank_id			integer
			  ,Rank_Title		integer
			  ,count_sub		bigint)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions

AS

begin
	--=============================================================================================================================
	-- Check for Current Customer Tree of Historical Customer Tree
	if ifnull(:pn_Period_id,0) = 0 then
		-- Upline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 0 then
			return 
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select sponsor_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
		
		-- Downline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 0 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		
		end if;
		
		-- Upline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select enroller_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
		
		-- Downline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, enroller_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
	
	--=============================================================================================================================
	else
		-- Upline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 0 then
			return 
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select sponsor_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
		
		-- Downline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 0 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		
		end if;
		
		-- Upline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select enroller_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
		
		-- Downline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 cust_root_id		as Customer_Root_id
					,customer_id		as Customer_id
					,'Customer_name'	as Customer_name
					,hierarchy_level	as Level_id
					,sponsor_id			as Sponsor_id
					,'Sponsor_name'		as Sponsor_name
					,enroller_id		as Enroller_id
					,'Enroller_name'	as Enroller_name
					,vol_1				as PV
					,vol_12				as OV
					,rank_id			as Rank_id
					,rank_high_id		as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, enroller_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    where hierarchy_level <= case when :pn_Levels = 0 then hierarchy_level else :pn_Levels end;
		end if;
	
	end if;
	
end;