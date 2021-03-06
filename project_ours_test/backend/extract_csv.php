<?php

$json = file_get_contents('php://input');
$map = json_decode($json,true);


$conn = new mysqli('localhost','root','','database_test');


if (mysqli_connect_error()) 
{
    die(json_encode(array("status"=>"failure","msg"=> mysqli_connect_error())));
}

mysqli_query($conn,'SET CHARACTER SET utf8;');
mysqli_query($conn,'SET COLLATION_CONNECTION=utf8_general_ci;');


$yearAll = $map['yearAll'];
$monthAll = $map['monthAll'];
$dayAll = $map['dayAll'];
$hourAll = $map['hourAll'];
$yearStart = $map['yearStart'];
$yearEnd = $map['yearEnd'];
$monthStart = $map['monthStart'];
$monthEnd = $map['monthEnd'];
$dayStart = $map['dayStart'];
$dayEnd = $map['dayEnd'];
$hourStart = $map['hourStart'];
$hourEnd = $map['hourEnd'];
//$activities = $map['activities'];
$actList = $map['actList'];


$extra_sql = "and (a.activity_type = '" . implode("' OR  a.activity_type  = '",$actList) . "')";
	//die(json_encode(array("sql"=>$extra_sql)));	

	
//yearAll

if ($yearAll == true){
	
	$sql = "SELECT min(yearT) as minyear, max(yearT) as maxyear FROM timestampMs";
	$result = mysqli_query($conn, $sql) or die(json_encode(array("status"=>"failure","msg"=>mysqli_error($conn))));
	$row = $result->fetch_assoc();
	$yearStart=intval($row['minyear']);
	$yearEnd =intval($row['maxyear']);
}



//monthAll
if ($monthAll == true){
	
	$sql = "SELECT min(monthT) as minmonth, max(monthT) as maxmonth FROM timestampMs";
	$result = mysqli_query($conn, $sql) or die(json_encode(array("status"=>"failure","msg"=>mysqli_error($conn))));
	$row = $result->fetch_assoc();
	$monthStart= intval($row['minmonth']);
	$monthEnd = intval($row['maxmonth']);
}

//dayAll
if ($dayAll == true){
	
	$sql = "SELECT min(dayT) as minday, max(dayT) as maxday FROM timestampMs";
	$result = mysqli_query($conn, $sql) or die(json_encode(array("status"=>"failure","msg"=>mysqli_error($conn))));
	$row = $result->fetch_assoc();
	$dayStart=intval($row['minday']);
	$dayEnd =intval($row['maxday']);
	
}

//$hourAll = true;
if ($hourAll == true){
	
	
	$sql = "SELECT min(hourT) as minhour, max(hourT) as maxhour FROM timestampMs";
	$result = mysqli_query($conn, $sql) or die(json_encode(array("status"=>"failure","msg"=>mysqli_error($conn))));
	$row = $result->fetch_assoc();
	$hourStart=intval($row['minhour']);
	$hourEnd =intval($row['maxhour']);
}

$sql= "select a.userId, a.locationID , a.activity_type, a.activity_confidence , a.activity_timestampMs ,a.accuracy ,a.longitudeE7 ,a.latitudeE7  ,a.timestampMs 
		from (select * from locationData inner join timestampMs on locationData.locationID = timestampMs.locationData) as a WHERE ( a.yearT BETWEEN $yearStart AND $yearEnd) AND (a.monthT BETWEEN $monthStart AND $monthEnd) AND (a.dayT BETWEEN $dayStart AND $dayEnd) AND (a.hourT BETWEEN $hourStart AND $hourEnd) "; 


if(!empty($actList)){
	$sql .= $extra_sql;
}


//get records from database
$query = $conn->query($sql);

if($query->num_rows > 0){
    $delimiter = ','; //comma-separated values
    $filename = "CSV_output_" . date('Y-m-d') . ".csv";
    
    //create a file pointer
    $f = fopen('php://memory', 'w');
    
    //set column headers
    $fields = array('userId', 'locationID' ,'activity_type', 'activity_confidence', 'activity_timestampMs' ,'accuracy','longitudeE7','latitudeE7' ,'timestampMs');
    fputcsv($f, $fields, $delimiter);
    
    //output each row of the data, format line as csv and write to file pointer
    while($row = $query->fetch_assoc()){
        $lineData = array($row['userId'], $row['locationID'] , $row['activity_type'], $row['activity_confidence'], $row['activity_timestampMs'],  $row['accuracy'], $row['longitudeE7'], $row['latitudeE7'] , $row['timestampMs']);
        fputcsv($f, $lineData, $delimiter);
    }
    
    //move back to beginning of file
    fseek($f, 0);
    
    //set headers to download file rather than displayed
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    //output all remaining data on a file pointer
    fpassthru($f);
}



?>

