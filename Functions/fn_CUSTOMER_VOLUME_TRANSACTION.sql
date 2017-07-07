drop function commissions.fn_CUSTOMER_VOLUME_TRANSACTION;
create function commissions.fn_CUSTOMER_VOLUME_TRANSACTION	(
	pn_customer_id integer
	, pn_period_id integer
	, pn_volume_type integer
	)
	returns table (customer_id integer
		, transaction_id integer
		, volume decimal(18,2)
	)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
/*------------------------------------------------------
by Del Stirling
returns the transaction calculation for each volume type

example call:
select * from commissions.fn_customer_volume_transaction (1001, 14, 1)
-------------------------------------------------------*/
as 
BEGIN
	declare ln_batch_id integer;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN

	END;
	
	ln_batch_id = gl_period_viewable(:pn_period_id);
	
	if :pn_volume_type = 1 then -- pv
		return 
		select customer_id
			, order_number 			as transaction_id
			, pv 					as volume
		from gl_Volume_Pv_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 2 then --pv_lrp
		return 
		select customer_id
			, order_number 			as transaction_id
			, pv 					as volume
		from gl_Volume_Lrp_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;

	elseif :pn_volume_type = 3 then --pv_lrp_template
		return 
		select customer_id
			, order_number 			as transaction_id
			, 0 volume
		from transaction t
		where customer_id = :pn_customer_id
			and 1=2 --templates aren't available
			and t.period_id = :pn_period_id;

	elseif :pn_volume_type = 4 then --pv_retail
		return
		select transaction_customer_id	as customer_id
			, order_number 				as transaction_id
			, pv 						as volume
		from gl_Volume_Retail_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 5 then --pv_fs
		return 
		select customer_id
			, order_number 			as transaction_id
			, pv 					as volume
		from gl_Volume_Fs_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 6 then --cv
		return 
		select customer_id
			, order_number 			as transaction_id
			, cv 					as volume
		from gl_Volume_Pv_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 7 then --cv_lrp
		return 
		select customer_id
			, order_number 			as transaction_id
			, cv 					as volume
		from gl_Volume_Lrp_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 9 then --cv_retail
		return 
		select transaction_customer_id	as customer_id
			, order_number 				as transaction_id
			, cv 						as volume
		from gl_Volume_Retail_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 10 then --cv_fs
		return 
		select customer_id
			, order_number 				as transaction_id
			, cv 					as volume
		from gl_Volume_Fs_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 11 then --egv
		return 
		Select customer_id
			, order_number 				as transaction_id
			, pv 						as volume
		From  gl_Volume_Qual_Detail(:pn_period_id, :ln_batch_id) c
		Where c.country = 'KOR' 
		and c.customer_id = :pn_customer_id
		union all
		Select e.customer_id
			, e.order_number 				as transaction_id
			, e.pv 							as volume
		From  gl_Customer(:pn_period_id, :ln_batch_id) c
			, gl_Volume_Qual_Detail(:pn_period_id, :ln_batch_id) e
		Where c.customer_id = e.enroller_id
		and c.country = 'KOR'
		and c.customer_id = :pn_customer_id 
		and e.rank_high_id < 5;
			
	elseif :pn_volume_type = 12 then --pv_egv_lrp
		return 
		select customer_id
			, order_number 				as transaction_id
			, pv 						as volume
		from gl_Volume_Lrp_Detail(:pn_period_id, :ln_batch_id) c
		Where country = 'KOR' 
		and customer_id = :pn_customer_id
		union all
		select e.customer_id
			, e.order_number 			as transaction_id
			, e.pv 						as volume
		from gl_Customer(:pn_period_id, :ln_batch_id) c
			,gl_Volume_Lrp_Detail(:pn_period_id, :ln_batch_id) e
		Where c.customer_id = e.enroller_id
		and c.period_id = :pn_period_id
		and c.batch_id = :ln_batch_id
		and c.country = 'KOR'
		and c.customer_id = :pn_customer_id 
		and e.rank_high_id < 5;
	
	elseif :pn_volume_type = 14 then --TV
		return
		select 
			 customer_id
			,order_number 				as transaction_id
			,pv 						as volume
		from gl_Volume_Qual_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id
		or sponsor_id = :pn_customer_id
		order by sponsor_id, customer_id;
	
	elseif :pn_volume_type = 15 then --TW_CV
		return 
		select customer_id
			, order_number 				as transaction_id
			, cv 					as volume
		from gl_Volume_Tw_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;

	else --this is a placeholder since there are volumes that aren't being calculated yet.
		return
		select null as customer_id
			, null as transaction_id
			, null as volume
		from dummy 
		where 1=0;
				
	end if;
END;
