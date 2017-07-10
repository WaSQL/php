drop function commissions.fn_Customer_Volume;
create function commissions.fn_Customer_Volume(
								  pn_Customer_id 	integer
								, pn_Period_id 		integer)
returns table (
	  PV 				decimal(18,2)
	, PV_LRP 			decimal(18,2)
	, PV_LRP_TEMPLATE 	decimal(18,2)
	, PV_RETAIL 		decimal(18,2)
	, PV_FS 			decimal(18,2)
	, CV 				decimal(18,2)
	, CV_LRP 			decimal(18,2)
	, CV_LRP_TEMPLATE 	decimal(18,2)
	, CV_RETAIL 		decimal(18,2)
	, CV_FS 			decimal(18,2)
	, EGV 				decimal(18,2)
	, EGV_LRP 			decimal(18,2)
	, OV 				decimal(18,2)
	, TV 				decimal(18,2)
	, TW_CV 			decimal(18,2))
	LANGUAGE SQLSCRIPT
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_Period_Batch_id	integer = gl_Period_Viewable(:pn_Period_id);
	
	return
	select
		 pv
		,pv_lrp
		,pv_lrp_template
		,pv_retail
		,pv_fs
		,cv
		,cv_lrp
		,cv_lrp_template
		,cv_retail
		,cv_fs
		,egv
		,egv_lrp
		,ov
		,tv
		,tw_cv
	from gl_customer(:pn_Period_id, :ln_Period_Batch_id, :pn_Customer_id)
	where customer_id = :pn_customer_id;
END;
