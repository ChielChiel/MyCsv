<?PHP 

class mycsv {
  
  public $connect_error = false;
  public $sql;
  public $error;
  public $insert_id;
  protected $csvLocation;
  protected $connection;
  protected $dbname;
  protected $dbLocation;
  
  //https://www.w3schools.com/php/php_ref_directory.asp
  //sets up connection to directory with csv tables.
  function __construct($pathToDir, $dbname = "") {
    $pathToDir = ROOT_DIR.$pathToDir;
    $this->connection = opendir($pathToDir);
    
    if($this->connection) {
      $this->connect_error = false;
      $this->csvLocation = $pathToDir;
      if($dbname != "") {
        if (is_dir($this->csvLocation . $dbname)){
            $this->dbLocation = $this->csvLocation . $dbname;
            $this->connect_error = false;
            $this->dbname = $dbname;
            return true;
          } else {
            $this->connect_error = "database named: '" . $dbname .  "' doesn't exist";
            return false;
        }
      }
    } else {
      $this->connect_error = "Cannot find specified folder. Please specify absolute folderpath from dir";
      return false;
    }
  }
  
  //closes connection
  public function close() {
    closedir($this->connection);
    return true;
  }
  
  public function query($sql) {
    substr($sql, -1) != ";" ? $sql = $sql . ";" : $sql; //checks whether to add a ; or not
    $this->sql = $sql;
    $prepSql = explode(' ', $sql);
    
    switch(strtolower($prepSql[0])) { // switch over what kind of sql statement the $sql is.
      case "create": //second word was create so its a create statement.
        if(strtolower($prepSql[1]) == "database") { //third word in the statement was database so create one
          if (is_dir($this->csvLocation . $prepSql[2])){ 
            $this->error = "database named: '" . $prepSql[2] .  "' already exists";
            return false;
          } else {
            mkdir($this->csvLocation . $prepSql[2], 0777);
            $this->error = false;
            return true;
          }
        } else if(strtolower($prepSql[1]) == "table") { //third word in the statement was table so create one + add .csv
          $completePath = $this->completePath(trim($prepSql[2], ' '));
          if(file_exists($completePath)) {
            $this->error = "Cannot create table. Table named " . $prepSql[2] . " already exsits";
            return false;
          } else {
            $fp = fopen($completePath, "a+");
            //All collected data:
            $data = trim($this->get_string_between(trim(preg_replace('/\s+/', '', $sql)), "(", ")"), '"');
            echo "Data: " . $data;
            $data = explode(',', $data);
            echo print_r($data);
            if(fputcsv($fp, $data) !== false) {
              return true;
            } else {
              $this->error = "Something went wrong trying to write data: " . print_r($data) . ", to file " . $fp;
              return false;
            }
            fclose($fp);
          }
         }
        break;
      case "insert":
       if(strtolower($prepSql[1]) == "into") {
         //complete path to the .csv file or table
         $completePath = $this->completePath($prepSql[2]);
         //the fields extracted from the sql query to write to 
         $fieldsToWrite = $this->get_string_between($this->sql, $prepSql[2], "VALUES");
         $fieldsToWrite = explode(",",trim($this->get_string_between(trim(preg_replace('/\s+/', '', $fieldsToWrite)), "(", ")"), '"'));
         //the avaiable fields extracted from the csv table 
         $fields = $this->getLine($completePath, 0);
         //the provided data to write extracted from the sql query 
         $dataToWrite = $this->get_string_between($this->sql, "VALUES", ";");
         $dataToWrite = explode(",",trim($this->get_string_between(trim(preg_replace('/\s+/', '', $dataToWrite)), "(", ")"), '"'));
         //The difference between the provided columns to write and 
         //the avaiable column extracted from the .csv file
         $dif=array_values(array_diff($fields,$fieldsToWrite));
          
         //puts every an template at the index where a computed value should be
         foreach ($dif as $item) { 
           array_splice($dataToWrite, array_search($item, $fields), 0,array('temp'));
         }
         
         //The final array to be written to the csv table
         $final = array();
         for($i=0; $i < count($fields); $i++) {
          if (strpos($fields[$i], ' [ai]') !== false) { //auto increment
           $cellData = $this->getCel($completePath, $this->lastLineNum($completePath), $fields[$i]);
            if($cellData == $fields[$i]) { //no data in the .csv file yet. It is the table column. So start the [ai] at 1
             $cellData = 1;
            } else { //if there is already a number in place, add 1 to that one.
              $cellData = ((int) preg_replace('/[^0-9]/', '', $cellData)) + 1;
            }
            strpos($fields[$i], 'id') !== false ? $this->insert_id = $cellData : $this->insert_id = false;
          } else if (strpos($fields[$i], ' [timestamp]') !== false) { //The column holds a timestamp so put the current time
            $cellData = str_replace('"', "_",(string)date('d-m-Y H:i:s'));
          } else { //just normal, remove the " and '  
            $cellData = str_replace('"', "", str_replace("'", "",$dataToWrite[$i]));
          } 
          array_push($final, $cellData);
         }
          $fp = fopen($completePath, "a+");
          fputcsv($fp, $final);
          fclose($fp);
        return true;  
       } 
        break;
      case "select":
        //echo "selecteer een waarde uit een database " . $prepSql[1] . ";";
        //echo $this->completePath("MyGuests"); 
        return new result($this->csvLocation, str_replace("/","",$this->dbname), $this->sql);
         
        
        break;
      case "update":
        echo "up";
        break;
      case "delete":
        echo "del";
        break;
      case "drop":
        echo "drop";
        break;
    }
    
    //echo $this->sql;
  }

  
   
  
  
  
  //Returns string between $start and $end 
  protected function get_string_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }
  
  //Returns the complete fall path to $table.csv
  protected function completePath($table) {
    //echo "csv location: " . $this->csvLocation . "<br><br>";
    return $this->csvLocation . "/" . str_replace("/","",$this->dbname) . "/" . $table . ".csv";
  }

  protected function getLine($file, $line) {
    $csvfile = fopen($file,'rb');
    while(false !== ($csv = fgetcsv($csvfile))) {
      $data[] = $csv;
    }
    fclose($csvfile);
    return $data[$line];
  }
  
  protected function getCel($file, $line, $column) {
    $csvfile = fopen($file);
    while(false !== ($csv = fgetcsv($csvfile))) {
      $data[] = $csv;
    }
    fclose($csvfile);
    $place = array_search($column, $data[0]);    
    return $data[$line][$place];
   }
  
  //returns the number of lines in a file
  protected function lastLineNum($file) {
    $fp = file($file);     
    return count($fp) - 1;
   }

}
class result extends mycsv {
  public $num_rows;
  public $sql;
  private $i;
  private $gehad;
  private $records;
  private $allColumns;
  protected $csvLocation;
  protected $dbname;
  protected $table;
  
