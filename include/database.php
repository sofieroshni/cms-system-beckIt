$connection = mysqli_connect('localhost', 'cms-becktit', 'password', 'cms-becktit');

if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
} 