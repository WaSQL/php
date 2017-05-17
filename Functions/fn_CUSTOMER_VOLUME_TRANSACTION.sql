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
	DEFAULT SCHEMA commissions
as 
BEGIN
	declare ln_batch_id integer;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN

	END;
	
	select max(batch_id)
	into ln_batch_id 
	from period_batch where period_id = :pn_period_id;
	
	if :pn_volume_type = 1 then -- pv
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, pv 					as volume
		from fn_volume_pv_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 2 then --pv_lrp
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, pv 					as volume
		from fn_volume_pv_lrp_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;

	elseif :pn_volume_type = 3 then --pv_lrp_template
		return 
		select t.customer_id
			, t.transaction_id
			, 0 volume
		from transaction t
		where customer_id = :pn_customer_id
			and 1=2 --templates aren't available
			and t.period_id = :pn_period_id;

	elseif :pn_volume_type = 4 then --pv_retail
		return
		select transaction_customer_id	as customer_id
			, transaction_number 		as transaction_id
			, pv 						as volume
		from fn_volume_pv_retail_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 5 then --pv_fs
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, pv 					as volume
		from fn_volume_pv_fs_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 6 then --cv
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, cv 					as volume
		from fn_volume_pv_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 7 then --cv_lrp
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, cv 					as volume
		from fn_volume_pv_lrp_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 9 then --cv_retail
		return 
		select transaction_customer_id	as customer_id
			, transaction_number 		as transaction_id
			, cv 						as volume
		from fn_volume_pv_retail_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
			
	elseif :pn_volume_type = 10 then --cv_fs
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, cv 					as volume
		from fn_volume_pv_fs_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
	
	elseif :pn_volume_type = 14 then --TV
		return
		select 
			 transaction_customer_id	as customer_id
			,transaction_number 		as transaction_id
			,pv 						as volume
		from fn_Volume_Pv_Qual_Detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id
		or sponsor_id = :pn_customer_id
		order by sponsor_id, customer_id;
	
	elseif :pn_volume_type = 15 then --TW_CV
		return 
		select customer_id
			, transaction_number 	as transaction_id
			, cv 					as volume
		from fn_volume_tw_cv_detail(:pn_period_id, :ln_batch_id)
		where customer_id = :pn_customer_id;
				
	end if;
END;
