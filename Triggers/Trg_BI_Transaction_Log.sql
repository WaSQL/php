drop trigger Commissions.Trg_BI_Transaction_Log;
create trigger Commissions.Trg_BI_Transaction_Log 
BEFORE INSERT ON Commissions.Transaction_Log 
REFERENCING NEW ROW TRG_NEW FOR EACH ROW 
begin 
	declare ln_Realtime_Trans integer;
 	declare le_Error nvarchar(200);
 	declare exit handler for sqlexception 
		begin
			le_Error = 'Error!';
		end;
 
	if 
		(ifnull(:trg_new.value_2 ,0) <> 0 or ifnull(:trg_new.value_4,0) <> 0) 
		and ifnull(:trg_new.transaction_type_id,4) <> 0 
	then 
		select realtime_trans 
		into ln_Realtime_Trans 
		from period 
		where period_id = 0;
 
		if :ln_Realtime_Trans = 1 then 
			call Commissions.Customer_Rollup_Volume(
				:trg_new.customer_id,
				:trg_new.value_2,
				:trg_new.value_4);
				
		 	trg_new.processed_date = current_timestamp;
		end if;
 
	else 
		trg_new.processed_date = current_timestamp;
	end if;
 
end;