<html>

<?php 
//Homepage.php
//homepage is populated with information based on what is submitted from index.php (the login page)
//Programmer: Amy Robben

//the following require files are used for different styling elements, "the tutorHeader" function
//and all methods we have defined for accessing the database
require_once 'sched_header.php';
require_once 'Database.php';

//connecting to the database
$db= new Database();
$db->connect();

//method to provide the Navigation bar at top of webpage
tutorHeader("home");

$tutorID= $_SESSION['uname'];


//Selecting infomation from the tutor table in the datbase to populate all fields
//on the homepage.


$query= $db->selectQuery('tutor', "firstName, lastName, grade, primarySubject", "tutorID= $tutorID");
$result= $query->fetch_array(MYSQLI_ASSOC);

$fName= $result["firstName"];
$lName= $result["lastName"];
$grade= $result["grade"];
$primarySubject= $result["primarySubject"];

//Selecting information from the working table in the database to populate table on homepage to 
//display tutor's currently scheduled hours for that week

$workHours= $db->selectQuery("working JOIN timespot", "day, time", 
								"working.tutorID= '$tutorID' && working.timespotID=timespot.timespotID");



//Arrays to hold tutor's hours for days monday-friday
//A tutor's scheduled hours are pulled from the datbase, then placed in each array according
//to their "day" value in the database

$mHours= array();
$tHours= array();
$wHours= array();
$rHours= array();
$fHours= array();

if($workHours != null)
{
$rows= $workHours->num_rows;
for($i=0; $i<$rows; $i++)
{
	$workHours->data_seek ( $i );
	$row = $workHours->fetch_array ( MYSQLI_ASSOC );
	if($row["day"]=="Monday")
		array_push($mHours, $row["time"]);
	if($row["day"]=="Tuesday")
		array_push($tHours, $row["time"]);
	if($row["day"]=="Wednesday")
		array_push($wHours, $row["time"]);
	if($row["day"]=="Thursday")
		array_push($rHours, $row["time"]);
	if($row["day"]=="Friday")
		array_push($fHours, $row["time"]);
		
}

//Using the sortHours function to sort arrays so that scheduled hours are arranged in 
//chronological order

$mHours= sortHours($mHours);
$tHours= sortHours($tHours);
$wHours= sortHours($wHours);
$rHours= sortHours($rHours);
$fHours= sortHours($fHours);

}


//Displaying each element on the Homepage

echo<<<_END
<body>
<h1>Welcome, $fName $lName</h1>


<div id= Info>
<br>
<b>Primary Subject</b>: $primarySubject

<br>
<br>
<b>Class</b>: $grade <br>
<br>

</div>
<br><br><br><br>				
<table class="common">
<tr>
<td id= TableDetails>Monday</td>
<td id= TableDetails>Tuesday</td>
<td id= TableDetails>Wednesday</td>
<td id= TableDetails>Thursday</td>
<td id= TableDetails>Friday</td>			
</tr>
_END;


//the actual populating of the table to display tutor's scheduled hours is done here
//using the getConsecutiveHours method to display hours in each cell of the table

echo "<td id= TableDetails>";
if($mHours!= null)
	getConsecutiveHours($mHours);
echo "</td>";

echo "<td id= TableDetails>";
if($tHours != null)
	getConsecutiveHours($tHours);
echo "</td>";

echo "<td id= TableDetails>";
if($wHours != null)
	getConsecutiveHours($wHours);
echo "</td>";

echo "<td id= TableDetails>";
if($rHours != null)
	getConsecutiveHours($rHours);
echo "</td>";

echo "<td id= TableDetails>";
if($fHours != null)
	getConsecutiveHours($fHours);
echo "</td>";


echo "</tr>";

echo "</table>";



//The getConsecutiveHours searches through an array to establish a range of hours that are 
//scheduled for the tutor
//Establishes a firstHour and lastHour value
//When a value is no longer consecutive from the previous, a new range is started

function getConsecutiveHours($array)
{
	$firstHour= $array[0];
	$lastHour= null;
	$consecString= array();
	$consec= false;
	
	if(count($array)==1)
	{
		if($firstHour>12)
		{
			$firstHour-=12;
			if($firstHour==6)
				$lastHour=8;
			else 
				$lastHour=$firstHour+1;
		}
		else 
		{
			if($firstHour== 12)
				$lastHour=1;
			else
				$lastHour= $firstHour+1;
		}
		echo $firstHour.'-'.$lastHour;
	}
	else
	{
		
		for($i=1; $i<count($array); $i++)
		{			
			$current= $array[$i];
			if($current == ($array[$i-1]+1))
			{
				$lastHour= $current;
			}	
			
			else 
			{
				if($firstHour>12)
					$firstHour-=12;
				if($lastHour >12)
					$lastHour-=12;
			
				if($lastHour==6)
				{
					$lastHour+=2;
					array_push($consecString, "$firstHour-$lastHour");
				}
				else if($lastHour != null)
				{
					$lastHour= $lastHour+1;
					array_push($consecString, "$firstHour-$lastHour");
				}
				else
				{
					$lastHour= $firstHour+1;
					array_push($consecString, "$firstHour-$lastHour");
				}
				$firstHour= $current;
				$lastHour=null;
			}
		}
		
		
		if($firstHour>12)
			$firstHour-=12;
		if($lastHour != null && $lastHour >12)
			$lastHour-=12;
					
		if($lastHour==6)
		{
			$lastHour+=2;
		}
		else if($lastHour!= null)
		{
			$lastHour= $lastHour+1;
			if($lastHour>12)
				$lastHour-=12;
		}
		else 
		{
			if($firstHour==6)
			{
				$lastHour=$firstHour+2;
			}
			else
				$lastHour= $firstHour+1;
			
		}
		array_push($consecString, "$firstHour-$lastHour");
	
		for($p=0; $p<count($consecString); $p++)
		{
			echo $consecString[$p]."<br>";
		}
	}
}

//implementation of bubble sort to sort a day's assigned hours from earliest to latest

function sortHours($arr)
{
	for($i=0; $i<count($arr); $i++)
	{
		if($arr[$i]<8)
			$arr[$i]+=12;
	}
	
	$swapped=true;
	$j=0;
	while($swapped)
	{
		$swapped=false;
		$j++;
		for($i=0; $i<count($arr)-$j; $i++)
		{
			if($arr[$i]>$arr[$i+1])
			{
				$temp=$arr[$i];
				$arr[$i]=$arr[$i+1];
				$arr[$i+1]= $temp;
				$swapped= true;
			}
		}
			
	}
	
	return $arr;
}



?>
</body>

</html>