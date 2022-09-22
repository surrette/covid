<?php
//file_put_contents("covid_confirmed_usafacts.csv", fopen("https://usafactsstatic.blob.core.windows.net/public/data/covid-19/covid_confirmed_usafacts.csv", 'r'));
$csv = array_map('str_getcsv', file('covid_confirmed_usafacts.csv'));
//$csv = array_map('str_getcsv', file('https://usafactsstatic.blob.core.windows.net/public/data/covid-19/covid_confirmed_usafacts.csv'));
$sqlState = "CREATE TABLE [CovidByState]( 
		[Date] [date],
		[State] [nvarchar](255) NULL,
		[stateFIPS] [int] NULL,
		[Count] [int] NULL
	);\n";
$sqlCounty = "CREATE TABLE [CovidByCounty]( 
		[Date] [date],
		[countyFIPS] [int] NULL,
		[County] [nvarchar](255) NULL,
		[State] [nvarchar](255) NULL,
		[stateFIPS] [int] NULL,
		[Count] [int] NULL
	);\n";


$mysql = "DROP TABLE IF EXISTS `Covid`;
		 CREATE TABLE `Covid`( 
		`Date` date,
		`countyFIPS` int NULL,
		`County` nvarchar(255) NULL,
		`State` nvarchar(255) NULL,
		`stateFIPS` int NULL,
		`Count` int NULL
	);\n";


foreach($csv as $rNum=>$row)
{
	if($rNum==0) //first row with dates
	{
		$dates = array_slice($row, 4); //remove first 4 elements to put all date columns into an array
	}
	else
	{
		$countyFIPS = $row[0];
		$County = str_replace("'", "''", $row[1]); //escape any ' required for sql
		$State = $row[2];
		$stateFIPS = $row[3];
		$values = array_slice($row, 4); //remaining rows after first 4
		foreach($values as $index=>$value)
		{
			$date = $dates[$index];
			if($County == 'Statewide Unallocated')
			{
				$sqlState .= "INSERT INTO [CovidByState] ([Date], [State], [stateFIPS], [Count]) VALUES ('$date', '$State', $stateFIPS, $value);\n";
			}
			else
			{
				$sqlCounty .= "INSERT INTO [CovidByCounty] ([Date], [countyFIPS], [County], [State], [stateFIPS], [Count]) VALUES ('$date', $countyFIPS, '$County', '$State', $stateFIPS, $value);\n";
			}
			$mysql .= "INSERT INTO Covid (Date, countyFIPS, County, State, stateFIPS, Count) VALUES ('$date', $countyFIPS, '$County', '$State', $stateFIPS, $value);\n";
		}
	}
}

file_put_contents('CovidByState.sql', $sqlState);
file_put_contents('CovidByCounty.sql', $sqlCounty);

// Create database connection
$servername = "surrette.net";
$username = "surrette_newsq1";
$password = "Shs#04SD";
$dbname = "surrette_sql";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	print_r("Connection failed: " . $conn->connect_error);
}

if ($conn->query($mysql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $conn->error;
}
echo "hello";
$conn->close();

?>
