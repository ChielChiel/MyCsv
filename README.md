# MyCsv
A PHP library for using the mysql framwork for .csv management.

# Setup
Put the config.php file in the root directory of your webserver. 
After that you can put the Mycsv.php file anywhere in your directory. As long as you include it right it will be accesible from 
anywhere on your webserver. You can simple use the sql language for .csv files. 

# Examples
## Setup
First, put the config.php file in the root of your directory, by doing this, all of the paths used in the programm will be absolute to the root and thus making it easier to refer to.

At the top of the file where you want to use MyCSV in put this:
```php
require("../config.php"); //sets Root dir so that all file paths are relative to the webservers root
require(ROOT_DIR."/includes/Mycsv.php"); //The Mycsv class. Use the absolute path from root after ROOT_DIR
```
Note that the first path is relative to the file where it is included in.

## Connect
There is a way to connect to the location of the 'databases' or folder and you can connect directly to the 'database' or folder directly
### 'Databases location'
The following code connects to the folder where all your 'databases' are stored:
```php
$conn = new mycsv("/data/"); //The location where your 'databases' will be created is (from root) /data/. 
//So the location is from the root of your directory "/data/myDB/". In here will your tables or .csv files be stored.
```
### 'Database itself'
```php
$conn = new mycsv("/data/", "myDB"); //folder where your "databases" are stored and the name of your "database" aka folder. 
//So the location is from the root of your directory "/data/myDB/". In here will your tables or .csv files be stored.
```

###conections
To check whether or not the connection, and thus mycsv could find the given folder, use the following statement:
```php
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully";
```
To close a connection and in this case the directory use the following:
```php
$conn->close();
```
## The use of sql statements

```php
// Create table
$sql = "CREATE TABLE MyGuests (
id [ai],                      //[ai] means autoincrement
firstname,
lastname,
email,
reg_date [timestamp]        //[timestamp] means the current date of writing the data to the .csv file. 
)";

//This 'sql' statement create a file MyGuests.csv in de directory "/data/myDB/" 
//with the columns id,firstname,lastname,email and reg_date

if ($conn->query($sql) == true) {
    echo "Table MyGuests created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
```
