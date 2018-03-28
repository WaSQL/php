SELECT
	table_name tablename,
	round(table_rows/1000000,2) million_rows,
	concat(round(data_length/(1024*1024*1024),2),'G') data_size,
    concat(round(index_length/(1024*1024*1024),2),'G') index_size,
    concat(round((data_length+index_length)/(1024*1024*1024),2),'G') total_size,
    round(index_length/data_length,2) index_data_ratio
    FROM information_schema.TABLES
    where table_schema=SCHEMA()
    ORDER BY tablename,data_length+index_length DESC
