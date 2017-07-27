create Procedure Comm_Run_Dist(
					 pn_Period_id	    integer
                    ,pn_Dist_id  integer)
LANGUAGE SQLSCRIPT AS

Begin
    
    call Set_Ranks_dist(:pn_Period_id,:pn_Dist_id);
   
End