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
   declare ln_Set_Volume_Org       	Integer;
   declare ln_Set_Rank             	Integer;
   declare ln_Set_Payout_1  		Integer;
   declare ln_Set_Payout_2         	Integer;
   declare ln_Set_Payout_3  		Integer;
   declare ln_Set_Payout_4         	Integer;
   declare ln_Set_Payout_5         	Integer;
   declare ln_Set_Payout_6         	Integer;
   declare ln_Set_Payout_7         	Integer;
   
   Select 
        clear_flag
      , set_volume
      , set_volume_lrp
      , set_volume_fs
      , set_volume_retail
      , set_volume_egv
      , set_volume_org
      , set_rank
      , set_payout_1
      , set_payout_2
      , set_payout_3
      , set_payout_4
      , set_payout_5
      , set_payout_6
      , set_payout_7
   Into 
        ln_Clear
      , ln_Set_Volume
      , ln_Set_Volume_Lrp
      , ln_Set_Volume_FS
      , ln_Set_Volume_Retail
      , ln_Set_Volume_EGV
      , ln_Set_Volume_Org
      , ln_Set_Rank
      , ln_Set_Payout_1
      , ln_Set_Payout_2
      , ln_Set_Payout_3
      , ln_Set_Payout_4
      , ln_Set_Payout_5
      , ln_Set_Payout_6
      , ln_Set_Payout_7
   From  period_batch
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;

	-- Clear Commission
	if ln_Clear = 1 then
		call commission_history_clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
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
   --If ln_Set_Volume_Lrp = 1 Then
   --   Set_Volume_Lrp(pn_Period_id);
   --End If;
        
   -- Set Fast Start Volumes
   If :ln_Set_Volume_FS = 1 Then
      volume_fs_history_set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set EGV Volumes
   If :ln_Set_Volume_EGV = 1 Then
      call volume_egv_history_set(:pn_Period_id, :pn_Period_Batch_id);
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
   
   Update period_batch
   Set end_date_run = current_timestamp
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
   
End
