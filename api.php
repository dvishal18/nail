<?php

	extract($_REQUEST);
	
	switch($method) {
		case "getAppointments" : getAppointments();
						break;
		case "getMyAppointments" : getMyAppointments();
						break;	
		case "bookAppointment" : bookAppointment();
						break;
		case "updateStatus" : updateStatus();
						break;	
		case "updateSubscription" : updateSubscription();
						break;	
		case "isSubscribedMember" : isSubscribedMember();
						break;
		case "sendNotification" : sendNotification();
						break;	
	}

	function isSubscribedMember() {
		require "config.php";
		extract($_REQUEST);	
		$data = array();
		$date = date('Y-m-d');
		$selectQuery = "select * from user_profiles where id=$id and subscriptionDate<='$date' and subscriptionExpDate>='$date'";
		
		//echo $selectQuery;
		$result=mysqli_query($con,$selectQuery);
		if(mysqli_num_rows($result)>0) {			
			$reply=array("ack"=>true, "message"=>"Member");
		} else {
			$reply=array("ack"=>false,"message"=>"No Member");	
		}
		mysqli_close($con);
		print(json_encode($reply));
	}

	function updateSubscription() {
		extract($_REQUEST);
		require "config.php";

		$date = date('Y-m-d');
		$expDate = date('Y-m-d', strtotime($date. ' +30 days'));
		//echo $expDate;

		$query_string="update user_profiles set subscriptionDate='$date', subscriptionExpDate='$expDate' where id=$id";
		$query=mysqli_query($con,$query_string);		
		if($query>0) {		
			$reply=array("ack"=>true, "message"=>"Updated Successfully");
		} else {
			$reply=array("ack"=>false, "message"=>"Serveer Error");
		}
	
		print(json_encode($reply));
		mysqli_close($con);
	}

	function updateStatus() {
		extract($_REQUEST);
		require "config.php";

		$userId = $spUserId = 0;
		$selectQuery = "select * from appointments where id=$id";
		$result=mysqli_query($con,$selectQuery);		
		if(mysqli_num_rows($result)>0) {
			while($row=mysqli_fetch_assoc($result)) {	
				$spUserId = $row['spUserId'];
				$userId = $row['userId'];
			}			
		}
		$query_string="update appointments set status='$status' where id=$id";
		$query=mysqli_query($con,$query_string);		
		if($query>0) {
			$selectQuery = "select fcm_registration_id from user_profiles where id=$spUserId or id=$userId";
			//echo $selectQuery;
			$result=mysqli_query($con,$selectQuery);
			$fcmIds = array();
			if(mysqli_num_rows($result)>0) {
			    echo "token";
				while($row=mysqli_fetch_assoc($result)) {	
					$fcmIds[] = $row['fcm_registration_id'];
				}
                //print_r($fcmIds);
				sendNotifications($fcmIds);
			}
			$reply=array("ack"=>true, "message"=>"Updated Successfully..");
		} else {
			$reply=array("ack"=>false, "message"=>"Serveer Error");
		}
	
		print(json_encode($reply));
		mysqli_close($con);
	}

	function getMyAppointments() {
		require "config.php";
		extract($_REQUEST);	
		$data = array();
		$date = date('Y-m-d');
		$time = date("h:i:s");
		if($status=="Upcoming") {
			//SELECT * FROM `appointments` WHERE date>='2018-12-31' and time>'13:00'
			$selectQuery = "select appointments.id, appointments.spUserId, appointments.userId, appointments.date, appointments.time, appointments.gender, appointments.status, appointments.shortDesc, appointments.createdAt, user_profiles.name from appointments, user_profiles where spUserId=user_profiles.id and userId=$userId and date>='$date' and time>'$time'";
		} else {
		    $selectQuery = "select appointments.id, appointments.spUserId, appointments.userId, appointments.date, appointments.time, appointments.gender, appointments.status, appointments.shortDesc, appointments.createdAt, user_profiles.name from appointments, user_profiles where spUserId=user_profiles.id and userId=$userId and date<'$date' and time<'$time'";
			//$selectQuery = "select * from appointments where userId=$userId and date<'$date' and time<'$time'";
		}
		//$selectQuery = "select * from appointments where userId=$userId and status='$status'";	
		$result=mysqli_query($con,$selectQuery);
		if(mysqli_num_rows($result)>0) {
			while($row=mysqli_fetch_assoc($result)) {
				$data[]=$row;
			}
			$reply=array("ack"=>true, "result"=>$data, "message"=>"");
		} else {
			$reply=array("ack"=>false,"message"=>"No record found");	
		}
		mysqli_close($con);
		print(json_encode($reply));
	}

	function getAppointments() {
		require "config.php";
		extract($_REQUEST);	
		$data = array();
		$date = date('Y-m-d');
		$selectQuery = "select * from appointments where spUserId=$userId and status='$status'";	
		$result=mysqli_query($con,$selectQuery);
		if(mysqli_num_rows($result)>0) {
			while($row=mysqli_fetch_assoc($result)) {
				$data[]=$row;
			}
			$reply=array("ack"=>true, "result"=>$data, "message"=>"");
		} else {
			$reply=array("ack"=>false,"message"=>"No record found");	
		}
		mysqli_close($con);
		print(json_encode($reply));
	}

	function bookAppointment() {		
		extract($_REQUEST);
		require "config.php";	
		$reply = array();
		//$createdAt=date('Y-m-d h:i:s');
		$createdAt = date("Y-m-d H:i:sa");
		
		$query="insert into appointments(spUserId, userId, name, date, time, gender, shortDesc, status, createdAt) values($spUserId, $userId, '$name', '$date', '$time','$gender','$shortDesc', 'Pending', '$createdAt')";
		//echo $query;
		$res=mysqli_query($con,$query);
		if($res==1) {
			$lastUserId = mysqli_insert_id($con);
			$reply=array("ack"=>true, "message"=>"Added Successfully.");
		} else {
			$reply=array("ack"=>false,"message"=>"Something went wrong.");	
		}
		mysqli_close($con);
		print(json_encode($reply));
	}

	function sendNotifications($fcmIds) {
		// Replace with the real server API key from Google APIs
	    $apiKey = "AAAA3M9QohA:APA91bGEddlanmDMowNhEcKJnkCgSYxvpqNXwHAsAol7pTv4qd2S-O-_eePDG52nQHAupiKdl3MDjeH8s0gU0QJz9ObKgLKlP9INgkEk5lWrs9F2dpXF9jXwOHmhUULei5lbh0MAkevY";	   

	    // Replace with the real client registration IDs
	    //$registrationIDs = array("id1", "id2");
	    //$registrationIDs = array("c3hKbtIlrjM:APA91bGJQvJn3M_99uxzO5br52OPsar8xcbsjxWlMpQGMC6WBXVY2HefJBEm1BMjbG-cNS_0nZNYsrvIbfHRgxCcR8bk7TK1scJOfRRNSmYIC7hfvmusq9AtU4QoQXEH18XzO2fbjlDy");

	    $registrationIDs = $fcmIds;

	    // Message to be sent
	    $message = "This is matter of notification";
	    $title = "This is title of notification";

	    // Set POST variables
	    $url = 'https://android.googleapis.com/gcm/send';

	    $fields = array(
	        'registration_ids' => $registrationIDs,
	        'data' => array("post_id" => "1", "message" => $message, "body" => $message, "title" => $title ),
	    );
	    $headers = array(
	        'Authorization: key=' . $apiKey,
	        'Content-Type: application/json'
	    );

	    // Open connection
	    $ch = curl_init();

	    // Set the URL, number of POST vars, POST data
	    curl_setopt( $ch, CURLOPT_URL, $url);
	    curl_setopt( $ch, CURLOPT_POST, true);
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
	    //curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields));

	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    // curl_setopt($ch, CURLOPT_POST, true);
	    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $fields));

	    // Execute post
	    $result = curl_exec($ch);

	    // Close connection
	    curl_close($ch);
	    // print the result if you really need to print else neglate thi
	    //echo $result;
	    //print_r($result);
	    //var_dump($result);
	}

	function sendNotification() {
		// Replace with the real server API key from Google APIs
	    $apiKey = "AAAA3M9QohA:APA91bGEddlanmDMowNhEcKJnkCgSYxvpqNXwHAsAol7pTv4qd2S-O-_eePDG52nQHAupiKdl3MDjeH8s0gU0QJz9ObKgLKlP9INgkEk5lWrs9F2dpXF9jXwOHmhUULei5lbh0MAkevY";	   

	    // Replace with the real client registration IDs
	    //$registrationIDs = array("id1", "id2");
	    $registrationIDs = array("c3hKbtIlrjM:APA91bGJQvJn3M_99uxzO5br52OPsar8xcbsjxWlMpQGMC6WBXVY2HefJBEm1BMjbG-cNS_0nZNYsrvIbfHRgxCcR8bk7TK1scJOfRRNSmYIC7hfvmusq9AtU4QoQXEH18XzO2fbjlDy");

	    //$registrationIDs = $fcmIds;

	    // Message to be sent
	    $message = "This is matter of notification";
	    $title = "This is title of notification";

	    // Set POST variables
	    $url = 'https://android.googleapis.com/gcm/send';

	    $fields = array(
	        'registration_ids' => $registrationIDs,
	        'data' => array("post_id" => "1", "message" => $message, "body" => $message, "title" => $title ),
	    );
	    $headers = array(
	        'Authorization: key=' . $apiKey,
	        'Content-Type: application/json'
	    );

	    // Open connection
	    $ch = curl_init();

	    // Set the URL, number of POST vars, POST data
	    curl_setopt( $ch, CURLOPT_URL, $url);
	    curl_setopt( $ch, CURLOPT_POST, true);
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
	    //curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields));

	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    // curl_setopt($ch, CURLOPT_POST, true);
	    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $fields));
	    $result = curl_exec($ch);
	    curl_close($ch);

	    echo "Result : ".$result;
	    
	}
?>