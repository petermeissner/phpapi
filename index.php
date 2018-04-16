<?php
// phpcs:ignore
// includes - functions -------------------------------------------------------- 

require_once 'array_key_exists_or_default.php';
require_once 'clean_up_string.php';
require_once 'api_gather_request_data.php';


// includes - classes ----------------------------------------------------------
require_once 'Returner.php';



// options ---------------------------------------------------------------------

// setting debug_level to use throughout script for logging to user facing response
//   0 = off
//   1 = minor
//   2 = full
$debug_level = 0 ;



// functions -------------------------------------------------------------------

// initialize
$returner = new Returner($debug_level);
$api_data = [];


// gather relevant input data --------------------------------------------------
api_gather_request_data($api_data, $returner);
$returner->add_debug_message($api_data, "api_data");

// initialize connection to db
$db    = new SQLite3("api_backend.db");
$table = $api_data['table'];
  
// check table exists
$sql = "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = '$table' COLLATE NOCASE";
$results = $db->query($sql);
$table_exists_results = [];
if ( $row = $results->fetchArray() ) {
  $table_exists_results[] = $row;
}
$table_exists = $table_exists_results[0]['COUNT(*)'] == 1;

$returner->add_debug_message($table_exists_results, "sql_res", 1);
$returner->add_return_value("feedback/api_exists", $table_exists);


// determine sql_method to use
$sql_method = "select";
$returner->add_debug_message($sql_method, "sql_method", 1);

// create SQL statements
if ( $table_exists ){
  $sql = "select * from `$table` 'LIMIT 5'"; 
  $returner->add_debug_message($sql, "sql", 1);
} else {
  $returner->add_debug_message("table does not exist", "sql", 1);
  $returner->return_and_exit(1, "api does not exist");
}


// execute query
$returner->add_return_value('feedback/rows_affected',0);
 
if ( $table_exists ){
  $results = $db -> query($sql);
  $row_count = 0;
  
  // retrieve results
  $res_array = [];
  
  while ( $row = $results->fetchArray() ) {
    $row_count++;
    $res_array[] = $row;
  }
  
  $returner->add_return_value('feedback/rows_affected', $row_count);

  // add results to return array
  $returner->add_return_value('result_set', $res_array);
  
  // close db connection
  $db->close();
}else{
  // close db connection
  $db->close();
  
  // return
  $returner->return_and_exit(1, "api does not exist");
}


// return 
$returner->return_and_exit(0, "");
?>