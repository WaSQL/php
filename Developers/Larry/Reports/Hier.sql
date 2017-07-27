SELECT 
	hierarchy_rank AS "rank",  
    hierarchy_tree_size AS "tree_size",
    hierarchy_parent_rank AS "parent_rank",
    hierarchy_level AS "level",
    hierarchy_is_cycle AS "is_cycle",
    hierarchy_is_orphan AS "is_orphan",
    node_id,
    parent_id
 FROM HIERARCHY ( 
	 	SOURCE ( SELECT customer_id AS node_id, sponsor_id AS parent_id
	             FROM customer
	             where customer_id > 1000
	             and customer_id < 2000000000)
	    Start where customer_id = 1161
	    Orphan Ignore
 		Cache FORCE )
 ORDER BY hierarchy_rank;