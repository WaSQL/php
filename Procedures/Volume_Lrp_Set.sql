drop procedure Commissions.Volume_Lrp_Set;
create procedure Commissions.Volume_Lrp_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	replace customer (customer_id, vol_2, vol_7)
	Select 
	      t.customer_id
	     ,Sum(ifnull(t.value_2,0)) As pv
	     ,Sum(ifnull(t.value_4,0)) As cv
	From transaction t
	Where case when t.transaction_type_id = 2 then 
   		(select ifnull(a.transaction_category_id,1)
   		 from transaction a
   		 where a.transaction_id = t.transaction_ref_id)
   		 else ifnull(t.transaction_category_id,1) end in (3,6)
   	and ifnull(t.transaction_type_id,4) <> 0
    Group By t.customer_id
    having (Sum(ifnull(t.value_2,0)) != 0
		or  Sum(ifnull(t.value_4,0)) != 0);
   	
   	commit;

end;
