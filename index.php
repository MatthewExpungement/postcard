<?php
require 'vendor/autoload.php';
require_once (__DIR__ . '/../../CLUE/Backend/config.php');
require DB;
$casearray = array();
$sql = "SELECT *, hearings_set.Date as HearingDate FROM case_information INNER JOIN hearings_set ON
case_information.CaseNumber = hearings_set.CaseNumber 
INNER JOIN service ON service.CaseNumber = case_information.CaseNumber
INNER JOIN related_person on related_person.CaseNumber = case_information.CaseNumber
WHERE CaseLocation = '0101' AND hearings_set.Date > NOW()
AND case_information.CaseStatus = 'ACTIVE'
AND hearings_set.SetText = 'TRIAL SET FOR'
AND service.Outcome = 'SV'
AND related_person.Connection = 'DEFENDANT'
AND (related_person.Zip = '21217' OR related_person.Zip = '21215')
AND case_information.CaseNumber NOT IN (SELECT CaseNumber FROM postcard)
LIMIT 1";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Civil Case Reminder</title>

<link rel="stylesheet" href="css/bootstrap.css">

</head>

<body>
<section>
  <div class="jumbotron text-center" style="background-color: #99ccff">
    <div class="container">
      <div class="row">
        <div class="col-xs12">
          <h1>Postcard Service</h1>
          <p>Letting people know about their civil case one postcard at a time.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
