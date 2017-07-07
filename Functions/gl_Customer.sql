drop function Commissions.gl_Customer;
CREATE function Commissions.gl_Customer(
					 pn_Period_id		integer
					,pn_Period_Batch_id	integer
					,pn_Customer_id		integer default 0)
returns table (
			 period_id 					integer
			,batch_id					integer
			,customer_id				integer
			,customer_name				nvarchar(900)
			,hier_level					integer
			,hier_rank					integer
			,type_id					integer
			,status_id					integer
			,rank_id					integer
			,rank_high_id				integer
			,sponsor_id					integer
			,enroller_id				integer
			,country					varchar(5)
			,currency					nvarchar(100)
			,exchange_rate				decimal(18,8)
			,round_factor				integer
			,comm_status_date			timestamp
			,entry_date					timestamp
			,version_id					integer
			,pv							decimal(18,8)
			,cv							decimal(18,8)
			,qv							decimal(19,8)
			,pv_lrp						decimal(18,8)
			,egv						decimal(18,8)
			,egv_lrp					decimal(18,8)
			,tv							decimal(18,8)
			,ov							decimal(18,8)
			,tv_waiver					integer
			,pv_lrp_waiver				integer
			,has_downline				integer
			,has_faststart				integer
			,has_retail					integer
			,has_power3					integer
			,has_earnings				integer)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		12-May-2017

Purpose:	Returns a resultset of cusomters

-------------------------------------------------------------------------------- */

begin
		
	-- Get Exchange Rates
	lc_Exchange = 
		select *
		from gl_Exchange(:pn_Period_id);
		
	-- Get Customer Type
	lc_Customer_Type =
		select *
		from customer_type;
		
	-- Get Customer Status
	lc_Customer_Status =
		select *
		from customer_status;
		
	-- Get Customer Flags
	lc_Customer_Flag =
		select *
		from gl_Customer_Flag(0, :pn_Period_id);
		
	-- Get Versions
	lc_Version =
		select *
		from version;
		
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		-- if period is open use customer table
		return
		Select
			 :pn_Period_id													as period_id
			,:pn_Period_Batch_id											as batch_id
			,c.customer_id													as customer_id
			,c.customer_name												as customer_name
			,0																as hier_level
			,0																as hier_rank
			,c.type_id														as type_id
			,c.status_id													as status_id
			,c.rank_id														as rank_id
			,c.rank_high_id													as rank_high_id
			,c.sponsor_id													as sponsor_id
			,c.enroller_id													as enroller_id
			,c.country														as country
			,e.currency														as currency
			,e.rate															as exchange_rate
			,e.round_factor													as round_factor
			,ifnull(c.comm_status_date,
				case when ifnull(t1.has_faststart,0) = 1 then c.entry_date						-- Type Wellness, Professional and Wholesale default to entry_date
				else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
			,c.entry_date													as entry_date
			,ifnull(v.version_id,1)											as version_id
			,vol_1															as pv
			,vol_6															as cv
			,vol_1+vol_4													as qv
			,vol_2															as pv_lrp
			,vol_11															as egv
			,vol_12															as egv_lrp
			,vol_14															as tv
			,vol_13															as ov
			,map(ifnull(f7.flag_type_id,0),7,1,0)							as tv_waiver
			,map(ifnull(f6.flag_type_id,0),6,1,0)							as pv_lrp_waiver
			,ifnull(t1.has_downline,0)										as has_downline
			,ifnull(t1.has_faststart,0)										as has_faststart
			,ifnull(t1.has_retail,0)										as has_retail
			,ifnull(t1.has_power3,0)										as has_power3
			,ifnull(s1.has_earnings,0)										as has_earnings
		From customer c
			left outer join :lc_Customer_Type t1
				on c.type_id = t1.type_id
		   	left outer join :lc_Customer_Status s1
		   		on c.status_id = s1.status_id
			left outer join :lc_Version v
				on c.country = v.country
			left outer join :lc_Customer_Flag f6
				on f6.customer_id = c.customer_id
				and f6.flag_type_id = 6
			left outer join :lc_Customer_Flag f7
				on f7.customer_id = c.customer_id
				and f7.flag_type_id = 7
    		left outer join :lc_Exchange e
    			on e.currency = ifnull((select flag_value
										  from :lc_Customer_Flag
										  where customer_id = c.customer_id
										  and flag_type_id = 2),c.currency)
    	where c.customer_id = map(ifnull(:pn_Customer_id,0),0, c.customer_id, :pn_Customer_id);
	else
		-- if period is closed use customer_history table
		return
		Select
			 c.period_id													as period_id
			,c.batch_id														as batch_id
			,c.customer_id													as customer_id
			,c.customer_name												as customer_name
			,c.hier_level													as hier_level
			,c.hier_rank													as hier_rank
			,c.type_id														as type_id
			,c.status_id													as status_id
			,c.rank_id														as rank_id
			,c.rank_high_id													as rank_high_id
			,c.sponsor_id													as sponsor_id
			,c.enroller_id													as enroller_id
			,c.country														as country
			,e.currency														as currency
			,e.rate															as exchange_rate
			,e.round_factor													as round_factor
			,ifnull(c.comm_status_date,
				case when ifnull(t1.has_faststart,0) = 1 then c.entry_date						-- Type Wellness, Professional and Wholesale default to entry_date
				else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
			,c.entry_date													as entry_date
			,ifnull(v.version_id,1)											as version_id
			,vol_1															as pv
			,vol_6															as cv
			,vol_1+vol_4													as qv
			,vol_2															as pv_lrp
			,vol_11															as egv
			,vol_12															as egv_lrp
			,vol_14															as tv
			,vol_13															as ov
			,map(ifnull(f7.flag_type_id,0),7,1,0)							as tv_waiver
			,map(ifnull(f6.flag_type_id,0),6,1,0)							as pv_lrp_waiver
			,ifnull(t1.has_downline,0)										as has_downline
			,ifnull(t1.has_faststart,0)										as has_faststart
			,ifnull(t1.has_retail,0)										as has_retail
			,ifnull(t1.has_power3,0)										as has_power3
			,ifnull(s1.has_earnings,0)										as has_earnings
		From customer_history c
			left outer join :lc_Customer_Type t1
				on c.type_id = t1.type_id
		   	left outer join :lc_Customer_Status s1
		   		on c.status_id = s1.status_id
			left outer join :lc_Version v
				on c.country = v.country
			left outer join :lc_Customer_Flag f6
				on f6.customer_id = c.customer_id
				and f6.flag_type_id = 6
			left outer join :lc_Customer_Flag f7
				on f7.customer_id = c.customer_id
				and f7.flag_type_id = 7
    		left outer join :lc_Exchange e
    			on e.currency = ifnull((select flag_value
										  from :lc_Customer_Flag
										  where customer_id = c.customer_id
										  and flag_type_id = 2),c.currency)
		Where c.period_id = :pn_Period_id
		and c.batch_id = :pn_Period_Batch_id
		and c.customer_id = map(ifnull(:pn_Customer_id,0),0, c.customer_id, :pn_Customer_id);
	end if;
	
end;
