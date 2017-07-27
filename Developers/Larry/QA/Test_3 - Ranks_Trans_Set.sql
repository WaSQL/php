--update Commissions.period set realtime_trans = 1;
--update Commissions.period set realtime_rank = 1;
--call Commissions.Commission_Clear();
--call Commissions.Transaction_Test(779431, 9);

--call Commissions.Commission_Run(9);

call Commissions.Customer_Update(779431, 442624, 442624, 1, 0, 1);
--call Commissions.Customer_Update(779431, 607513, 607513, 0, 0, 1);

--call Commissions.Customer_Update(1642631, 1001, 1001, 0, 0, 1);
--call Commissions.Customer_Update(1642631, 852432, 852432, 0, 0, 1);

--select *
--from Commissions.customer
--where type_id = 3
--and sponsor_id = 852432;

--select *
--from Commissions.customer
--where vol_1 < 100
--and rank_id >= 5