drop Procedure Commissions.sp_Commission_Run;
create Procedure Commissions.sp_Commission_Run
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			5-Jul-2017
*
* @describe		Runs all commission engines according to flags set for the period batch
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @example		call Commissions.sp_Cap_Req_Snap(10);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

Begin
	declare ln_Clear				   	Integer;
	declare ln_Set_Level				integer;
	declare ln_Set_Volume           	Integer;
	declare ln_Set_Volume_Lrp       	Integer;
	declare ln_Set_Volume_FS        	Integer;
	declare ln_Set_Volume_Retail    	Integer;
	declare ln_Set_Volume_EGV       	Integer;
	declare ln_Set_Volume_TV       		Integer;
	declare ln_Set_Volume_TW_CV     	Integer;
	declare ln_Set_Volume_Org       	Integer;
   	declare ln_Set_Rank             	Integer;
   	declare ln_Set_Earning_1  			Integer;
   	declare ln_Set_Earning_2         	Integer;
   	declare ln_Set_Earning_3  			Integer;
   	declare ln_Set_Earning_4         	Integer;
   	declare ln_Set_Earning_5         	Integer;
   	declare ln_Set_Earning_6         	Integer;
   	declare ln_Set_Earning_7         	Integer;
   	declare ln_Set_Earning_8         	Integer;
   	declare ln_Set_Earning_9         	Integer;
   	declare ln_Set_Earning_10        	Integer;
   	declare ln_Set_Earning_11        	Integer;
   	declare ln_Set_Earning_12        	Integer;
   	declare ln_Set_Earning_13        	Integer;
	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		ln_Clear = 1;
		ln_Set_Level = 1;
		ln_Set_Volume = 1;
		ln_Set_Volume_Lrp = 1;
		ln_Set_Volume_FS = 1;
		ln_Set_Volume_Retail = 1;
		ln_Set_Volume_EGV = 1;
		ln_Set_Volume_TV = 1;
		ln_Set_Volume_TW_CV = 1;
		ln_Set_Volume_Org = 1;
		ln_Set_Rank = 1;
		ln_Set_Earning_1 = 0;
		ln_Set_Earning_2 = 0;
		ln_Set_Earning_3 = 0;
		ln_Set_Earning_4 = 0;
		ln_Set_Earning_5 = 0;
		ln_Set_Earning_6 = 0;
		ln_Set_Earning_7 = 0;
		ln_Set_Earning_8 = 0;
		ln_Set_Earning_9 = 0;
		ln_Set_Earning_10 = 0;
		ln_Set_Earning_11 = 0;
		ln_Set_Earning_12 = 0;
		ln_Set_Earning_13 = 0;
	else
		Select 
        	  clear_flag
        	, set_level
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
        	  ln_Clear
        	, ln_Set_Level
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
	end if;

	-- Clear Commission
	call sp_Commission_Clear(:pn_Period_id, :pn_Period_Batch_id);
   
   Update period_batch
   Set
       beg_date_run = current_timestamp
      ,end_date_run = Null
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
	
   -- Set Level
   If :ln_Set_Level = 1 Then
      call sp_Customer_Hier_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
	
   -- Set Volumes
   If :ln_Set_Volume = 1 Then
      call sp_Volume_Pv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set Retail Volumes
   If :ln_Set_Volume_Retail = 1 Then
      call sp_Volume_Retail_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set LRP Volumes
   If ln_Set_Volume_Lrp = 1 Then
      call sp_Volume_Lrp_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
        
   -- Set Fast Start Volumes
   If :ln_Set_Volume_FS = 1 Then
      call sp_Volume_Fs_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set EGV Volumes
   If :ln_Set_Volume_EGV = 1 Then
      call sp_Volume_Egv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set TV Volumes
   If :ln_Set_Volume_TV = 1 Then
      call sp_Volume_Tv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set Taiwan Volumes
   If :ln_Set_Volume_TW_CV = 1 Then
      call sp_Volume_Tw_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set Org Volumes
   If :ln_Set_Volume_Org = 1 Then
      call sp_Volume_Org_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set Ranks
   If :ln_Set_Rank = 1 Then
      call sp_Rank_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set Earning 1 --Unilevel
	if :ln_Set_Earning_1 = 1 then
		call sp_Earning_01_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 2 --Power3
	if :ln_Set_Earning_2 = 1 then
		call sp_Earning_02_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 3 --Retail
	if :ln_Set_Earning_3 = 1 then
		call sp_Earning_03_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 4 --Preferred
	if :ln_Set_Earning_4 = 1 then
		call sp_Earning_04_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 5 --Leadership Performance
	if :ln_Set_Earning_5 = 1 then
		call sp_Earning_05_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 6 --Diamond Performance
	if :ln_Set_Earning_6 = 1 then
		call sp_Earning_06_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 7 --Diamond
	if :ln_Set_Earning_7 = 1 then
		call sp_Earning_07_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 8 --Blue Diamond
	if :ln_Set_Earning_8 = 1 then
		call sp_Earning_08_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 9 --Presidential Diamond
	if :ln_Set_Earning_9 = 1 then
		call sp_Earning_09_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 10 --Taiwan
	if :ln_Set_Earning_10 = 1 then
		call sp_Earning_10_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 11 --Empowerment
	if :ln_Set_Earning_11 = 1 then
		call sp_Earning_11_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 12 --Professional
	if :ln_Set_Earning_12 = 1 then
		call sp_Earning_12_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   -- Set Earning 13 --Faststart
	if :ln_Set_Earning_13 = 1 then
		--call sp_Earning_13_Set(:pn_Period_id, :pn_Period_Batch_id);
	end if;
   
   Update period_batch
   Set end_date_run = current_timestamp
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
   
End
