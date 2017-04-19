drop procedure Commissions.Volume_Tv_History_Set;
create procedure Commissions.Volume_Tv_History_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	Update period_batch
	Set beg_date_volume_tv = current_timestamp
      ,end_date_volume_tv = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;
   
	replace customer_history (period_id, batch_id, customer_id, vol_14)
	Select 
	      c.period_id
	     ,c.batch_id
	     ,c.customer_id
	     ,Sum(ifnull(s.vol_1,0)+ifnull(s.vol_4,0))+ifnull(c.vol_1,0)+ifnull(c.vol_4,0) As tv
	From customer_history c, customer_history s
	Where c.customer_id = s.sponsor_id
	and c.period_id = :pn_Period_id
   	and c.batch_id = :pn_Period_Batch_id
	and s.period_id = c.period_id
   	and s.batch_id = c.batch_id
   	--and c.type_id = 1
   	--and s.type_id in (1,5)
    Group By c.period_id, c.batch_id, c.customer_id, c.vol_1, c.vol_4;
   	
   	commit;
   
   	Update period_batch
   	Set end_date_volume_tv = current_timestamp
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
   	
   	commit;

end;
