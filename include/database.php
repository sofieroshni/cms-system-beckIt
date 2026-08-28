<?php  $connection = mysqli_connect('localhost', 'cms-becktIt', 'password', 'cms-becktit',3307);

if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}