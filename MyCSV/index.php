<?

require("../config.php"); //sets Root dir so that all file paths are relative to the webservers root
require("includes/Mycsv.php"); //The Mycsv class

$conn = new mycsv("/data/", "myDB");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
//echo "Connected successfully";


// Create database
//        0      1    2
$sql = "INSERT INTO MyGuests (firstname, lastname, email)
VALUES ('John', 'Doe', 'john@example.com')";

if ($conn->query($sql) == true) {
    echo "New record created successfully";
} else {
    //echo "Error: " . $sql . "<br>" . $conn->error;
}






$conn->close();

?>