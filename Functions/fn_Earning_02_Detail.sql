drop function commissions.fn_Earning_02_Detail;
create function commissions.fn_Earning_02_Detail(
						  pn_Customer_id 	integer
						, pn_Period_id 		integer
						, ps_Locale			varchar(7) default 'en-US')
	returns table (
		  id 			integer
		, name 			nvarchar(900)
		, rank_id		integer
		, qv 			decimal(18,2)
		, pv_lrp 		decimal(18,2)
		, egv_lrp 		decimal(18,2)
		, tv			decimal(18,2)
		, struct 		integer
		, lvl 			integer
		, cnt_lvl_1		integer
		, cnt_lvl_2		integer
		, cnt_lvl_3		integer
		, rate			decimal(18,2)
		, bonus 		varchar(50)
		, bonus_ex 		varchar(50))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
/* --------------------------------------------------------------------------------
Created by: 	Del Stirling
Created Date:	6/5/2017

Purpose:		returns data for power of 3 dropdown

Note:			6-Jun-2017 LTC	I reworked this function to meet basic stardards
								and to return correct results.

-------------------------------------------------------------------------------- */
as 
begin
	declare ln_Period_Batch_id		integer = gl_Period_Viewable(:pn_Period_id);
	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		select 
			 null as id
			,null as name 
			,null as rank_id
			,null as qv
			,null as pv_lrp
			,null as egv_lrp
			,null as tv
			,null as struct
			,null as lvl
			,null as cnt_lvl_1
			,null as cnt_lvl_2
			,null as cnt_lvl_3
			,null as rate
			,null as bonus
			,null as bonus_ex
		from dummy;
	else
		lc_Earning =
			select *
			from Earning_02
			where period_id = :pn_Period_id
			and batch_id = :ln_Period_Batch_id;
			
		lc_Exchange =
			select *
			from gl_Exchange(:pn_Period_id);
			
		return
		select 
			  c.customer_id														as id
			, c.customer_name													as name
			, c.rank_id															as rank_id
			, c.vol_1+c.vol_4													as qv
			, c.vol_2															as pv_lrp
			, c.vol_12															as egv_lrp
			, c.vol_14															as tv
			, e.paid_lvl_id 													as struct
			, e.lvl_id	 														as lvl
			, (select count(*)
			   from :lc_Earning
			   where sponsor_id = c.customer_id
			   and (lvl_id >= 1
			    or paid_lvl_id > 1))											as cnt_lvl_1
			, (select count(*)
			   from :lc_Earning
			   where sponsor_id = c.customer_id
			   and (lvl_id >= 2
			    or paid_lvl_id > 1))											as cnt_lvl_2
			, (select count(*)
			   from :lc_Earning
			   where sponsor_id = c.customer_id
			   and (lvl_id >= 3
			    or paid_lvl_id > 1))											as cnt_lvl_3
			, e.exchange_rate 													as rate
			, round(e.bonus,x1.round_factor) || ' ' || e.from_currency			as bonus
			, round(e.bonus_exchanged,x2.round_factor) || ' ' || e.to_currency	as bonus_ex
		from :lc_Earning e
			left join customer_history c
				on c.customer_id = e.customer_id
				and c.period_id = e.period_id
				and c.batch_id = e.batch_id
			left outer join :lc_Exchange x1
				on x1.currency = e.from_currency
			left outer join :lc_Exchange x2
				on x2.currency = e.to_currency
		where (e.sponsor_id = :pn_Customer_id or e.customer_id = :pn_Customer_id)
		order by case when e.customer_id = :pn_Customer_id then 1 else 2 end, e.paid_lvl_id, e.lvl_id, c.customer_id;
	end if;
	
end;
