drop function commissions.FN_CUSTOMER_SEARCH;
create function commissions.FN_CUSTOMER_SEARCH
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			4/13/2017
*
* @describe     searches for customer records by ID & name
*
* @param		nvarchar ps_search_term
* @param		integer pn_period_id
* @param		varchar [ls_locale]
*
* @returns 		table
*				integer customer_id
*				nvarchar customer_name
*				integer period_id
*				integer period_type
*				varchar country
*				integer sponsor_id
*				integer enroller_id
*				integer rank_id
*				nvarchar rank_description
*				integer rank_high_id
*				nvarchar rank_high_description
*				nvarchar sponsor_name
*				nvarchar enroller_name
*				varchar zip_code
*				integer status
*				nvarchar status_description
*				integer type
*				nvarchar type_description
*				timestamp entry_date
*				timestamp comm_status_date
* @example      select * from commissions.fn_customer_search(1001, 15, 'EN-us')
-------------------------------------------------------*/
	(
							ps_search_term 	nvarchar(100)
						  , pn_period_id 	integer
						  , ls_Locale 		varchar(10) default 'EN-US')
returns table (
	  CUSTOMER_ID 			integer
	, CUSTOMER_NAME 		nvarchar(900)
	, PERIOD_ID 			integer
	, PERIOD_TYPE 			integer
	, COUNTRY 				varchar(4)
	, SPONSOR_ID 			integer
	, ENROLLER_ID 			integer
	, RANK_ID 				integer
	, RANK_DESCRIPTION 		nvarchar(25)
	, RANK_HIGH_ID 			integer
	, RANK_HIGH_DESCRIPTION nvarchar(25)
	, SPONSOR_NAME 			nvarchar(900)
	, ENROLLER_NAME 		nvarchar(900)
	, ZIP_CODE 				varchar(25)
	, STATUS 				integer
	, STATUS_DESCRIPTION 	nvarchar(50)
	, TYPE 					integer
	, TYPE_DESCRIPTION 		nvarchar(50)
	, ENTRY_DATE 			timestamp
	, COMM_STATUS_DATE 		timestamp
	)
	LANGUAGE SQLSCRIPT
	sql security invoker
   	DEFAULT SCHEMA Commissions
