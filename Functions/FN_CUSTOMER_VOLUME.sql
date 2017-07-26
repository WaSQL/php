DROP FUNCTION COMMISSIONS.FN_CUSTOMER_VOLUME;
create function commissions.FN_CUSTOMER_VOLUME
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/2/2017
*
* @describe     Gets the different volume types for a customer
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		varchar [ls_locale]
*
* @returns 		table
*				decimal pv
*				decimal pv_lrp
*				decimal pv_lrp_template_high
*				decimal pv_retail
*				decimal pv_fs
*				decimal cv
*				decimal cv_lrp
*				decimal cv_lrp_template
*				decimal cv_retail
*				decimal cv_fs
*				decimal egv
*				decimal egv_lrp
*				decimal ov
*				decimal tv

* @example      select * from commissions.customer_history_rank(1001)
-------------------------------------------------------*/
(pn_customer_id 	integer
,pn_period_id 		integer
,ls_locale			varchar(20) default 'en-US')
	returns table (
		PV 						decimal(18,2)
		, PV_LRP 				decimal(18,2)
		, PV_LRP_TEMPLATE 		decimal(18,2)
		, pv_lrp_template_high	decimal(18,2)
		, PV_RETAIL 			decimal(18,2)
		, PV_FS 				decimal(18,2)
		, CV 					decimal(18,2)
		, CV_LRP				decimal(18,2)
		, CV_LRP_TEMPLATE 		decimal(18,2)
		, CV_RETAIL 			decimal(18,2)
		, CV_FS 				decimal(18,2)
		, EGV 					decimal(18,2)
		, EGV_LRP 				decimal(18,2)
		, OV 					decimal(18,2)
		, TV 					decimal(18,2))
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
		,0 	as pv_lrp_template_high
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
	from gl_customer(:pn_Period_id, :ln_Period_Batch_id, :pn_Customer_id)
	where customer_id = :pn_customer_id;
END;