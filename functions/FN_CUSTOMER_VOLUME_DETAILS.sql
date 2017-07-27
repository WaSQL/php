DROP FUNCTION COMMISSIONS.FN_CUSTOMER_VOLUME_DETAILS;
create function commissions.FN_CUSTOMER_VOLUME_DETAILS
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/2/2017
*
* @describe     returns the frontline volumes of a customer
*
* @param		integer pn_customer_id
* @param		integer pn_period_id
* @param		integer pn_volume_type_flag
* @param		varchar [locale]
*
* @returns 		table
*				integer customer_id
*				nvarchar customer_name
*				decimal amount
*				decimal downline_level
*				decimal sponsor_count
*
* @example      select * from commissions.fn_customer_volume_details(1001, 13, 13)
-------------------------------------------------------*/
	(
		pn_customer_id 			integer
		, pn_period_id 			integer
		, pn_volume_type_flag 	integer
		, ls_locale				varchar(20) default 'en-US'
	)
	returns table (
		CUSTOMER_ID 		integer
		, CUSTOMER_NAME 	nvarchar(900)
		, AMOUNT 			decimal(18,8)
		, DOWNLINE_LEVEL 	decimal
		, SPONSOR_COUNT 	decimal)
	LANGUAGE SQLSCRIPT
	DEFAULT SCHEMA commissions
as 
BEGIN
	if gl_period_isopen(:pn_period_id) = 1 then
		return 
		select c.customer_id
			, c.customer_name
			, case :pn_volume_type_flag when 1 then c.vol_1
				when 2 then c.vol_2
				when 3 then c.vol_3
				when 4 then c.vol_4
				when 5 then c.vol_5
				when 6 then c.vol_6
				when 7 then c.vol_7
				when 8 then c.vol_8
				when 9 then c.vol_9
				when 10 then c.vol_10
				when 11 then c.vol_11
				when 12 then c.vol_12
				when 13 then c.vol_13
				when 14 then c.vol_14
				else 0
				end as amount
			, 1 as downline_level
			, count(dn.customer_id) as sponsor_count
		from customer c
			left join customer dn
				on dn.sponsor_id = c.customer_id
		where c.sponsor_id = :pn_customer_id
			and c.vol_13 > case when :pn_volume_type_flag = 13 then 0 else -1 end
		group by c.customer_id
			, c.customer_name
			, c.vol_1
			, c.vol_2
			, c.vol_3
			, c.vol_4
			, c.vol_5
			, c.vol_6
			, c.vol_7
			, c.vol_8
			, c.vol_9
			, c.vol_10
			, c.vol_11
			, c.vol_12
			, c.vol_13
			, c.vol_14;			
	else 
		return 
		select c.customer_id
			, c.customer_name
			, case :pn_volume_type_flag when 1 then c.vol_1
				when 2 then c.vol_2
				when 3 then c.vol_3
				when 4 then c.vol_4
				when 5 then c.vol_5
				when 6 then c.vol_6
				when 7 then c.vol_7
				when 8 then c.vol_8
				when 9 then c.vol_9
				when 10 then c.vol_10
				when 11 then c.vol_11
				when 12 then c.vol_12
				when 13 then c.vol_13
				when 14 then c.vol_14
				else 0
				end as amount
			, 1 as downline_level
			, count(dn.customer_id) as sponsor_count
		from customer_history c
			left join customer_history dn
				on dn.sponsor_id = c.customer_id
					and dn.period_id = c.period_id
					and dn.batch_id = c.batch_id
		where c.sponsor_id = :pn_customer_id
			and c.period_id = :pn_period_id
			and c.batch_id = gl_period_viewable(:pn_period_id)
			and c.vol_13 > case when :pn_volume_type_flag = 13 then 0 else -1 end
		group by c.customer_id
			, c.customer_name
			, c.vol_1
			, c.vol_2
			, c.vol_3
			, c.vol_4
			, c.vol_5
			, c.vol_6
			, c.vol_7
			, c.vol_8
			, c.vol_9
			, c.vol_10
			, c.vol_11
			, c.vol_12
			, c.vol_13
			, c.vol_14;			
	end if;
END;