drop procedure Commissions.Customer_Clear;
create procedure Commissions.Customer_Clear
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	update customer
	set rank_id = 0
	  , rank_qual = 0
	  , vol_1 = 0
	  , vol_2 = 0
	  , vol_3 = 0
	  , vol_4 = 0
	  , vol_5 = 0
	  , vol_6 = 0
	  , vol_7 = 0
	  , vol_8 = 0
	  , vol_9 = 0
	  , vol_10 = 0
	  , vol_11 = 0
	  , vol_12 = 0
	  , vol_13 = 0
	  , vol_14 = 0
	  , vol_15 = 0;
	  
	commit;
	
end;
