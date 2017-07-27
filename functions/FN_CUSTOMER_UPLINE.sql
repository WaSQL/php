drop function commissions.FN_CUSTOMER_UPLINE;
create function commissions.FN_CUSTOMER_UPLINE
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/20/2017
*
* @describe     returns a customer's upline via a manual loop (much faster than using a hierarchy statement)
*
* @param		integer pn_customer_id
* @param		integer pn_type_id type 
*				0 - sponsor tree type 1 - enroller tree
* @param		integer [pn_period_id]
*
* @returns 		table
*				integer customer_root_id
*				integer org_type_id
*				varchar org_type
*				integer customer_id
*				nvarchar customer_name
*				integer level_id
*				nvarchar sponsor_name
*				integer enroller_id
*				nvarchar enroller_name
*				decimal pv
*				decimal ov
*				integer rank_id
*				integer rank_title
* @example      select * from fn_customer_upline(4219710, 0, 12)
-------------------------------------------------------*/
	(
		pn_customer_id 	integer
		, pn_type_id 	integer
		, pn_period_id 	integer default null
	)
returns table (Customer_Root_id	integer
			  ,Org_Type_id		integer
			  ,Org_Type			varchar(50)
			  ,Customer_id		integer
			  ,Customer_name	nvarchar(900)
			  ,Level_id			integer
			  ,Sponsor_id		integer
			  ,Sponsor_name		nvarchar(900)
			  ,Enroller_id		integer
			  ,Enroller_name	nvarchar(900)
			  ,PV				decimal(18,8)
			  ,OV				decimal(18,8)
			  ,Rank_id			integer
			  ,Rank_Title		integer)
	LANGUAGE SQLSCRIPT
	sql security invoker
   	DEFAULT SCHEMA Commissions	
