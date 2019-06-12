<?PHP 

class mycsv {
  
  public $connect_error = false;
  public $sql;
  public $error;
  private $csvLocation;
  private $connection;
  private $dbname;
  private $dbLocation;
  
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
    $this->sql = $sql;
    $prepSql = explode(' ', $sql);
    //print_r($prepSql);
    
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
          $completePath = $this->completePath($prepSql[2]);
          $fieldsToWrite = $this->get_string_between($this->sql, $prepSql[2], "VALUES");
          $fieldsToWrite = explode(",",trim($this->get_string_between(trim(preg_replace('/\s+/', '', $fieldsToWrite)), "(", ")"), '"'));
          $fields = $this->getLine($completePath, 0);
           echo print_r($fields) . "<br>";
         
           echo print_r($fieldsToWrite) . "<br>";
            
         $dif=array_values(array_diff($fields,$fieldsToWrite));
print_r($dif) . "<br>";
         echo "wek" . $dif[0];
         $nonExistent = array();
         for($i=0; $i<count($dif); $i++) {
          array_push($nonExistent, array_search($dif[$i], $fields)); 
         }
           
       }
        break;
      case "select":
        echo "selecteer een waarde uit een database " . $prepSql[1];
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
  private function get_string_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }
  
  //Returns the complete fall path to $table.csv
  private function completePath($table) {
    return $this->csvLocation . "/" . str_replace("/","",$this->dbname) . "/" . $table . ".csv";
  }

   private function getLine($file, $line) {
    $csvfile = fopen($file,'rb');
    while(false !== ($csv = fgetcsv($csvfile))) {
      $data[] = $csv;
    }
    fclose($csvfile);
    return $data[$line];
   }

}

?>