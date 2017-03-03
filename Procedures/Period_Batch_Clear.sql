drop procedure Commissions.Period_Batch_Clear;
create procedure Commissions.Period_Batch_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set
	   beg_date_run = Null
	  ,end_date_run = Null
	  ,beg_date_volume = Null
	  ,end_date_volume = Null
	  ,beg_date_volume_lrp = Null
	  ,end_date_volume_lrp = Null
	  ,beg_date_volume_fs = Null
	  ,end_date_volume_fs = Null
	  ,beg_date_volume_retail = Null
	  ,end_date_volume_retail = Null
	  ,beg_date_volume_egv = Null
	  ,end_date_volume_egv = Null
	  ,beg_date_volume_org = Null
	  ,end_date_volume_org = Null
	  ,beg_date_rank = Null
	  ,end_date_rank = Null
	  ,beg_date_payout_1 = Null
	  ,end_date_payout_1 = Null
	  ,beg_date_payout_2 = Null
	  ,end_date_payout_2 = Null
	  ,beg_date_payout_3 = Null
	  ,end_date_payout_3 = Null
	  ,beg_date_payout_4 = Null
	  ,end_date_payout_4 = Null
	  ,beg_date_payout_5 = Null
	  ,end_date_payout_5 = Null
	  ,beg_date_payout_6 = Null
	  ,end_date_payout_6 = Null
	  ,beg_date_payout_7 = Null
	  ,end_date_payout_7 = Null
	Where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	   
	commit;

end;
