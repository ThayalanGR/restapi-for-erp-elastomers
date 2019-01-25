<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../config/dbconnection.php");


if (isset($_GET['id'])) {
    // $sql = "select docId from tbl_despatch where docId = '".$doc[1]."' and docType = '".$doc[0]."'";
    // $data = mysqli_query($DB, $sql);
    // $assoc = mysqli_fetch_assoc($data);
    // $docId = $assoc["docId"];
    
    $array = array("response" => $_GET['id']);
    echo json_encode($array);
}
