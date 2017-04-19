drop function Commissions.fn_Customer_Organization;
create function Commissions.fn_Customer_Organization(
					  pn_Customer_id 		integer
					, pn_Period_id 			integer
					, pn_Direction_id 		integer
					, pn_Type_id			integer
					, pn_Levels				integer)
returns table (Customer_Root_id	integer
			  ,Org_Type_id		integer
			  ,Org_Type			varchar(50)
			  ,Direction_id		integer
			  ,Direction		varchar(50)
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
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		15-Mar-2017

Purpose:	Returns resultset of a customer's upine/downline

Inputs:
		pn_Direction_id: 0 Upline; 1 Downline
		pn_Type_id: 0 Sponsor Tree; 1 Enroller Tree
		pn_Levels: Number of levels to retrieve

-------------------------------------------------------------------------------- */

begin
	--=============================================================================================================================
	-- Check for Current Customer Tree of Historical Customer Tree
	if ifnull(:pn_Period_id,0) = 0 then
		-- Upline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 0 then
			return 
				select
					 h.cust_root_id					as Customer_Root_id
					,0								as Org_Type_id
					,'Sponsor'						as Org_Type 
					,0								as Direction_id
					,'Upline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select sponsor_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 0 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,0								as Org_Type_id
					,'Sponsor'						as Org_Type 
					,1								as Direction_id
					,'Downline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		
		end if;
		
		-- Upline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,1								as Org_Type_id
					,'Enroller'						as Org_Type 
					,0								as Direction_id
					,'Upline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select enroller_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,1								as Org_Type_id
					,'Enroller'						as Org_Type 
					,1								as Direction_id
					,'Downline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer
					  where sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, enroller_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
	
	--=============================================================================================================================
	else
		-- Upline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 0 then
			return 
				select
					 h.cust_root_id					as Customer_Root_id
					,0								as Org_Type_id
					,'Sponsor'						as Org_Type 
					,0								as Direction_id
					,'Upline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select sponsor_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Sponsor Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 0 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,0								as Org_Type_id
					,'Sponsor'						as Org_Type 
					,1								as Direction_id
					,'Downline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		
		end if;
		
		-- Upline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,1								as Org_Type_id
					,'Enroller'						as Org_Type 
					,0								as Direction_id
					,'Upline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select enroller_id AS node_id, customer_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Enroller Tree
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 h.cust_root_id					as Customer_Root_id
					,1								as Org_Type_id
					,'Enroller'						as Org_Type 
					,1								as Direction_id
					,'Downline'						as Direction 
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.vol_1,2)				as PV
					,round(h.vol_12,2)				as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, enroller_id AS parent_id, to_integer(:pn_Customer_id) as cust_root_id, customer_id, customer_name, sponsor_id, enroller_id, vol_1,vol_12,rank_id,rank_high_id
					             from customer_history
					             where period_id = :pn_Period_id
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case when :pn_Levels = 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
	
	end if;
	
end;
