drop procedure Commissions.sp_Period_Batch_Set;
create procedure Commissions.sp_Period_Batch_Set(
					 pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update period_batch
	set viewable = 0
	where period_id = :pn_Period_id;
	
	-- Create New Batch
	insert into period_batch
	select
		(select ifnull(max(batch_id)+1,0) 
		 from period_batch 
		 where period_id = p.period_id)	as batch_id
		,p.period_id					as period_id
		,current_timestamp				as entry_date
		,1								as viewable
		,t.clear_flag					as clear_flag
		,1					as set_level
		,t.set_volume					as set_volume
		,t.set_volume_lrp				as set_volume_lrp
		,t.set_volume_fs				as set_volume_fs
		,t.set_volume_retail			as set_volume_retail
		,t.set_volume_egv				as set_volume_egv
		,t.set_volume_tv				as set_volume_tv
		,t.set_volume_tw_cv				as set_volume_tw_cv
		,t.set_volume_org				as set_volume_org
		,t.set_rank						as set_rank
		,t.set_Earning_1					as set_Earning_1
		,t.set_Earning_2					as set_Earning_2
		,t.set_Earning_3					as set_Earning_3
		,t.set_Earning_4					as set_Earning_4
		,t.set_Earning_5					as set_Earning_5
		,t.set_Earning_6					as set_Earning_6
		,t.set_Earning_7					as set_Earning_7
		,t.set_Earning_8					as set_Earning_8
		,t.set_Earning_9					as set_Earning_9
		,t.set_Earning_10				as set_Earning_10
		,t.set_Earning_11				as set_Earning_11
		,t.set_Earning_12				as set_Earning_12
		,t.set_Earning_13				as set_Earning_13
		,null							as beg_date_clear
		,null							as end_date_clear
		,null							as beg_date_run
		,null							as end_date_run
		,null							as beg_date_level
		,null							as end_date_level
		,null							as beg_date_volume
		,null							as end_date_volume
		,null							as beg_date_volume_lrp
		,null							as end_date_volume_lrp
		,null							as beg_date_volume_fs
		,null							as end_date_volume_fs
		,null							as beg_date_volume_retail
		,null							as end_date_volume_retail
		,null							as beg_date_volume_egv
		,null							as end_date_volume_egv
		,null							as beg_date_volume_tv
		,null							as end_date_volume_tv
		,null							as beg_date_volume_tw_cv
		,null							as end_date_volume_tw_cv
		,null							as beg_date_volume_org
		,null							as end_date_volume_org
		,null							as beg_date_rank
		,null							as end_date_rank
		,null							as beg_date_Earning_1
		,null							as end_date_Earning_1
		,null							as beg_date_Earning_2
		,null							as end_date_Earning_2
		,null							as beg_date_Earning_3
		,null							as end_date_Earning_3
		,null							as beg_date_Earning_4
		,null							as end_date_Earning_4
		,null							as beg_date_Earning_5
		,null							as end_date_Earning_5
		,null							as beg_date_Earning_6
		,null							as end_date_Earning_6
		,null							as beg_date_Earning_7
		,null							as end_date_Earning_7
		,null							as beg_date_Earning_8
		,null							as end_date_Earning_8
		,null							as beg_date_Earning_9
		,null							as end_date_Earning_9
		,null							as beg_date_Earning_10
		,null							as end_date_Earning_10
		,null							as beg_date_Earning_11
		,null							as end_date_Earning_11
		,null							as beg_date_Earning_12
		,null							as end_date_Earning_12
		,null							as beg_date_Earning_13
		,null							as end_date_Earning_13
	from period p, period_template t
	where p.period_type_id = t.period_type_id
	and p.period_id = :pn_Period_id;
			
	commit;
			
	-- Snapshot Customer and all supporting tables
	call sp_Customer_Snap(:pn_Period_id);
	call sp_Customer_Flag_Snap(:pn_Period_id);
	call sp_Req_Qual_Leg_Snap(:pn_Period_id);
	call sp_Req_Cap_Snap(:pn_Period_id);
	call sp_Req_Unilevel_Snap(:pn_Period_id);
	call sp_Req_Power3_Snap(:pn_Period_id);
	call sp_Req_Pool_Snap(:pn_Period_id);
	call sp_Req_Preferred_Snap(:pn_Period_id);

end;
