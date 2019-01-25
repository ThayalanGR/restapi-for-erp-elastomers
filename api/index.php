<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$array = array("response" => "mmrp restful api service");
echo json_encode($array);