as
BEGIN
	declare ln_period_id	integer;
	declare ln_batch_id 	integer;
	declare ln_return_type 	integer;
	declare ln_match_check 	integer;
	declare ln_ret_count 	integer;
	
	ln_batch_id = gl_period_viewable(:pn_period_id);	
	
	if (gl_Period_isOpen(:pn_period_id) = 1) then --open period, read from customer
		--search IDs if it's a number
		if substr_regexpr('^\d+$' in :ps_search_term) is not null then
			return
			select top :ln_ret_count c.customer_id
				, c.customer_name
				, :pn_period_id as period_id
				, :ln_return_type as period_type
				, c.country
				, c.sponsor_id
				, c.enroller_id
				, c.rank_id
				, r.description rank_description
				, c.rank_high_id
				, rh.description rank_high_description
				, s.customer_name as sponsor_name
				, e.customer_name as enroller_name
				, null as zip_code
				, c.status_id as status
				, st.description as status_description
				, c.type_id as type
				, t.description as type_description
				, c.entry_date
				, ifnull(c.comm_status_date, c.entry_date) as comm_status_date
			from customer c
				left join rank r
					on r.rank_id = c.rank_id
				left join rank rh
					on rh.rank_id = c.rank_high_id
				left join customer s
					on s.customer_id = c.sponsor_id
				left join customer e
					on e.customer_id = c.enroller_id
				left join customer_type t
					on t.type_id = c.type_id
				left join customer_status st
					on st.status_id = c.status_id
			where c.customer_id = :ps_search_term;

		else --fuzzy search		
			return
			select top :ln_ret_count c.customer_id
				, c.customer_name
				, :pn_period_id as period_id
				, :ln_return_type as period_type
				, c.country
				, c.sponsor_id
				, c.enroller_id
				, c.rank_id
				, r.description rank_description
				, c.rank_high_id
				, rh.description rank_high_description
				, s.customer_name as sponsor_name
				, e.customer_name as enroller_name
				, null as zip_code
				, c.status_id as status
				, st.description as status_description
				, c.type_id as type
				, t.description as type_description
				, c.entry_date
				, ifnull(c.comm_status_date, c.entry_date) as comm_status_date
			from customer c
				left join rank r
					on r.rank_id = c.rank_id
				left join rank rh
					on rh.rank_id = c.rank_high_id
				left join customer s
					on s.customer_id = c.sponsor_id
				left join customer e
					on e.customer_id = c.enroller_id
				left join customer_type t
					on t.type_id = c.type_id
				left join customer_status st
					on st.status_id = c.status_id
			where lower(c.customer_name) like '%' || lower(:ps_search_term) || '%'
			--order by relevance
			order by case when lower(c.customer_name) like :ps_search_term || ',%' then 2 --last name match
						when lower(c.customer_name) like :ps_search_term || '%' then 3 --first part of name match
						when customer_name like_regexpr ' ' || :ps_search_term flag 'i' then 4--first name match
						else 5
						end;	
		end if;
	else --closed period, read from history
		--search IDs if it's a number
		if substr_regexpr('^\d+$' in :ps_search_term) is not null then
			return
			select top :ln_ret_count c.customer_id
				, c.customer_name
				, :pn_period_id as period_id
				, :ln_return_type as period_type
				, c.country
				, c.sponsor_id
				, c.enroller_id
				, c.rank_id
				, r.description rank_description
				, c.rank_high_id
				, rh.description rank_high_description
				, s.customer_name as sponsor_name
				, e.customer_name as enroller_name
				, null as zip_code
				, c.status_id as status
				, st.description as status_description
				, c.type_id as type
				, t.description as type_description
				, c.entry_date
				, ifnull(c.comm_status_date, c.entry_date) as comm_status_date
			from customer_history c
				, rank r
				, rank rh
				, customer_history s
				, customer_history e
				, customer_type t
				, customer_status st
			where r.rank_id = c.rank_id
				and rh.rank_id = c.rank_high_id
				and s.customer_id = c.sponsor_id
				and e.customer_id = c.enroller_id
				and c.period_id = :pn_period_id
				and s.period_id = c.period_id
				and s.batch_id = c.batch_id
				and e.period_id = c.period_id
				and s.batch_id = c.batch_id
				and c.batch_id = (select max(batch_id) from commissions.period_batch where period_id = :pn_period_id and viewable = 1)
				and t.type_id = c.type_id
				and st.status_id = c.status_id
				and c.customer_id = :ps_search_term;
		else
			return
			select top :ln_ret_count c.customer_id
				, c.customer_name
				, :pn_period_id as period_id
				, :ln_return_type as period_type
				, c.country
				, c.sponsor_id
				, c.enroller_id
				, c.rank_id
				, r.description rank_description
				, c.rank_high_id
				, rh.description rank_high_description
				, s.customer_name as sponsor_name
				, e.customer_name as enroller_name
				, null as zip_code
				, c.status_id as status
				, st.description as status_description
				, c.type_id as type
				, t.description as type_description
				, c.entry_date
				, ifnull(c.comm_status_date, c.entry_date) as comm_status_date
			from customer_history c
				, rank r
				, rank rh
				, customer_history s
				, customer_history e
				, customer_type t
				, customer_status st
			where r.rank_id = c.rank_id
				and rh.rank_id = c.rank_high_id
				and s.customer_id = c.sponsor_id
				and e.customer_id = c.enroller_id
				and c.period_id = :pn_period_id
				and s.period_id = c.period_id
				and s.batch_id = c.batch_id
				and e.period_id = c.period_id
				and s.batch_id = c.batch_id
				and c.batch_id = (select max(batch_id) from commissions.period_batch where period_id = :pn_period_id and viewable = 1)
				and t.type_id = c.type_id
				and st.status_id = c.status_id
				and lower(c.customer_name) like '%' || lower(:ps_search_term) || '%'
			--order by relevance
			order by case when lower(c.customer_name) like :ps_search_term || ',%' then 2 --last name match
						when lower(c.customer_name) like :ps_search_term || '%' then 3 --first part of name match
						when customer_name like_regexpr ' ' || :ps_search_term flag 'i' then 4--first of any part of name match
						else 5
						end;
		end if;--id/fuzzy
	end if;--live/history
END;