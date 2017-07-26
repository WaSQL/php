drop function commissions.fn_Earning_Pool_Detail;
create function commissions.fn_Earning_Pool_Detail
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5-Jun-2017
*
* @describe     returns drilldown data for the total pool payouts
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_pool_type
*
* @returns 		table
*				nvarchar description
*				integer shares
*				integer shares_extra
*				decimal share_value
*				decimal rate
*				varchar bonus
*				varchar bonus_ex
*
* @example      select * from commissions.fn_Earning_Pool_Detail(1001, 10, 5)
-------------------------------------------------------*/
(pn_Customer_id 	integer
,pn_Period_id 		integer
,pn_Pool_type 		integer)
returns table (
	  description		nvarchar(50)
	, shares 			integer
	, shares_extra 		integer
	, share_value 		decimal(18,2)
	, rate 				decimal(18,5)
	, bonus 			varchar(50)
	, bonus_ex 			varchar(50))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
as
begin
	declare ln_Period_Batch_id	integer;
	
	ln_Period_Batch_id = gl_Period_Viewable(:pn_Period_id);
	
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
	
	if (:pn_pool_type = 5) then
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_05 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 1) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 6) then
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_06 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 2) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 7) then 
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_07 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 3) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 8) then 
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_08 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 4) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 9) then 
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_09 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 5) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 10) then 
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_10 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 6) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
			
	elseif (:pn_pool_type = 11) then 
		return
		select 
			 a.description
			,a.shares
			,a.shares_extra
			,a.share_value
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency		as bonus
			,round(a.bonus_ex,x2.round_factor) || ' ' || to_currency	as bonus_ex
		from (
				select 
					  e.description									as description
					, ea.shares										as shares
					, ea.shares_extra								as shares_extra
					, map(ea.customer_id,null,null,h.share_value)	as share_value
					, ea.from_currency								as from_currency
					, ea.to_currency								as to_currency
					, ea.exchange_rate								as rate
					, ea.bonus										as bonus
					, ea.bonus_exchanged							as bonus_ex
				from pool e
					,pool_head h
					left outer join earning_11 ea
						on ea.period_id = h.period_id
						and ea.batch_id = h.batch_id
						and ea.customer_id = :pn_Customer_id
				where h.pool_id = e.pool_id
				and h.period_id = :pn_Period_id
				and h.batch_id = :ln_Period_Batch_id
				and e.pool_id = 7) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency;
	end if;
end;
