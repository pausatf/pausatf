<?php
// ----------------------------------------------------------------------------*
// updatess1.php                                                               *
// Created August 2006                                                         *
// Created by Dan Preston                                                      *
// This script handles updates of race number and/or club number for           * 
// maintenance.                                                                *
// ----------------------------------------------------------------------------*
   require_once ('/home/pausat/dbss.php');
   
// Successful connection.  Set values for update:
         
        $rnum = 195;
        $cname = 'Fleet Feet Sac';  
        
        $query = "SELECT * FROM RaceName   
                 WHERE RaceNumber = '$rnum'"; 
         
        $result1 = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result1)) {  // was SELECT succcessful
            echo "<p>Error in updatess3 on SELECT Race $rnum</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result1);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, Race exists
            echo "<p><b>The race number '$rnum' is not on the database.  Consult the Listing of Races.";
            exit();
        } else {
            $query = "UPDATE RaceName SET ClubName = '$cname' WHERE RaceNumber='$rnum'"; 
            $result = @mysql_query ($query);
            $return = mysql_affected_rows();
            if ($return == 0) {
                echo "<p>No update occurred because nothing changed.";
            } else {
                if ($return < 0) {   // if nothing changed, return is 0; if anything changes, it's 1; -1 for error
                    echo "<p>Return code $return error in updatess3 on UPDATE</p><p>" . mysql_error() . '</p>';
                    mysql_close();
                    exit();
                } else {
                    echo "<p><h3>Update of Club $rnum as $cname was successful</h3></p>"; 
                }
            } 
        }            