drop procedure Commissions.Commission_History_Clear;
create procedure Commissions.Commission_History_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

begin
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
    
	Update period_batch
	Set beg_date_clear = current_timestamp
      ,end_date_clear = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
   	Select 
        set_rank
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
        ln_Set_Rank
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
   
	call period_batch_clear(:pn_Period_id, :pn_Period_Batch_id);
	call customer_history_clear(:pn_Period_id, :pn_Period_Batch_id);
	
	if ln_Set_Rank = 1 then
		call Customer_History_Rank_Clear(:pn_Period_id, :pn_Period_Batch_id);
		call customer_history_qual_leg_clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_1 = 1 then
		call Payout_Unilevel_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_2 = 1 then
		call Payout_Power3_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_3 = 1 then
		call Payout_Retail_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_4 = 1 then
		call Payout_Preferred_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_5 = 1 then
		call Payout_Leadership_Perform_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_6 = 1 then
		call Payout_Diamond_Perform_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_7 = 1 then
		call Payout_Diamond_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_8 = 1 then
		call Payout_Blue_Diamond_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_9 = 1 then
		call Payout_Pres_Diamond_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_10 = 1 then
		call Payout_Taiwan_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_11 = 1 then
		call Payout_Empowerment_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_12 = 1 then
		--call Payout_Professional_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if ln_Set_Payout_13 = 1 then
		--call Payout_Faststart_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
   	Update period_batch
   	Set end_date_clear = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
	
end;
