select      table_name
,           index_type
,           min(column_names)      column_names
,           trim(',' FROM
                case index_type
                    when 'BTREE' then
                        replace(
                            -- report all but the last one
                            -- (the last one is the longest one)
                            substring_index(
                                group_concat(
                                    '`',index_name,'`'
                                    order by    column_count asc
                                    ,           non_unique   asc
                                    ,           index_name   desc
                                    separator   ','
                                )
                            ,   ','
                            ,   count(*) - 1
                            )
                            -- get the first one
                            -- (the first one is the smallest unique one)
                        ,   concat(
                                '`'
                            ,   substring_index(
                                    group_concat(
                                        if( non_unique = 0
                                        ,   index_name
                                        ,   ''
                                        )
                                        order by    non_unique   asc
                                        ,           column_count asc
                                        ,           index_name   asc
                                        separator   ','
                                    )
                                ,   ','
                                ,   1
                                )
                            ,   '`'
                            )
                        ,   ''
                        )
                    when 'HASH' then
                        substring_index(
                            group_concat(
                                '`',index_name,'`'
                                order by    non_unique   asc
                                ,           index_name   asc
                                separator   ','
                            )
                        ,   ','
                        ,   1 - count(*)
                        )
                    when 'SPATIAL' then
                        substring_index(
                            group_concat(
                                '`',index_name,'`'
                                order by    index_name  asc
                                separator ','
                            )
                        ,   ','
                        ,   1 - count(*)
                        )
                    else 'unexpected type - not implemented'
                end
            )           redundant_indexes
from        (
            select      table_name
            ,           index_name
            ,           index_type
            ,           non_unique
            ,           count(seq_in_index) as column_count
            ,           group_concat(
                            if(seq_in_index=1,column_name,'')
                            separator ''
                        )                   as column_name
            ,           group_concat(
                            column_name
                            order by seq_in_index
                            separator ','
                        )                   as column_names
            from        information_schema.statistics s
            where       s.table_schema = schema()
            and         s.index_type  != 'FULLTEXT'
            group by    table_name
            ,           index_name
            ,           index_type
            ,           non_unique
            )           as s
group by    table_name
,           index_type
,           if(index_type='HASH',column_names,column_name)
having      redundant_indexes != ''
