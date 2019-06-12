# MyCsv
A PHP library for using the mysql framwork for .csv management.

# Setup
Put the config.php file in the root directory of your webserver. 
After that you can put the Mycsv.php file anywhere in your directory. As long as you include it right it will be accesible from anywhere on your webserver. You can simple use the sql language for .csv files. 

# Example
```php
require("../config.php"); //sets Root dir so that all file paths are relative to the webservers root
require("includes/Mycsv.php"); //The Mycsv class

$conn = new mycsv("/data/", "myDB"); //folder where your "databases" are stored and the name of your "database" aka folder. So the location is from the root of your directory "/data/myDB/". In here will your tables or .csv files be stored.

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully";

// Create table
$sql = "CREATE TABLE MyGuests (
id [ai],                      //[ai] means autoincrement
firstname,
lastname,
email,
reg_date [timestamp]        //[timestamp] means the current date of writing the data to the .csv file. 
)";

//This 'sql' statement create a file MyGuests.csv in de directory "/data/myDB/" with the columns id,firstname,lastname,email and reg_date

if ($conn->query($sql) == true) {
    echo "Table MyGuests created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();

```
