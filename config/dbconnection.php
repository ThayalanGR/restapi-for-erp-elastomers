<?php
define('DB_NAME', 'db_elastomers');
define('DB_USER', 'root');
define('DB_PASSWORD', 'wepl');
define('DB_HOST', 'localhost');

$DB = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// else {
//     echo "connection success";
// }
