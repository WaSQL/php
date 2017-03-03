drop TRIGGER "LCARDON"."RANK_ROLLUP";
CREATE TRIGGER "LCARDON"."RANK_ROLLUP" 
AFTER UPDATE OF RANK_ID ON "LCARDON"."COMM_DIST" 
REFERENCING OLD ROW TRG_OLD, NEW ROW TRG_NEW FOR EACH ROW 

begin 
	declare ln_leg_rank_id integer;
 	declare ln_Count integer;
 
	if ifnull(:trg_new.rank_id,	0) >= 4 then 
		select count(*) 
		into ln_Count 
		from comm_leg_detail 
		where (period_id,dist_id,leg_dist_id) in (
					select
			 			:trg_new.period_id
						,h.Root_id
						,(select root_id 
						  from comm_sponsor_hier 
						  where dist_id = h.dist_id 
						  and level_up = h.level_up+1) 
					from comm_sponsor_hier h 
					where h.dist_id = :trg_new.dist_id 
					and h.Root_id = :trg_new.enroller_id);
			
	 	ln_leg_rank_id = 0;
 
		if ln_Count > 0 then 
			select
				 leg_rank_id 
			into ln_leg_rank_id 
			from comm_leg_detail 
			where (period_id,dist_id,leg_dist_id) in (
						select
							:trg_new.period_id
							,h.Root_id
							,(select root_id 
							  from comm_sponsor_hier 
							  where dist_id = h.dist_id 
							  and level_up = h.level_up+1) 
						from comm_sponsor_hier h 
						where h.dist_id = :trg_new.dist_id 
						and h.Root_id = :trg_new.enroller_id);
 
		end if;
 
		if :trg_new.rank_id > ifnull(ln_leg_rank_id,0) then 
			replace comm_leg_detail (period_id, dist_id,leg_dist_id,rank_dist_id,leg_rank_id)
			select
				  :trg_new.period_id 			as period_id
				 ,h.Root_id 					as dist_id
				 ,(select root_id 
				   from comm_sponsor_hier 
				   where dist_id = h.dist_id 
				   and level_up = h.level_up+1) as leg_dist_id
				 ,:trg_new.dist_id 				as rank_dist_id
				 ,:trg_new.rank_id 				as leg_rank_id 
			from comm_sponsor_hier h 
			where h.dist_id = :trg_new.dist_id 
			and h.Root_id = :trg_new.enroller_id;
		end if;
	end if;
 
end;