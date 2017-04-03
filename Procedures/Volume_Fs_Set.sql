drop procedure Commissions.Volume_Fs_Set;
create procedure Commissions.Volume_Fs_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	replace customer (customer_id, vol_5, vol_10)
	Select 
		  c.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction_log t
		left outer join transaction_log r
			on t.transaction_log_ref_id = r.transaction_log_id
		, customer c
   	Where t.customer_id = c.customer_id
   	And t.period_id = 0
   	and c.type_id = 1
   	and ifnull(t.transaction_type_id,4) <> 0
   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.transaction_date,t.transaction_date)) <= 60
   	Group By c.customer_id;
   	
   	commit;

end;
