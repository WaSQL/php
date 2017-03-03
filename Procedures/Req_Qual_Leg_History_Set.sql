drop procedure Commissions.Req_Qual_Leg_History_Set;
create procedure Commissions.Req_Qual_Leg_History_Set(
					 ps_Period_id		int
					,pn_Batch_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	insert into req_qual_leg_history
	select
		 :ps_Period_id			as period_id
		,:pn_Batch_id			as batch_id
		,version_id
		,rank_id
		,leg_rank_id
		,leg_rank_count
		,vol_1
		,vol_2
		,vol_3
		,vol_4
	from req_qual_leg;
			
	commit;

end;
