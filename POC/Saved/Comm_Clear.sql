drop procedure COMM_CLEAR;
create procedure COMM_CLEAR(
            pn_Period_id      Integer)
LANGUAGE SQLSCRIPT AS

Begin

	Update comm_period
	Set
	   date_start = Null
	  ,date_end = Null
	  ,date_srt_dist = Null
	  ,date_end_dist = Null
	  ,date_srt_order = Null
	  ,date_end_order = Null
	  ,date_srt_hier = Null
	  ,date_end_hier = Null
	  ,date_srt_vol = Null
	  ,date_end_vol = Null
	  ,date_srt_vol_lrp = Null
	  ,date_end_vol_lrp = Null
	  ,date_srt_vol_fs = Null
	  ,date_end_vol_fs = Null
	  ,date_srt_vol_retail = Null
	  ,date_end_vol_retail = Null
	  ,date_srt_vol_egv = Null
	  ,date_end_vol_egv = Null
	  ,date_srt_vol_org = Null
	  ,date_end_vol_org = Null
	  ,date_srt_rank = Null
	  ,date_end_rank = Null
	  ,date_srt_payout_1_detail = Null
	  ,date_end_payout_1_detail = Null
	  ,date_srt_payout_1 = Null
	  ,date_end_payout_1 = Null
	  ,date_srt_payout_2_detail = Null
	  ,date_end_payout_2_detail = Null
	  ,date_srt_payout_2 = Null
	  ,date_end_payout_2 = Null
	Where period_id = :pn_Period_id;
	   
	commit;

	update comm_dist
   	set
   	   pv = 0
   	  ,cv = 0
   	  ,pv_fs = 0
   	  ,cv_fs = 0
   	  ,pv_retail = 0
   	  ,cv_retail = 0
   	  ,pv_egv = 0
   	  ,pv_org = 0
	  ,rank_id = 0
      ,rank_qual = 0
      ,leg_rank_dist_id = 0
   	  ,leg_rank_id = 0
 	Where period_id = :pn_Period_id;
   
	delete
	from comm_legs
	where period_id = 2;
	
	delete
	from comm_payout_2_detail
	where period_id = :pn_Period_id;
   
end