as
BEGIN
	declare ar_root_customer_id integer array;
	declare ar_org_type_id integer array;
	declare ar_org_type varchar(50) array;
	declare ar_customer_id integer array;
	declare ar_customer_name nvarchar(900) array;
	declare ar_level_id integer array;
	declare ar_sponsor_id integer array;
	declare ar_sponsor_name nvarchar(900) array;
	declare ar_enroller_id integer array;
	declare ar_enroller_name nvarchar(900) array;
	declare ar_pv decimal(18,8) array;
	declare ar_ov decimal(18,8) array;
	declare ar_rank_id integer array;
	declare ar_rank_title integer array;

	declare ln_root_customer_id integer;
	declare ln_org_type_id integer;
	declare ls_org_type varchar(50);
	declare ln_customer_id integer := :pn_customer_id;
	declare ls_customer_name nvarchar(900);
	declare ln_level_id integer;
	declare ln_sponsor_id integer;
	declare ls_sponsor_name nvarchar(900);
	declare ln_enroller_id integer;
	declare ls_enroller_name nvarchar(900);
	declare ld_pv decimal(18,8);
	declare ld_ov decimal(18,8);
	declare ln_rank_id integer;
	declare ln_rank_title integer;

	declare ln_loop_count integer := 0;
	declare ln_type_id integer := ifnull(:pn_type_id, 0);
	declare ls_type varchar(50);
	
	
	if :ln_type_id = 0 then 
		ls_type := 'Sponsor';
	else 
		ls_type := 'Enroller';
	end if;
	

	while (ifnull(ln_customer_id, 0) != 0) do
		DECLARE EXIT HANDLER FOR SQL_ERROR_CODE 1299
		BEGIN
		   ln_customer_id :=0;
		END;		
		ln_loop_count := :ln_loop_count + 1;
		if (:pn_period_id is null or gl_period_isopen(:pn_period_id) = 1) then
			select :pn_customer_id
				, :ln_type_id
				, :ls_type
				, c.customer_id
				, c.customer_name
				, :ln_loop_count
				, c.sponsor_id
				, s.customer_name
				, c.enroller_id
				, e.customer_name
				, round(c.vol_1,2)
				, round(c.vol_12,2)
				, c.rank_id
				, c.rank_high_id
			into ln_root_customer_id
				, ln_org_type_id
				, ls_org_type
				, ln_customer_id
				, ls_customer_name
				, ln_level_id
				, ln_sponsor_id
				, ls_sponsor_name
				, ln_enroller_id
				, ls_enroller_name
				, ld_pv
				, ld_ov
				, ln_rank_id
				, ln_rank_title
			from customer c
				left join customer s on s.customer_id = c.sponsor_id
				left join customer e on e.customer_id = c.enroller_id
			where c.customer_id = :ln_customer_id;
		else
			select :pn_customer_id
				, :ln_type_id
				, :ls_type
				, c.customer_id
				, c.customer_name
				, :ln_loop_count
				, c.sponsor_id
				, s.customer_name
				, c.enroller_id
				, e.customer_name
				, round(c.vol_1,2)
				, round(c.vol_12,2)
				, c.rank_id
				, c.rank_high_id
			into ln_root_customer_id
				, ln_org_type_id
				, ls_org_type
				, ln_customer_id
				, ls_customer_name
				, ln_level_id
				, ln_sponsor_id
				, ls_sponsor_name
				, ln_enroller_id
				, ls_enroller_name
				, ld_pv
				, ld_ov
				, ln_rank_id
				, ln_rank_title
			from customer_history c
				left join customer_history s 
					on s.customer_id = c.sponsor_id 
					and s.period_id = c.period_id 
					and s.batch_id = c.batch_id
				left join customer_history e 
					on e.customer_id = c.enroller_id 
					and e.period_id = c.period_id 
					and e.batch_id = c.batch_id
			where c.customer_id = :ln_customer_id
				and c.period_id = :pn_period_id
				and c.batch_id = gl_period_viewable(:pn_period_id);
		end if;
		
		
		ar_root_customer_id[:ln_loop_count] := ln_root_customer_id;
		ar_org_type_id[:ln_loop_count] := ln_org_type_id;
		ar_org_type[:ln_loop_count] := ls_org_type;
		ar_customer_id[:ln_loop_count] := ln_customer_id;
		ar_customer_name[:ln_loop_count] := ls_customer_name;
		ar_level_id[:ln_loop_count] := ln_level_id;
		ar_sponsor_id[:ln_loop_count] := ln_sponsor_id;
		ar_sponsor_name[:ln_loop_count] := ls_sponsor_name;
		ar_enroller_id[:ln_loop_count] := ln_enroller_id;
		ar_pv[:ln_loop_count] := ld_pv;
		ar_ov[:ln_loop_count] := ld_ov;
		ar_rank_id[:ln_loop_count] := ln_rank_id;
		ar_rank_title[:ln_loop_count] := ln_rank_title;
		
		if (ifnull(:pn_type_id, 0)= 0) then
			ln_customer_id := ln_sponsor_id;
		else
			ln_customer_id := ln_enroller_id;
		end if;		
	end while;
	
	var_out = UNNEST(:ar_root_customer_id
					, :ar_org_type_id
					, :ar_org_type
					, :ar_customer_id
					, :ar_customer_name
					, :ar_level_id
					, :ar_sponsor_id
					, :ar_sponsor_name
					, :ar_enroller_id
					, :ar_enroller_name
					, :ar_pv
					, :ar_ov
					, :ar_rank_id
					, :ar_rank_title) 
				AS ("CUSTOMER_ROOT_ID"
					, "ORG_TYPE_ID"
					, "ORG_TYPE"
					, "CUSTOMER_ID"
					, "CUSTOMER_NAME"
					, "LEVEL_ID"
					, "SPONSOR_ID"
					, "SPONSOR_NAME"
					, "ENROLLER_ID"
					, "ENROLLER_NAME"
					, "PV"
					, "OV"
					, "RANK_ID"
					, "RANK_TITLE");
	return :var_out;
	
END;
