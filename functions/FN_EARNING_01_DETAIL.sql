drop function Commissions.fn_Earning_01_Detail;
CREATE function Commissions.fn_Earning_01_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Function
* @date			2-Jun-2017
*
* @describe		Returns a resultset of Unilevel Translations Detail
*
* @param		integer		pn_Customer_id 		Customer id
* @param		integer		pn_Period_id 		Commission Period
* @param		varchar		[ps_Locale]			Locale to Translate to
* @param		varchar		[pn_Limit]			Number of Records to Return
* @param		varchar		[pn_Offset]			Page Offset
*
* @return		table
*					integer		id
*					nvarchar	name
*					integer		rank_id
*					integer		trans_id
*					nvarchar	trans_type
*					decimal		pv
*					decimal		cv
*					integer		pct
*					integer		lvl
*					integer		lvl_paid
*					decimal		rate
*					varchar		bonus
*					varchar		bonus_ex
*					decimal		bonus_sub
*
* @example		select * from fn_Earning_01_Detail(1001, 10);
-------------------------------------------------------*/
(pn_Customer_id	integer
,pn_Period_id	integer
,ps_Locale		varchar(7) default 'en-US'
,pn_Limit		integer default 100
,pn_Offset		integer	default 0)
returns table (
			 id 				integer
			,name				nvarchar(900) 
			,rank_id			integer
			,trans_id			integer
			,trans_type			nvarchar(20)
			,pv					decimal(18,2)
			,cv					decimal(18,2)
			,pct				integer
			,lvl				integer
			,lvl_paid			integer
			,rate				decimal(18,5)
			,bonus				varchar(50)
			,bonus_ex			varchar(50)
			,bonus_sub			decimal(18,2))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_Batch_id		integer = gl_Period_Viewable(:pn_Period_id);
	declare ln_Limit				integer = ifnull(:pn_Limit,100);
	declare ln_Offset				integer = ifnull(:pn_Offset,0);
	
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
		
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		select 
			 null as id
			,null as name 
			,null as rank_id
			,null as trans_id
			,null as trans_type
			,null as pv
			,null as cv
			,null as pct
			,null as lvl
			,null as lvl_paid
			,null as rate
			,null as bonus
			,null as bonus_ex
			,null as bonus_sub
		from dummy;
	else
		return
		select
			 a.id
			,a.name
			,a.rank_id
			,a.trans_id
			,a.trans_type
			,a.pv
			,a.cv
			,a.pct
			,a.lvl
			,a.lvl_paid
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency			as bonus
			,round(a.bonus_exchanged,x2.round_factor) || ' ' || to_currency	as bonus_ex
			,round(a.bonus_sub,x2.round_factor)								as bonus_sub
		from (
				select 
					c.customer_id
					,c.hier_rank
					,case rank() over (partition by c.customer_id order by t.order_number)
					 	when 1 then c.customer_id
					 	else null end		as id
					,case rank() over (partition by c.customer_id order by t.order_number)
					 	when 1 then c.customer_name
					 	else null end 		as name
					,case rank() over (partition by c.customer_id order by t.order_number)
					 	when 1 then c.rank_id
					 	else null end		as rank_id
					,t.order_number			as trans_id
					,y.description			as trans_type
					,p.pv
					,p.cv
					,p.percentage			as pct
					,p.lvl
					,p.lvl_paid
					,p.from_currency
					,p.to_currency
					,p.exchange_rate		as rate
					,p.bonus
					,p.bonus_exchanged
					,case rank() over (partition by c.customer_id order by t.order_number)
						when 1 then sum(p.bonus_exchanged) over (partition by c.customer_id)
						else null end 		as bonus_sub
				from Earning_01 p
					, customer_history c
					, transaction t
					, transaction_type y
				where p.transaction_customer_id = c.customer_id
				and p.transaction_id = t.transaction_id
				and p.transaction_type_id = y.transaction_type_id
				and p.customer_id = :pn_Customer_id
				and p.period_id = c.period_id
				and p.batch_id = c.batch_id
				and p.period_id = :pn_Period_id
				and p.batch_id = :ln_Period_Batch_id) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency
			order by hier_rank, customer_id, trans_id
			limit :ln_Limit offset :ln_Offset;
	end if;
	
end;
