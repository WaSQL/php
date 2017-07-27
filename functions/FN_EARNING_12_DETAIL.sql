drop function Commissions.fn_Earning_12_Detail;
CREATE function Commissions.fn_Earning_12_Detail
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Function
* @date			2-Jun-2017
*
* @describe		Returns a resultset of Professional Translations Detail
*
* @param		integer		pn_Customer_id 		Customer id
* @param		integer		pn_Period_id 		Commission Period
* @param		varchar		[ps_Locale]			Locale to Translate to
*
* @return		table
*					integer		id
*					nvarchar	name
*					nvarchar	type
*					integer		order_id
*					decimal		pv
*					decimal		cv
*					decimal		rate
*					varchar		bonus
*					varchar		bonus_ex
*					decimal		bonus_sub
*
* @example		select * from fn_Earning_12_Detail(1001, 10);
-------------------------------------------------------*/
(pn_Customer_id	integer
,pn_Period_id	integer
,ps_Locale		varchar(7) default 'en-US')
returns table (
			 id 				integer
			,name				nvarchar(900) 
			,type				nvarchar(20)
			,order_id			integer
			,pv					decimal(18,2)
			,cv					decimal(18,2)
			,rate				decimal(18,5)
			,bonus				varchar(50)
			,bonus_ex			varchar(50)
			,bonus_sub			decimal(18,2))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Period_Batch_id	integer = gl_Period_Viewable(:pn_Period_id);
	
	lc_Exchange =
		select *
		from gl_Exchange(:pn_Period_id);
		
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		select 
			 null as id
			,null as name 
			,null as type
			,null as order_id
			,null as pv
			,null as cv
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
			,a.type
			,a.order_id
			,a.pv
			,a.cv
			,a.rate
			,round(a.bonus,x1.round_factor) || ' ' || from_currency			as bonus
			,round(a.bonus_exchanged,x2.round_factor) || ' ' || to_currency	as bonus_ex
			,round(a.bonus_sub,x2.round_factor) 							as bonus_sub
		from (
				select c.customer_id
					,c.hier_rank
					,case rank() over (partition by c.customer_id order by t.order_number)
					 	when 1 then c.customer_id
					 	else null end		as id
					,case rank() over (partition by c.customer_id order by t.order_number)
					 	when 1 then c.customer_name
					 	else null end 		as name
					,y.description			as type
					,t.order_number			as order_id
					,t.value_2				as pv
					,t.value_4				as cv
					,e.from_currency
					,e.to_currency
					,e.exchange_rate		as rate
					,e.bonus
					,e.bonus_exchanged
					,case rank() over (partition by c.customer_id order by t.order_number)
						when 1 then sum(e.bonus_exchanged) over (partition by c.customer_id)
						else null end 		as bonus_sub
				from Earning_12 e
					, transaction t
					left outer join customer_history c
						on c.customer_id = t.customer_id
						and c.period_id = :pn_Period_id
						and c.batch_id =:ln_Period_Batch_id
					, customer_type y
				where e.transaction_id = t.transaction_id
				and t.customer_type_id = y.type_id
				and e.customer_id = :pn_Customer_id
				and e.period_id = :pn_Period_id
				and e.batch_id = :ln_Period_Batch_id) a
			left outer join :lc_Exchange x1
				on x1.currency = from_currency
			left outer join :lc_Exchange x2
				on x2.currency = to_currency
			order by hier_rank, customer_id, order_id;
	end if;
	
end;
