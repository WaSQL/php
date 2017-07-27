drop Procedure Comm_Run;
create Procedure Comm_Run(
            pn_Period_id      Integer)
LANGUAGE SQLSCRIPT AS

Begin
   declare ln_Set_Dist             Integer;
   declare ln_Set_Order            Integer;
   declare ln_Clear				   Integer;
   declare ln_Set_Vol              Integer;
   declare ln_Set_Volume_Lrp       Integer;
   declare ln_Set_Volume_FS        Integer;
   declare ln_Set_Volume_Retail    Integer;
   declare ln_Set_Volume_EGV       Integer;
   declare ln_Set_Volume_Org       Integer;
   declare ln_Set_Rank             Integer;
   declare ln_Set_Payout_1_Detail  Integer;
   declare ln_Set_Payout_1         Integer;
   declare ln_Set_Payout_2_Detail  Integer;
   declare ln_Set_Payout_2         Integer;
   
   Select 
        set_Dist
      , set_order
      , clear_run
      , set_vol
      , set_vol_lrp
      , set_vol_fs
      , set_vol_retail
      , set_vol_egv
      , set_vol_org
      , set_rank
      , set_payout_1_detail
      , set_payout_1
      , set_payout_2_detail
      , set_payout_2
   Into 
        ln_Set_Dist
      , ln_Set_Order
      , ln_Clear
      , ln_Set_Vol
      , ln_Set_Volume_Lrp
      , ln_Set_Volume_FS
      , ln_Set_Volume_Retail
      , ln_Set_Volume_EGV
      , ln_Set_Volume_Org
      , ln_Set_Rank
      , ln_Set_Payout_1_Detail
      , ln_Set_Payout_1
      , ln_Set_Payout_2_Detail
      , ln_Set_Payout_2
   From  comm_period
   Where period_id = :pn_Period_id;
   
   -- Get Distributors from data warehouse
   --If ln_Set_Dist = 1 Then
   --   Set_Dist(pn_Period_id);
   --End If;
   
   -- Get Period Orders from data warehouse
   --If ln_Set_Order = 1 Then
   --   Set_Orders(pn_Period_id);
   --End If;

	-- Clear Commission
	if ln_Clear = 1 then
		call comm_clear(:pn_Period_id);
	end if;
   
   Update comm_period
   Set
       date_start = current_timestamp
      ,date_end = Null
   Where period_id = :pn_Period_id;
   
   commit;
	
   -- Set Volumes
   If ln_Set_Vol = 1 Then
      call set_volume(:pn_Period_id);
   End If;
         
   -- Set LRP Volumes
   --If ln_Set_Volume_Lrp = 1 Then
   --   Set_Volume_Lrp(pn_Period_id);
   --End If;
         
   -- Set Fast Start Volumes
   If ln_Set_Volume_FS = 1 Then
      Set_Volume_FS(:pn_Period_id);
   End If;
         
   -- Set Retail Volumes
   If ln_Set_Volume_Retail = 1 Then
      call Set_Volume_Retail(:pn_Period_id);
   End If;
         
   -- Set EGV Volumes
   If ln_Set_Volume_EGV = 1 Then
      call Set_Volume_EGV(:pn_Period_id);
   End If;
         
   -- Set Org Volumes
   If ln_Set_Volume_Org = 1 Then
      call Set_Volume_Org(:pn_Period_id);
   End If;
         
   -- Set Ranks
   If ln_Set_Rank = 1 Then
      call Set_Ranks(pn_Period_id);
   End If;
         
   -- Set Fast Start Detail
   --If ln_Set_Payout_1_Detail = 1 Then
   --   Set_Payout_1_Detail(pn_Period_id);
   --End If;
         
   -- Set Fast Start Summary
   --If ln_Set_Payout_1 = 1 Then
   --   Set_Payout_1(pn_Period_id);
   --End If;
         
   -- Set Unilevel Detail
   
         
   -- Set Unilevel Summary
   --If ln_Set_Payout_2 = 1 Then
   --   Set_Payout_2(pn_Period_id);
   --End If;
   
   Update comm_period
   Set date_end = current_timestamp
   Where period_id = :pn_Period_id;
   
   commit;
   
End