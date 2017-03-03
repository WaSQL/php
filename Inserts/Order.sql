insert into transaction_log 
(transaction_log_id
,transaction_log_ref_id
,customer_id
,source_key_id
,source_id
,period_id
,transaction_date
,transaction_type_id
,transaction_category_id
,currency_code
,value_2
,value_4)
values
(transaction_log_id.nextval
,0
,70020
,0
,1 -- Source: 1 - Datatrax
,9 -- Period 9 - Dec
,current_timestamp
,5 -- Trans Type 5 - Manual
,1 -- Trans Sub Type 1 - Standard
,'SGD'
,30 -- PV
,30 -- CV
);
