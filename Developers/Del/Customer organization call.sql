select * from commissions.customer
select * from commissions.fn_customer_organization_tree (1, 1001, 13, 5, 1)

select * from commissions.fn_customer_organization(1001, 13, 1, 1, 5)
					  pn_Customer_id 		integer
					, pn_Period_id 			integer
					, pn_Direction_id 		integer
					, pn_Type_id			integer
					, pn_Levels				integer default 2

select * from commissions.rank

select * from commissions.fn_customer_organization(1001, 13, 1, 1, 5)
