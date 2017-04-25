call commissions.Commission_History_Run(13,0);

/*
update commissions.period_batch
set 
 clear_flag			= 0
,set_volume			= 0
,set_volume_lrp		= 0
,set_volume_fs		= 0
,set_volume_retail	= 0
,set_volume_egv		= 0
,set_volume_tv		= 0
,set_volume_org		= 0
,set_rank			= 0
,set_payout_1		= 0
,set_payout_2		= 0
,set_payout_3		= 1
where period_id = 13
and batch_id = 0;
