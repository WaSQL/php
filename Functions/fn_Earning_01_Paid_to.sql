drop function Commissions.fn_Earning_01_Paid_to;
CREATE function Commissions.fn_Earning_01_Paid_to(
						 pn_Customer_id	integer
						,pn_Period_id	integer
						,ps_Locale		varchar(7) default 'en-US')
returns table (
			 id 			integer
			,name			nvarchar(900) 
			,trans_id		integer
			,trans_type		nvarchar(20)
			,pv				decimal(18,2)
			,cv				decimal(18,2)
			,pct			integer
			,lvl			integer
			,lvl_paid		integer
			,rate			decimal(18,2)
			,bonus			varchar(50)
			,bonus_ex		varchar(50)
			,bonus_sub		decimal(18,2))
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		19-May-2017

Purpose:	Returns a resultset of Unilevel Translations that are paid up 7 compressed levels

-------------------------------------------------------------------------------- */

begin
	declare ln_Period_Batch_id		integer = gl_Period_Viewable(:pn_Period_id);
	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		select 
			 null as id
			,null as name 
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
		lc_Exchange =
			select *
			from gl_Exchange(:pn_Period_id);
			
		return
		select 
			 case rank() over (partition by p.customer_id order by t.order_number)
			 	when 1 then p.customer_id
			 	else null end																as id
			,case rank() over (partition by p.customer_id order by t.order_number)
			 	when 1 then c.customer_name
			 	else null end 																as name
			,t.order_number																	as trans_id
			,y.description																	as trans_type
			,p.pv
			,p.cv
			,p.percentage																	as pct
			,p.lvl
			,p.lvl_paid
			,p.exchange_rate																as rate
			,round(p.bonus,x1.round_factor) || ' ' || p.from_currency						as bonus
			,round(p.bonus_exchanged,x2.round_factor) || ' ' || p.to_currency				as bonus_ex
			,case rank() over (partition by p.customer_id order by t.order_number)
				when 1 then sum(p.bonus_exchanged) over (partition by p.customer_id)
				else null end 																as bonus_sub
		from Earning_01 p
			left outer join :lc_Exchange x1
				on x1.currency = p.from_currency
			left outer join :lc_Exchange x2
				on x2.currency = p.to_currency
			, customer_history c
			, transaction t
			, transaction_type y
		where p.customer_id = c.customer_id
		and p.transaction_id = t.transaction_id
		and p.transaction_type_id = y.transaction_type_id
		and p.transaction_customer_id = :pn_Customer_id
		and p.period_id = c.period_id
		and p.batch_id = c.batch_id
		and p.period_id = :pn_Period_id
		and p.batch_id = :ln_Period_Batch_id
		order by p.lvl,t.order_number;
	end if;
	
end;
