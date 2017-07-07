drop function Commissions.fn_Earning;
CREATE function Commissions.fn_Earning(
						 pn_Customer_id	integer
						,pn_Period_id	integer
						,ps_Locale		varchar(7) default 'en-US')
returns table (
			 Earning_id 		integer
			,display_name		nvarchar(900) 
			,amount				decimal(18,2)
			,currency			varchar(5)
			,detail_function	varchar(50))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		19-May-2017

Purpose:	Returns a resultset of Earning Totals

-------------------------------------------------------------------------------- */

begin
	declare ln_Period_Batch_id		integer = gl_Period_Viewable(:pn_Period_id);
	
	-- Get Earning Types
	lc_Earning_Type =
		select 
			 e.Earning_id
			,e.description
		from Earning e, period p
		where e.period_type_id = p.period_type_id
		and p.period_id = :pn_Period_id
		order by e.Earning_id;
		
	-- Get Commission Currency Override Flags
	lc_Flags =
		select *
		from gl_Customer_Flag(:pn_Customer_id, :pn_Period_id, 0)
		where flag_type_id = 2;
		
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
	
	-- Period is Open
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		lc_Customer =
			select 
				 0															as amount
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency)	as currency
			from customer c
				  left outer join :lc_Flags f
					  on c.customer_id = f.customer_id
			where c.customer_id = :pn_Customer_id;
			
		return
		select 
			 p.Earning_id 					as Earning_id
			,p.description 					as display_name 
			,round(c.amount,x.round_factor)	as amount
			,c.currency						as currency
			,null							as detail_function
		from :lc_Earning_Type p, :lc_Customer c
			left outer join :lc_Exchange x
				on x.currency = c.currency;
		
	-- Period is Closed
	else
		lc_Customer_hist =
			select 
				 ifnull(c.Earning_1, 0)										as Earning_1
				,ifnull(c.Earning_2, 0)										as Earning_2
				,ifnull(c.Earning_3, 0)										as Earning_3
				,ifnull(c.Earning_4, 0)										as Earning_4
				,ifnull(c.Earning_5, 0)										as Earning_5
				,ifnull(c.Earning_6, 0)										as Earning_6
				,ifnull(c.Earning_7, 0)										as Earning_7
				,ifnull(c.Earning_8, 0)										as Earning_8
				,ifnull(c.Earning_9, 0)										as Earning_9
				,ifnull(c.Earning_10, 0)									as Earning_10
				,ifnull(c.Earning_11, 0)									as Earning_11
				,ifnull(c.Earning_12, 0)									as Earning_12
				,ifnull(c.Earning_13, 0)									as Earning_13
				,map(ifnull(f.flag_type_id,0),2,f.flag_value,c.currency)	as currency
			from customer_history c
				  left outer join :lc_Flags f
					  on c.customer_id = f.customer_id
			where c.customer_id = :pn_Customer_id
			and c.period_id = :pn_Period_id
			and c.batch_id = :ln_Period_Batch_id;
		
		return
		select 
			 p.Earning_id
			,p.description	as display_name 
			,case p.Earning_id
				when 1 then round(c.Earning_1,x.round_factor)
				when 2 then round(c.Earning_2,x.round_factor)
				when 3 then round(c.Earning_3,x.round_factor)
				when 4 then round(c.Earning_4,x.round_factor)
				when 5 then round(c.Earning_5,x.round_factor)
				when 6 then round(c.Earning_6,x.round_factor)
				when 7 then round(c.Earning_7,x.round_factor)
				when 8 then round(c.Earning_8,x.round_factor)
				when 9 then round(c.Earning_9,x.round_factor)
				when 10 then round(c.Earning_10,x.round_factor)
				when 11 then round(c.Earning_11,x.round_factor)
				when 12 then round(c.Earning_12,x.round_factor)
				when 13 then round(c.Earning_13,x.round_factor)
				else round(0,x.round_factor) end as amount
			,c.currency			as currency
			,case p.Earning_id
				when 1 then 'fn_Earning_01_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', "' || :ps_Locale || '")'
				when 2 then 'fn_Earning_02_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', "' || :ps_Locale || '")'
				when 3 then 'fn_Earning_03_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', "' || :ps_Locale || '")'
				when 4 then 'fn_Earning_04_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', "' || :ps_Locale || '")'
				when 5 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 1)'
				when 6 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 2)'
				when 7 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 3)'
				when 8 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 4)'
				when 9 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 5)'
				when 10 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 6)'
				when 11 then 'fn_Earning_Pool_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', 7)'
				when 12 then 'fn_Earning_12_Detail(' || :pn_Customer_id || ', ' || :pn_Period_id || ', "' || :ps_Locale || '")'
				when 13 then null
			 	else null end	as detail_function
		from :lc_Earning_Type p, :lc_Customer_hist c
			left outer join :lc_Exchange x
				on x.currency = c.currency;
	end if;
	
end;
