<?php 
/*

Name:GenerateSchedule.php
Purpose: This is the algorithm that assigns all of the tutors to the timespots.
Author: William and Amy. 
Relations: This is the back end of createSchedule.php
 */

//


require_once 'Database.php';
require_once 'sched_header.php';
$db= new Database();
$db->connect();


$mins=array(8,6);
//gets the tutorIDs of seniors then the tutorIDs of everyone else
$seniorResults =  $db->selectQuery("tutor", "tutor.tutorID, tutor.primarySubject"
			,"tutor.grade = 'Senior' ");
$otherResults =    $db->selectQuery("canwork, tutor", "tutor.tutorID, tutor.primarySubject"
		,"(tutor.grade = 'Junior' OR tutor.grade = 'Sophomore' OR tutor.grade = 'freshman') AND tutor.tutorID=canwork.tutorID");	
	
//Seniors is 1st in this array because we wish to give seniors priority. 
$results=array($seniorResults,$otherResults);
	
	//iterate through each tutor in the canWork (seniors 1st) to place them in the 'working' table
	//on each row, it evaluates whether or not that timespot is currently filled with 
	//a tutor of that subject
	//if it is not, then that tutor is placed in the working table for that timespot
	//if it is, then the tutor is not placed in the working table for that particular timespot
for($x=0;$x<2;$x++ )
{
	$tutorResults=$results[$x];
	$min=$mins[$x];
	$numRes= $tutorResults->num_rows;
	for($i=0; $i< $numRes; $i++)
	{
		$tutorResults->data_seek($i);
		$current= $tutorResults->fetch_array ( MYSQLI_ASSOC ); 
		
		$tutorID= $current["tutorID"];
		$subj= strtoupper($current["primarySubject"]);
		
		$options =  $db->selectQuery("canwork", "timespotID"
			," tutorID='$tutorID' ");// all of the timespots this tutor can work. 
		$numOptions= $options->num_rows;
		$numRepeats=0;
		while($numRepeats<3)//only repeat 3 times
		{
			for($j=0;$j<$numOptions;$j++)
		
			{
				$options->data_seek($j);
				$currentOption= $options->fetch_array ( MYSQLI_ASSOC );
				$timeID= $currentOption["timespotID"];
				$dayRow= "";
				$day= $db->selectNoBreaks("timespot", "day", "timespotID= '$timeID'"); //gets day of current timespot
				//using database methods to the following informations
				$hours=intval($db->getHoursByID($tutorID));    //how many hoours the tutor is working
				$dayHours = intval($db->getDayHours($tutorID, $day)); //how many hours are being worked on given day. 
				$taken=$db->hasSubject($timeID,$subj);                //does this day already have this subject
				$adjCanWork=$db->canWorkAdjacentTime($tutorID,$timeID);
				$adjWorking=$db->isWorkingAdjacentTime($tutorID,$timeID);

				if($numRepeats==0) //the 1st time the loop iterates
				{    //it checks if this time already has a tutor of this type and if this tutor can work in the adjacent slots
					if($taken== false &&$hours <18 && (($dayHours < 4 && $adjWorking==true)||( $adjCanWork==true && $dayHours<3)))
					{
						$db->insert("working(tutorID, timespotID)", "'$tutorID', '$timeID'");
					}
				}
				elseif($numRepeats==1) //the 2st time the loop iterates this means the tutor did not get enough hours. 
				{
					if($hours <18 && (($dayHours < 4 && $adjWorking==true)||( $adjCanWork==true && $dayHours<3))) //
					{
						$db->insert("working(tutorID, timespotID)", "'$tutorID', '$timeID'");
					}
				}
				else 
				{
					if($hours <18 ) //final loop just checks for max hours
					{
						$db->insert("working(tutorID, timespotID)", "'$tutorID', '$timeID'");
					}
				}
				
			}		
			$hours=intval($db->getHoursByID($tutorID)); //refreshes hours
		
			if($hours<$min) //if they do not have enough hours repeat. 
			{
				$numRepeats++;
			}
			else
			{
				$numRepeats+=3;
			}
			
		}

		
	}
	
}	

echo<<<_END
	<div class= "logout">Schedule has been generated! <br>
		<a href= "editableSchedule.php">View Schedule</a>
		</div>
_END;
	
	
	
	
//}


?>