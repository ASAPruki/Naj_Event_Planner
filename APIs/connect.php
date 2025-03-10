<?php

$host = "localhost";
$username = "root";
$password = "99Vm6tBhw";
$database = "najevents_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
