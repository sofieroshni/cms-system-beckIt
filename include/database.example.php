<?php
$connection = mysqli_connect('localhost', 'DIT_BRUGERNAVN', 'DIT_PASSWORD', 'DIT_DATABASENAVN');

if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}