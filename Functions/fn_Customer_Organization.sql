drop function Commissions.fn_Customer_Organization;
create function Commissions.fn_Customer_Organization
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Function
* @date			15-Mar-2017
*
* @describe		Returns resultset of a customer's upine/downline
*
* @param		integer		pn_Customer_id 		Customer id
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Direction_id 	0 - Upline ; 1 - Downline
* @param		integer		pn_Type_id 			0 - Sponsor Tree ; 1 - Enroller Tree
* @param		varchar		[pn_Levels]			Number of levels to retrieve
*
* @return		table
*					integer		Earning_id
*					nvarchar	display_name
*					decimal		amount
*					varchar		currency
*					varchar		detail_function
*
* @example		select * from fn_Customer_Organization(1001, 10, 1, 0, 3);
-------------------------------------------------------*/
(pn_Customer_id 		integer
,pn_Period_id 			integer
,pn_Direction_id 		integer
,pn_Type_id				integer
,pn_Levels				integer default 2)
returns table (Customer_Root_id	integer
			  ,Customer_id		integer
			  ,Customer_name	varchar(50)
			  ,Level_id			integer
			  ,Sponsor_id		integer
			  ,Enroller_id		integer
			  ,PV				decimal(18,8)
			  ,OV				decimal(18,8)
			  ,Rank_id			integer
			  ,Rank_Title		integer
			  ,count_sub		bigint)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions

AS

begin
	lc_Customer =
		select *
		from gl_Customer(:pn_Period_id, 0); --gl_Period_Viewable(:pn_Period_id));
		
	--if ifnull(:pn_Levels,2) = 2 then
	if 1 = 1 then
		-- Upline Sponsor Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 null as Customer_Root_id
					,null as Customer_id
					,null as Customer_name
					,null as Level_id
					,null as Sponsor_id
					,null as Enroller_id
					,null as PV
					,null as OV
					,null as Rank_id
					,null as Rank_Title
					,0	  as count_sub
				from dummy;
		end if;
		
		-- Downline Sponsor Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 to_integer(:pn_Customer_id)		as Customer_Root_id
					,h.customer_id						as Customer_id
					,h.customer_name					as Customer_name
					,h.hier_level						as Level_id
					,h.sponsor_id						as Sponsor_id
					,h.enroller_id						as Enroller_id
					,round(h.pv,2)						as PV
					,round(h.ov,2)						as OV
					,h.rank_id							as Rank_id
					,h.rank_high_id						as Rank_Title
					,(select count(*)
					  from :lc_Customer
					  where sponsor_id = h.customer_id)	as count_sub
				from :lc_Customer h
			    where h.sponsor_id = :pn_Customer_id
			    order by h.hier_rank;
		end if;
		
		-- Upline Enroller Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 null as Customer_Root_id
					,null as Customer_id
					,null as Customer_name
					,null as Level_id
					,null as Sponsor_id
					,null as Enroller_id
					,null as PV
					,null as OV
					,null as Rank_id
					,null as Rank_Title
					,0	  as count_sub
				from dummy;
		end if;
		
		-- Downline Enroller Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 to_integer(:pn_Customer_id)		as Customer_Root_id
					,h.customer_id						as Customer_id
					,h.customer_name					as Customer_name
					,h.hier_level						as Level_id
					,h.sponsor_id						as Sponsor_id
					,h.enroller_id						as Enroller_id
					,round(h.pv,2)						as PV
					,round(h.ov,2)						as OV
					,h.rank_id							as Rank_id
					,h.rank_high_id						as Rank_Title
					,(select count(*)
					  from :lc_Customer
					  where enroller_id = h.customer_id)	as count_sub
				from :lc_Customer h
			    where h.enroller_id = :pn_Customer_id
			    order by h.hier_rank;
		end if;
	-- ============================================================================================================================================================================
	else
		-- Upline Sponsor Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 0 then
			return 
				select
					 to_integer(:pn_Customer_id)	as Customer_Root_id
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					--,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					--,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.pv,2)					as PV
					,round(h.ov,2)					as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select sponsor_id AS node_id, customer_id AS parent_id, *
					             from :lc_Customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join :lc_Customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join :lc_Customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case ifnull(:pn_Levels,2) when 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Sponsor Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 0 then
			return
				select
					 to_integer(:pn_Customer_id)	as Customer_Root_id
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					--,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					--,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.pv,2)					as PV
					,round(h.ov,2)					as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and sponsor_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, *
					             from :lc_Customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join :lc_Customer s
			    	 on s.customer_id = h.sponsor_id
			    	 left outer join :lc_Customer e
			    	 on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case ifnull(:pn_Levels,2) when 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		
		end if;
		
		-- Upline Enroller Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 0 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 to_integer(:pn_Customer_id)	as Customer_Root_id
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					--,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					--,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.pv,2)					as PV
					,round(h.ov,2)					as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select enroller_id AS node_id, customer_id AS parent_id, *
					             from :lc_Customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join :lc_Customer s
			    	 	on s.customer_id = h.sponsor_id
			    	 left outer join :lc_Customer e
			    	 	on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case ifnull(:pn_Levels,2) when 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
		
		-- Downline Enroller Tree --------------------------------------------------------------------------------------------------------
		if ifnull(:pn_Direction_id,1) = 1 and ifnull(:pn_Type_id,0) = 1 then
			return
				select
					 to_integer(:pn_Customer_id)	as Customer_Root_id
					,h.customer_id					as Customer_id
					,ifnull(h.customer_name,'******na')	as Customer_name
					,h.hierarchy_level				as Level_id
					,h.sponsor_id					as Sponsor_id
					--,ifnull(s.customer_name,'******na')	as Sponsor_name
					,h.enroller_id					as Enroller_id
					--,ifnull(e.customer_name,'******na')	as Enroller_name
					,round(h.pv,2)					as PV
					,round(h.ov,2)					as OV
					,h.rank_id						as Rank_id
					,h.rank_high_id					as Rank_Title
					,(select count(*)
					  from customer_history
					  where period_id = :pn_Period_id
					  and enroller_id = h.customer_id)	as count_sub
				from HIERARCHY ( 
					 	SOURCE ( select customer_id AS node_id, enroller_id AS parent_id, *
					             from :lc_Customer
					             order by customer_id)
			    		Start where customer_id = :pn_Customer_id) h
			    	 left outer join :lc_Customer s
			    	 	on s.customer_id = h.sponsor_id
			    	 left outer join :lc_Customer e
			    	 	on e.customer_id = h.enroller_id
			    where h.hierarchy_level <= case ifnull(:pn_Levels,2) when 0 then h.hierarchy_level else :pn_Levels end
			    order by h.hierarchy_rank;
		end if;
	end if;
	
end;