try{
	$stmt = $conn_district->prepare($sql);
	if(!$stmt->execute()){
		print_r($stmt->errorInfo());
	}
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		if(in_array($row['CaseNumber'],$casearray))continue;
		$plaintiff = getPlaintiff($row['CaseNumber']);
		if($plaintiff == false){
			echo("Plaintiff Error");
			continue;
		}
		
		$courtdate = date("F jS, Y",strtotime($row['HearingDate']));
		$time = ltrim($row['Time'],'0');
		$courtaddress = '501 E. FAYETTE STREET BALTIMORE, MD 21202';
		sendPostcard($row['CaseNumber'],$row['Name'],$row['Address'],$row['City'],$row['State'],$row['Zip'],$plaintiff,$courtdate,$time,$courtaddress);
		sleep(4);
		$casearray[] = $row['CaseNumber'];
	}
}catch(Exception $e){
	
	echo "\r\n Error \r\n";
	echo $e->getMessage();
	print_r($stmt->errorInfo());
}
function getPlaintiff($casenumber){
	global $conn_district;
	
	$sql = "SELECT * FROM related_person WHERE CaseNumber = :casenumber AND CONNECTION = 'Plaintiff' LIMIT 1";
	$stmt = $conn_district->prepare($sql);
	$stmt->bindParam(':casenumber',$casenumber);
	if(!$stmt->execute()){
		print_r($stmt->errorInfo());
	}
	$row = $stmt->fetch(PDO::FETCH_ASSOC);	

	if($stmt->rowCount() == 1){
		return $row['Name'];
	}
	else return false;
	
}
function addPostcard($casenumber,$datesent){
	global $conn_district;
	$sql = "INSERT INTO postcard (CaseNumber,DateSent) VALUES(:casenumber,:datesent)";
	$stmt = $conn_district->prepare($sql);
	$stmt->bindParam(':casenumber',$casenumber);
	$stmt->bindParam(':datesent',$datesent);
	if(!$stmt->execute()){
		print_r($stmt->errorInfo());
	}
}
function sendPostcard($casenumber,$name,$address,$city,$state,$zip,$plaintiff,$courtdate,$time,$courthouse){
	if(strpos($name,',') !== false){
		$lastname = explode(',',$name)[0];
		$firstname = explode(',',$name)[1];
		$firstname = explode(' ',trim($firstname))[0];
	}else{
		$firstname = $name;
		$lastname = $name;
	}

/*
	echo "<br>\r\n Sending postcard to ";
	echo "\r\n Firstname: " . $firstname;
	echo "\r\n CaseNumber: " . $casenumber;
	echo "\r\n Address: " . $address;
	echo "\r\n Plaintiff: " . $plaintiff;
	echo "\r\n Courtdate: " . $courtdate;
	echo "\r\n Time: " . $time;
	*/
	$file = file_get_contents('html/card.html');
	$lob = new \Lob\Lob('test_4609438f4705a56c52d261ebefafaab62f1');

	$to_address = $lob->addresses()->create(array(
	  'name'          => $firstname . " " . $lastname,
	  'address_line1' => $address,
	  'address_city'  => $city,
	  'address_state' => $state,
	  'address_zip'   => $zip
	));
	$frontmessage = 'You are currently being sued. If you do not show up to your court date you will likely lose your court case. If you cannot afford an attorney you may be able to get free legal help from a number of legal aid organizations. Please call 1-800-510-0050 for more information.';
	$postcard = $lob->postcards()->create(array(
	  'to'          => $to_address['id'],
	  'from'        => 'adr_965b627d38cb5ad2',
	  'front'       => $file,
	  'message'     => $frontmessage,
	  'data[name]'  => $firstname,
	  'data[plaintiff]' => $plaintiff,
	  'data[courtdate]' => $courtdate,
	  'data[time]' => $time,
	  'data[casenum]' => $casenumber,
	  'data[courtaddress]' => $courthouse
	  
	));
		?>
	<section>
  <div class="container well">
    <div class="row">
      <div class="col-lg-4 col-md-4 col-sm-6">
        <div class="media">
          <div class="media-left"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></div>
          <div class="media-body">
            <h2 class="media-heading">Parties: </h2>
            <?php echo "Defendant: " . $firstname . " <br> Address: " . $address . " <br> Plaintiff: " . $plaintiff; ?></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-4 col-sm-6">
        <div class="media">
          <div class="media-left"><span class="glyphicon glyphicon-home" aria-hidden="true"></span> </div>
          <div class="media-body">
            <h2 class="media-heading">Case Information</h2>
            <?php echo "Case Number: " . $casenumber . " <br> Court Date: " . $courtdate . " <br> Time: " . $time; ?></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-4 col-sm-6">
        <div class="media">
          <div class="media-left"> <span class="glyphicon glyphicon-print" aria-hidden="true"></span></div>
          <div class="media-body">
            <h2 class="media-heading">Postcard Information</h2>
            <?php echo "Postcard Arrival Date: " . $postcard['expected_delivery_date']; ?> </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
	//echo "\r\n Postcard will arrive on " . $postcard['expected_delivery_date'];
	//addPostcard($casenumber,$postcard['expected_delivery_date']);
	echo sleep(5);
	//echo "<br>";
	//echo $postcard['thumbnails'][0]['large'];
	?>
	<section>
	<div class="container">
		<div class="row">
        	<div class="col-lg-12 text-center">
            	<h2>Postcard to Be Sent</h2>
            </div>
        </div>
        <div class="row">
        	<div class="col-md-6 text-center">
				<h3>FRONT</h3>
				<?php echo "<image src='" . $postcard['thumbnails'][0]['large'] . "'></image>"; ?>        
            </div>
        	<div class="col-md-6 text-center">
				<h3>BACK</h3>
            	<?php echo "<image src='" . $postcard['thumbnails'][1]['large'] . "'></image>"; ?>              
            </div>
        </div>
	</div>
</section>
    <footer class="text-center">
  <div class="container">
    <div class="row">
      <div class="col-xs-12">
        <p>Copyright © MVLS. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) --> 
<script src="js/jquery-1.11.3.min.js"></script> 
<!-- Include all compiled plugins (below), or include individual files as needed --> 
<script src="js/bootstrap.js"></script>
</body>
</html>
<?php
	
	

	//print_r($postcard);
}
?>