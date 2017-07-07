drop procedure Commissions.sp_Period_Batch_Clear;
create procedure Commissions.sp_Period_Batch_Clear(
					 pn_Period_id		Integer
					,pn_Period_Batch_id	Integer)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
   declare ln_Set_Level           	Integer;
   declare ln_Set_Volume           	Integer;
   declare ln_Set_Volume_Lrp       	Integer;
   declare ln_Set_Volume_FS        	Integer;
   declare ln_Set_Volume_Retail    	Integer;
   declare ln_Set_Volume_EGV       	Integer;
   declare ln_Set_Volume_TV       	Integer;
   declare ln_Set_Volume_TW_CV     	Integer;
   declare ln_Set_Volume_Org       	Integer;
   declare ln_Set_Rank             	Integer;
   declare ln_Set_Earning_1  		Integer;
   declare ln_Set_Earning_2        	Integer;
   declare ln_Set_Earning_3  		Integer;
   declare ln_Set_Earning_4        	Integer;
   declare ln_Set_Earning_5        	Integer;
   declare ln_Set_Earning_6        	Integer;
   declare ln_Set_Earning_7        	Integer;
   declare ln_Set_Earning_8        	Integer;
   declare ln_Set_Earning_9        	Integer;
   declare ln_Set_Earning_10       	Integer;
   declare ln_Set_Earning_11       	Integer;
   declare ln_Set_Earning_12       	Integer;
   declare ln_Set_Earning_13       	Integer;
   
	Select 
		set_level
	  , set_volume
	  , set_volume_lrp
	  , set_volume_fs
	  , set_volume_retail
	  , set_volume_egv
	  , set_volume_tv
	  , set_volume_tw_cv
	  , set_volume_org
	  , set_rank
	  , set_Earning_1
	  , set_Earning_2
	  , set_Earning_3
	  , set_Earning_4
	  , set_Earning_5
	  , set_Earning_6
	  , set_Earning_7
	  , set_Earning_8
	  , set_Earning_9
	  , set_Earning_10
	  , set_Earning_11
	  , set_Earning_12
	  , set_Earning_13
   	Into 
   	  	ln_Set_Level
      , ln_Set_Volume
      , ln_Set_Volume_Lrp
      , ln_Set_Volume_FS
      , ln_Set_Volume_Retail
      , ln_Set_Volume_EGV
      , ln_Set_Volume_TV
      , ln_Set_Volume_TW_CV
      , ln_Set_Volume_Org
      , ln_Set_Rank
      , ln_Set_Earning_1
      , ln_Set_Earning_2
      , ln_Set_Earning_3
      , ln_Set_Earning_4
      , ln_Set_Earning_5
      , ln_Set_Earning_6
      , ln_Set_Earning_7
      , ln_Set_Earning_8
      , ln_Set_Earning_9
      , ln_Set_Earning_10
      , ln_Set_Earning_11
      , ln_Set_Earning_12
      , ln_Set_Earning_13
	From  period_batch
	Where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
   
	Update period_batch
	Set
	   beg_date_run = Null
	  ,end_date_run = Null
	  ,beg_date_level = 		map(:ln_Set_Level,1,null,beg_date_level)
	  ,end_date_level = 		map(:ln_Set_Level,1,null,end_date_level)
	  ,beg_date_volume = 		map(:ln_Set_Volume,1,null,beg_date_volume)
	  ,end_date_volume = 		map(:ln_Set_Volume,1,null,end_date_volume)
	  ,beg_date_volume_lrp = 	map(:ln_Set_Volume_Lrp,1,null,beg_date_volume_lrp)
	  ,end_date_volume_lrp = 	map(:ln_Set_Volume_Lrp,1,null,end_date_volume_lrp)
	  ,beg_date_volume_fs = 	map(:ln_Set_Volume_FS,1,null,beg_date_volume_fs)
	  ,end_date_volume_fs = 	map(:ln_Set_Volume_FS,1,null,end_date_volume_fs)
	  ,beg_date_volume_retail = map(:ln_Set_Volume_Retail,1,null,beg_date_volume_retail)
	  ,end_date_volume_retail = map(:ln_Set_Volume_Retail,1,null,end_date_volume_retail)
	  ,beg_date_volume_egv = 	map(:ln_Set_Volume_EGV,1,null,beg_date_volume_egv)
	  ,end_date_volume_egv = 	map(:ln_Set_Volume_EGV,1,null,end_date_volume_egv)
	  ,beg_date_volume_tv = 	map(:ln_Set_Volume_TV,1,null,beg_date_volume_tv)
	  ,end_date_volume_tv = 	map(:ln_Set_Volume_TV,1,null,end_date_volume_tv)
	  ,beg_date_volume_tw_cv = 	map(:ln_Set_Volume_TW_CV,1,null,beg_date_volume_tw_cv)
	  ,end_date_volume_tW_cv = 	map(:ln_Set_Volume_TW_CV,1,null,end_date_volume_tW_cv)
	  ,beg_date_volume_org = 	map(:ln_Set_Volume_Org,1,null,beg_date_volume_org)
	  ,end_date_volume_org = 	map(:ln_Set_Volume_Org,1,null,end_date_volume_org)
	  ,beg_date_rank = 			map(:ln_Set_Rank,1,null,beg_date_rank)
	  ,end_date_rank = 			map(:ln_Set_Rank,1,null,end_date_rank)
	  ,beg_date_Earning_1 = 	map(:ln_Set_Earning_1,1,null,beg_date_Earning_1)
	  ,end_date_Earning_1 = 	map(:ln_Set_Earning_1,1,null,end_date_Earning_1)
	  ,beg_date_Earning_2 = 	map(:ln_Set_Earning_2,1,null,beg_date_Earning_2)
	  ,end_date_Earning_2 = 	map(:ln_Set_Earning_2,1,null,end_date_Earning_2)
	  ,beg_date_Earning_3 = 	map(:ln_Set_Earning_3,1,null,beg_date_Earning_3)
	  ,end_date_Earning_3 = 	map(:ln_Set_Earning_3,1,null,end_date_Earning_3)
	  ,beg_date_Earning_4 = 	map(:ln_Set_Earning_4,1,null,beg_date_Earning_4)
	  ,end_date_Earning_4 = 	map(:ln_Set_Earning_4,1,null,end_date_Earning_4)
	  ,beg_date_Earning_5 = 	map(:ln_Set_Earning_5,1,null,beg_date_Earning_5)
	  ,end_date_Earning_5 = 	map(:ln_Set_Earning_5,1,null,end_date_Earning_5)
	  ,beg_date_Earning_6 = 	map(:ln_Set_Earning_6,1,null,beg_date_Earning_6)
	  ,end_date_Earning_6 = 	map(:ln_Set_Earning_6,1,null,end_date_Earning_6)
	  ,beg_date_Earning_7 = 	map(:ln_Set_Earning_7,1,null,beg_date_Earning_7)
	  ,end_date_Earning_7 = 	map(:ln_Set_Earning_7,1,null,end_date_Earning_7)
	  ,beg_date_Earning_8 = 	map(:ln_Set_Earning_8,1,null,beg_date_Earning_8)
	  ,end_date_Earning_8 = 	map(:ln_Set_Earning_8,1,null,end_date_Earning_8)
	  ,beg_date_Earning_9 = 	map(:ln_Set_Earning_9,1,null,beg_date_Earning_9)
	  ,end_date_Earning_9 = 	map(:ln_Set_Earning_9,1,null,end_date_Earning_9)
	  ,beg_date_Earning_10 = 	map(:ln_Set_Earning_10,1,null,beg_date_Earning_10)
	  ,end_date_Earning_10 = 	map(:ln_Set_Earning_10,1,null,end_date_Earning_10)
	  ,beg_date_Earning_11 = 	map(:ln_Set_Earning_11,1,null,beg_date_Earning_11)
	  ,end_date_Earning_11 = 	map(:ln_Set_Earning_11,1,null,end_date_Earning_11)
	  ,beg_date_Earning_12 = 	map(:ln_Set_Earning_12,1,null,beg_date_Earning_12)
	  ,end_date_Earning_12 = 	map(:ln_Set_Earning_12,1,null,end_date_Earning_12)
	  ,beg_date_Earning_13 = 	map(:ln_Set_Earning_13,1,null,beg_date_Earning_13)
	  ,end_date_Earning_13 = 	map(:ln_Set_Earning_13,1,null,end_date_Earning_13)
	Where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	   
	commit;

end;
