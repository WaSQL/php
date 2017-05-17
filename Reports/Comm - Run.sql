call commissions.Commission_History_Run(12, 0);
call commissions.Commission_History_Run(13, 0);

/*
update commissions.period_batch
set 
 set_volume			= 1
,set_volume_lrp		= 1
,set_volume_fs		= 1
,set_volume_retail	= 1
,set_volume_egv		= 1
,set_volume_tv		= 1
,set_volume_tw_cv	= 1
,set_volume_org		= 1
,set_rank			= 1
,set_payout_1		= 1
,set_payout_2		= 1
,set_payout_3		= 1
,set_payout_4		= 1
,set_payout_5		= 1
,set_payout_6		= 1
,set_payout_7		= 1
,set_payout_8		= 1
,set_payout_9		= 1
,set_payout_10		= 1
,set_payout_11		= 1
where period_id in (12, 13)
and batch_id = 0;
