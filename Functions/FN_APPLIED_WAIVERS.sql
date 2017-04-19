drop function commissions.FN_APPLIED_WAIVERS;
create function commissions.FN_APPLIED_WAIVERS (
	pn_period_id integer
	, pn_customer_id integer
	, pn_customer_waiver_type_id integer
	)
	returns table(REQ_WAIVER_TYPE nvarchar(34), PERIOD_TYPE nvarchar(34), VAL varchar(48))
as
begin
	if (pn_period_id > 0) then
		return 
		select wv.customer_waiver_type_id || ' - ' || wtyp.description as REQ_WAIVER_TYPE
			, per.period_type_id || ' - ' || ptyp.description as PERIOD_TYPE
			, wv.VALUE_1 || ' - ' || rtyp.description VAL
		from commissions.customer_history_waiver wv
			, commissions.customer_waiver_type wtyp
			, commissions.period per
			, commissions.period_type ptyp
			, commissions.customer_rank_type rtyp
		where wtyp.customer_waiver_type_id = wv.customer_waiver_type_id
			and per.period_id = wv.period_id
			and ptyp.period_type_id = per.period_type_id
			and rtyp.rank_type_id = wv.value_1
			and wv.period_id = :pn_period_id
			and wv.customer_id = pn_customer_id
			and wv.customer_waiver_type_id = ifnull(:pn_customer_waiver_type_id, wv.customer_waiver_type_id);
	else
		select wv.customer_waiver_type_id || ' - ' || wtyp.description as REQ_WAIVER_TYPE
			, null as PERIOD_TYPE
			, wv.VALUE_1 || ' - ' || rtyp.description VAL
		from commissions.customer_waiver wv
			, commissions.customer_waiver_type wtyp
			, commissions.customer_rank_type rtyp
		where wtyp.customer_waiver_type_id = wv.customer_waiver_type_id
			and rtyp.rank_type_id = wv.value_1
			and wv.customer_id = :pn_customer_id
			and wv.customer_waiver_type_id = ifnull(:pn_customer_waiver_type_id, wv.customer_waiver_type_id);
	end if;	
end;