  function __construct($csvLoc, $dbname, $sql) {
    $this->sql = $sql;
    $this->csvLocation = $csvLoc;
    $this->dbname = $dbname;
    $this->num_rows = 0;
    $this->gehad = 0;
    $this->i = -1;
    
    $prepSql = explode(' ', $this->sql);
    $prepSqlLower = explode(' ', strtolower($this->sql));
    $this->table = str_replace(';', '', trim($prepSql[array_search("from", $prepSqlLower) + 1]));
    $completePath = $this->completePath($this->table);   
    
    
    $allColumns = array();
    $temp = explode(',',$this->getColumns($completePath));
    foreach ($temp as $column) {
      $column = str_replace(' [ai]','', $column);
      $column = str_replace('[timestamp]', '',$column);
      array_push($allColumns,trim($column));
    }
    
    //all data to select is between SELECT and FROM
    $toGet = trim($this->get_string_between(strtolower($this->sql), "select", "from"));
    
    if($toGet == "*") { //get everything
      $records = $this->getRecords($completePath);      
    } else {
      $toGet = explode(',',trim($toGet));
      $temp = array();
      foreach($toGet as $column) {
        array_push($temp, trim($column));
        if(in_array(trim($column), $allColumns) == false) {
          $this->num_rows = 0;
          $this->records = false;
          return false;
        }
      }
      $toGet = $temp;
      unset($temp);
      
      $temp = array();
      foreach($allColumns as $column) {
        foreach($toGet as $val2) {
          if($val2 == $column){ $temp[] = $val2; }
        }
      }
      
      $toGet = $temp;
      unset($temp);
      
      $dif = array_diff($allColumns,$toGet);
      $records = $this->getRecords($completePath);
      $temp = array();
      foreach($records as $record) {
        $i = 0;
        foreach($dif as $toDelete) {
          $record[array_search($toDelete, $allColumns)] = "9040f923b4c7ca8c972b87be0581e1e9c54ab183";
        } 
        $record = preg_grep("/9040f923b4c7ca8c972b87be0581e1e9c54ab183/", $record, PREG_GREP_INVERT);
        $record = array_values($record);
        array_push($temp, $record);
      }
      $records = $temp;
      $allColumns = $toGet;
    }
    
    $this->num_rows = count($records) -1 ;
    $this->allColumns = $allColumns;
    $this->records = $records;
    
  }
  
  public function fetch_assoc() {
    if($this->num_rows <= 0) {
      $this->error = "No results found in " . $this->dbname . ". csv file";
      return false;
    }
    $this->i = $this->i + 1;
    if($this->i <= $this->num_rows) {
      $toReturn = array();
      for($i=0; $i<count($this->allColumns); $i++) {
        $toReturn[$this->allColumns[$i]] = $this->records[$this->i][$i];
      }
      return $toReturn;
    }
   }
  
  protected function getColumns($file) {
    $fp = fopen($file,'rb');
    return fgets($fp);
  }
  
  
  
}



?>



