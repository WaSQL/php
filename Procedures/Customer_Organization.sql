drop procedure Commissions.Customer_Organization;
create procedure Commissions.Customer_Organization(
					  pn_Customer_id 		integer
					, pn_Period_id 			integer
					, pn_Direction_id 		integer
					, pn_Type_id			integer
					, pn_Levels				integer
					, out pt_Org			table (Customer_Root_id	integer
												  ,Customer_id		integer
												  ,Customer_name	varchar(50)
												  ,Level_id			integer
												  ,Sponsor_id		integer
												  ,Sponsor_name		varchar(50)
												  ,Enroller_id		integer
												  ,Enroller_name	varchar(50)
												  ,PV				decimal(18,8)
												  ,OV				decimal(18,8)
												  ,Rank_id			integer
												  ,Rank_Title		integer
			  									  ,count_sub		bigint))
					   	
	LANGUAGE SQLSCRIPT 
	DEFAULT SCHEMA Commissions
AS

begin

	pt_Org = 
		select * 
		from Commissions.Organization(:pn_Customer_id, :pn_Period_id, :pn_Direction_id, :pn_Type_id, :pn_Levels);

end;