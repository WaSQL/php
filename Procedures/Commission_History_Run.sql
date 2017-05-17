drop Procedure Commissions.Commission_History_Run;
create Procedure Commissions.Commission_History_Run(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

Begin
   declare ln_Clear				   	Integer;
   declare ln_Set_Volume           	Integer;
   declare ln_Set_Volume_Lrp       	Integer;
   declare ln_Set_Volume_FS        	Integer;
   declare ln_Set_Volume_Retail    	Integer;
   declare ln_Set_Volume_EGV       	Integer;
   declare ln_Set_Volume_TV       	Integer;
   declare ln_Set_Volume_TW_CV     	Integer;
   declare ln_Set_Volume_Org       	Integer;
   declare ln_Set_Rank             	Integer;
   declare ln_Set_Payout_1  		Integer;
   declare ln_Set_Payout_2         	Integer;
   declare ln_Set_Payout_3  		Integer;
   declare ln_Set_Payout_4         	Integer;
   declare ln_Set_Payout_5         	Integer;
   declare ln_Set_Payout_6         	Integer;
   declare ln_Set_Payout_7         	Integer;
   declare ln_Set_Payout_8         	Integer;
   declare ln_Set_Payout_9         	Integer;
   declare ln_Set_Payout_10        	Integer;
   declare ln_Set_Payout_11        	Integer;
   declare ln_Set_Payout_12        	Integer;
   declare ln_Set_Payout_13        	Integer;
   
   Select 
        clear_flag
      , set_volume
      , set_volume_lrp
      , set_volume_fs
      , set_volume_retail
      , set_volume_egv
      , set_volume_tv
      , set_volume_tw_cv
      , set_volume_org
      , set_rank
      , set_payout_1
      , set_payout_2
      , set_payout_3
      , set_payout_4
      , set_payout_5
      , set_payout_6
      , set_payout_7
      , set_payout_8
      , set_payout_9
      , set_payout_10
      , set_payout_11
      , set_payout_12
      , set_payout_13
   Into 
        ln_Clear
      , ln_Set_Volume
      , ln_Set_Volume_Lrp
      , ln_Set_Volume_FS
      , ln_Set_Volume_Retail
      , ln_Set_Volume_EGV
      , ln_Set_Volume_TV
      , ln_Set_Volume_TW_CV
      , ln_Set_Volume_Org
      , ln_Set_Rank
      , ln_Set_Payout_1
      , ln_Set_Payout_2
      , ln_Set_Payout_3
      , ln_Set_Payout_4
      , ln_Set_Payout_5
      , ln_Set_Payout_6
      , ln_Set_Payout_7
      , ln_Set_Payout_8
      , ln_Set_Payout_9
      , ln_Set_Payout_10
      , ln_Set_Payout_11
      , ln_Set_Payout_12
      , ln_Set_Payout_13
   From  period_batch
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;

	-- Clear Commission
	call commission_history_clear(:pn_Period_id, :pn_Period_Batch_id);
   
   Update period_batch
   Set
       beg_date_run = current_timestamp
      ,end_date_run = Null
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
	
   -- Set Volumes
   If :ln_Set_Volume = 1 Then
      call volume_pv_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set Retail Volumes
   If :ln_Set_Volume_Retail = 1 Then
      call volume_retail_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set LRP Volumes
   If ln_Set_Volume_Lrp = 1 Then
      call volume_lrp_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
        
   -- Set Fast Start Volumes
   If :ln_Set_Volume_FS = 1 Then
      volume_fs_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set EGV Volumes
   If :ln_Set_Volume_EGV = 1 Then
      call volume_egv_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set TV Volumes
   If :ln_Set_Volume_TV = 1 Then
      call volume_tv_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set Taiwan Volumes
   If :ln_Set_Volume_TW_CV = 1 Then
      call volume_tw_cv_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set Org Volumes
   If :ln_Set_Volume_Org = 1 Then
      call volume_org_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set Ranks
   If :ln_Set_Rank = 1 Then
      call ranks_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set Payout 1 --Unilevel
	if :ln_Set_Payout_1 = 1 then
		call Payout_Unilevel_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 2 --Power3
	if :ln_Set_Payout_2 = 1 then
		call Payout_Power3_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 3 --Retail
	if :ln_Set_Payout_3 = 1 then
		call Payout_Retail_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 4 --Preferred
	if :ln_Set_Payout_4 = 1 then
		call Payout_Preferred_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 5 --Leadership Performance
	if :ln_Set_Payout_5 = 1 then
		call Payout_Leadership_Perform_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 6 --Diamond Performance
	if :ln_Set_Payout_6 = 1 then
		call Payout_Diamond_Perform_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 7 --Diamond
	if :ln_Set_Payout_7 = 1 then
		call Payout_Diamond_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 8 --Blue Diamond
	if :ln_Set_Payout_8 = 1 then
		call Payout_Blue_Diamond_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 9 --Presidential Diamond
	if :ln_Set_Payout_9 = 1 then
		call Payout_Pres_Diamond_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 10 --Taiwan
	if :ln_Set_Payout_10 = 1 then
		call Payout_Taiwan_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 11 --Empowerment
	if :ln_Set_Payout_11 = 1 then
		call Payout_Empowerment_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 12 --Professional
	if :ln_Set_Payout_12 = 1 then
		--call Payout_Professional_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Payout 13 --Faststart
	if :ln_Set_Payout_13 = 1 then
		--call Payout_Faststart_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   Update period_batch
   Set end_date_run = current_timestamp
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
   
End
