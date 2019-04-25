<?php

include("../../config/dbconnection.php");

$count = 0;

$userNo = 101;

$query = "select userId from tbl_users";

$row = mysqli_query($DB, $query);

while($data = mysqli_fetch_array($row)){
    echo $data[0];
    $sql = "update tbl_users set userNo =". $userNo++ ." where userId ='".$data[0]."'";
    mysqli_query($DB, $sql);
}


// print_r($data);

// for($i = 0; i < )