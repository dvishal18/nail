<?php
	$con = mysqli_connect('localhost','templete_app','VGo}4hrd_n?T','templete_app') or die(mysqli_connect_error());
	//define('BASEURL', 'http://192.168.1.9/rechargesystem/');

	header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');

    date_default_timezone_set('Asia/Kolkata');
?>