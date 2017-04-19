drop procedure Commissions.Volume_Tv_Set;
create procedure Commissions.Volume_Tv_Set()
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	replace customer (customer_id, vol_14)
	Select 
	      c.customer_id
	     ,Sum(ifnull(s.vol_1,0)+ifnull(s.vol_4,0))+ifnull(c.vol_1,0)+ifnull(c.vol_4,0) As tv
	From customer c, customer s
	Where c.customer_id = s.sponsor_id
   	--and c.type_id = 1
   	--and s.type_id in (1,5)
    Group By c.customer_id, c.vol_1, c.vol_4;
   	
   	commit;

end;
