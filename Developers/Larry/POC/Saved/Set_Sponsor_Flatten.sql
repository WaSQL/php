drop procedure set_sponsor_hier;
create procedure set_sponsor_hier(
					pn_Period_id	integer)
LANGUAGE SQLSCRIPT AS

begin
	declare ln_level_max 	integer;
	declare ln_level_work	integer;
   
	Update comm_period
   	Set date_srt_hier = current_timestamp
   		, date_end_hier = null
   	Where period_id = :pn_Period_id;
   
   	commit;
	
	lc_dst = select 
				 c.result_node 	as dist_id
				,c.path 			as path
				,c.level			as level_id
			from sponsor_tree_dn c, comm_dist d
			where c.result_node = d.dist_id
			and d.period_id = :pn_Period_id
			and c.query_node != 0;
	
	select max(level_id)
	into ln_level_max
	from :lc_dst;
	
	for ln_level_work in 1..ln_level_max do
		insert into comm_sponsor_hier (dist_id,root_id,level_id)
		select
			 dist_id
			,substr(path,
				 locate(path,'/',1,ln_level_work)+1
				,case 
					when level_id = :ln_level_work
				 	then length(path)
				 	else locate(path,'/',1,ln_level_work+1)-locate(path,'/',1,ln_level_work)-1
				 end
				)
			,level_id - :ln_level_work
		from :lc_dst
		where level_id >= :ln_level_work;
	end for;
   
	Update comm_period
   	Set date_end_hier = current_timestamp
   	Where period_id = :pn_Period_id;
   
   	commit;

end