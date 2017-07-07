drop procedure Commissions.sp_Commission_Clear;
create procedure Commissions.sp_Commission_Clear(
					 pn_Period_id		Integer
					,pn_Period_Batch_id	Integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS
/* --------------------------------------------------------------------------------
Created by: Larry Cardon
Date:		5-Jul-2017

Purpose:	Clears all commission and customer earning values according to flags set for the period batch

-------------------------------------------------------------------------------- */

begin
	declare ln_Set_Level           	Integer;
   	declare ln_Set_Rank            	Integer;
   	declare ln_Set_Earning_1  		Integer;
   	declare ln_Set_Earning_2       	Integer;
   	declare ln_Set_Earning_3  		Integer;
   	declare ln_Set_Earning_4       	Integer;
   	declare ln_Set_Earning_5       	Integer;
   	declare ln_Set_Earning_6       	Integer;
   	declare ln_Set_Earning_7       	Integer;
   	declare ln_Set_Earning_8       	Integer;
   	declare ln_Set_Earning_9       	Integer;
   	declare ln_Set_Earning_10      	Integer;
   	declare ln_Set_Earning_11      	Integer;
   	declare ln_Set_Earning_12      	Integer;
   	declare ln_Set_Earning_13      	Integer;
    
	Update period_batch
	Set beg_date_clear = current_timestamp
      ,end_date_clear = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		ln_Set_Level = 1;
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
	   		set_level
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
   
	call sp_Period_Batch_Clear(:pn_Period_id, :pn_Period_Batch_id);
	call sp_Customer_Clear(:pn_Period_id, :pn_Period_Batch_id);
	
	if :ln_Set_Level = 1 then
		call sp_Customer_Hier_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;

	if :ln_Set_Rank = 1 then
		call sp_Customer_Rank_Clear(:pn_Period_id, :pn_Period_Batch_id);
		call sp_Customer_Qual_Leg_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_1 = 1 then
		call sp_Earning_01_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_2 = 1 then
		call sp_Earning_02_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_3 = 1 then
		call sp_Earning_03_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_4 = 1 then
		call sp_Earning_04_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_5 = 1 then
		call sp_Earning_05_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_6 = 1 then
		call sp_Earning_06_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_7 = 1 then
		call sp_Earning_07_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_8 = 1 then
		call sp_Earning_08_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_9 = 1 then
		call sp_Earning_09_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_10 = 1 then
		call sp_Earning_10_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_11 = 1 then
		call sp_Earning_11_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_12 = 1 then
		call sp_Earning_12_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
	if :ln_Set_Earning_13 = 1 then
		--call sp_Earning_13_Clear(:pn_Period_id, :pn_Period_Batch_id);
	end if;
	
   	Update period_batch
   	Set end_date_clear = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
	
end;
