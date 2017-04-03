drop Procedure Commissions.Commission_Run;
create Procedure Commissions.Commission_Run()
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

Begin

	-- Clear Commission
	call commission_clear();
	
	-- Set Volumes
	call volume_pv_set();
      
	-- Set Retail Volumes
	call volume_retail_set();
         
	-- Set LRP Volumes
	--Set_Volume_Lrp();
        
	-- Set Fast Start Volumes
	call volume_fs_set();
      
	-- Set EGV Volumes
	call volume_egv_set();
         
	-- Set Org Volumes
	call volume_org_set();
         
	-- Set Ranks
	call ranks_set();
   
End
