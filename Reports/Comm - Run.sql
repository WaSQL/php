call commissions.sp_Commission_Run(14, 0);
call commissions.sp_Commission_Run(15, 0);

/*
update commissions.period_batch
set 
 set_level			= 1
,set_volume			= 1
,set_volume_lrp		= 1
,set_volume_fs		= 1
,set_volume_retail	= 1
,set_volume_egv		= 1
,set_volume_tv		= 1
,set_volume_tw_cv	= 1
,set_volume_org		= 1
,set_rank			= 1
,set_Earning_1		= 1
,set_Earning_2		= 1
,set_Earning_3		= 1
,set_Earning_4		= 1
,set_Earning_5		= 1
,set_Earning_6		= 1
,set_Earning_7		= 1
,set_Earning_8		= 1
,set_Earning_9		= 1
,set_Earning_10		= 1
,set_Earning_11		= 1
,set_Earning_12		= 1
,set_Earning_13		= 1
where period_id in (12, 13, 14, 15)
and batch_id = 0;

call commissions.sp_Rank_High_Set(15);
