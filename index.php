<?php
// The MIT License (MIT)

// Original Work: Copyright (c) 2016 Maurits van der Schee
//   file source: https://raw.githubusercontent.com/mevdschee/php-crud-api/df7f5a4a78c06e4838d6caa459a22da64aea1524/extras/core.php
//   repo: https://github.com/mevdschee/php-crud-api

// Modifications: Copyright (c) 2018 Peter Meissner

// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE. 

// -----------------------------------------------------------------------------




// options ---------------------------------------------------------------------

// setting debug_level to use throughout script for logging to user facing response
//   0 = off
//   1 = minor
//   2 = full
$debug_level = 0  ;





// functions -------------------------------------------------------------------

// helper function: clean up parameter strings for SQL usage
function clean_up_string(&$string)
{
  $string = 
  preg_replace(
    $pattern     = '/\W+/ui', # all non-word characters ignoring case and using unicode
    $replacement = '', 
    $subject     = 
      mb_strtolower(
        urldecode(
            $string
        )
      )
  );
}

// helper function: print out objects for debugging
$debug_messages = [];
function debug_message( $to_be_printed, $label = "", $min_debug_level = 1){
  global $debug_level;
  global $debug_messages;
  if ( $debug_level >= $min_debug_level ){
    if( $label !== "" ){
      $debug_messages[$label] = $to_be_printed;
    }else{
      $debug_messages[] = $to_be_printed;
    }
  }
}


// helper function that returns all data gathered and end script 
$script_return_array = [];
function script_return( $exit_value = 0, $exit_message = ""){
  
  // globals to use
  global $debug_level;
  global $debug_messages;
  global $script_return_array;
  
  // decide if to return debug messages or not
  if( $debug_level > 0 ){
    $script_return_array['debug_messages'] = $debug_messages;
  }

  // check if result set exists already otherwise add it
  if ( !array_key_exists('result_set', $script_return_array) ){
    $script_return_array['result_set'] = [];
  }

  // add exit code to return
  $script_return_array['exit_message'] = $exit_message;

  // add exit message to return
  $script_return_array['exit_value'] = $exit_value;

  // set return type
  header('Content-Type: application/json');

  // return info to user
  echo 
    json_encode(
      $script_return_array, 
      JSON_UNESCAPED_UNICODE | 
        JSON_UNESCAPED_SLASHES | 
        JSON_NUMERIC_CHECK | 
        JSON_PRETTY_PRINT
      );
      
      // end script 
      exit($exit_value);
}





// gather relevant input data --------------------------------------------------

// raw data 
debug_message($_SERVER, "server", 2);
debug_message($_SERVER['REQUEST_URI'], "request_uri", 1);


// get the HTTP method
$http_method  = $_SERVER['REQUEST_METHOD'];
$script_return_array['http_method'] = $http_method;
debug_message($http_method, "http_method", 1);

// get URL
$parsed_url = parse_url($_SERVER['REQUEST_URI']);
$script_return_array['api'] = $parsed_url['path'];
debug_message(
  $value = $parsed_url, 
  $label = "parsed_url", 
  $min_debug_level = 1
);

// get query string
parse_str($parsed_url['query'], $parsed_query_string); 
debug_message($parsed_query_string,    "parsed_query_string", 1);

   
// get api path
$request = explode("/", $parsed_url['path']);
array_shift($request);
array_shift($request);
array_walk($request, 'clean_up_string');
debug_message($request, "request", 1);


// get request body
$input   = json_decode(file_get_contents('php://input'),true);
if (!$input) $input = array();
debug_message($input, "input", 1);


// process path
$table = $request[0];
debug_message($table, "table", 1);




// process inputs --------------------------------------------------------------


// initialize connection to db
$db = new SQLite3("api_backend.db");

// check table exists
$sql = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' and name = '$table' COLLATE NOCASE";

$results = $db -> query($sql);
$table_exists_results = [];
if ( $row = $results->fetchArray() ) {
  $table_exists_results[] = $row;
}
$table_exists = $table_exists_results[0]['COUNT(*)'] == 1;
debug_message($table_exists_results, "sql_res", 1);
$script_return_array['api_exists'] = $table_exists;


// // escape the columns and values from the input object
// $columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($input));
// $values = array_map(function ($value) use ($db) {
//   if ($value===null) return null;
//   return SQLite3::escapeString((string)$value);
// },array_values($input));

// // build the SET part of the SQL command
// $set = '';
// for ( $i = 0; $i < count( $columns ); $i++ ) {
//   $set.=($i>0?',':'').'`'.$columns[$i].'`=';
//   $set.=($values[$i]===null?'NULL':'"'.$values[$i].'"');
// }


// determine sql_method to use
switch ( mb_strtolower($parsed_query_string['sql_method']) ) {
  case 'select':
    $sql_method = "select"; break;
  case 'update':
    $sql_method = "update"; break;
  case 'insert':
    $sql_method = "insert"; break;
  case 'delete':
    $sql_method = "delete"; break;
  default:
    $sql_method = "select"; break;
}
debug_message($sql_method,             "sql_method",          1);


// create SQL statements
if ( $table_exists ){
  switch ($sql_method) {
    case 'select':
      if( $http_method === "GET" ){
        $sql = "select * from `$table`".($key?" WHERE id=$key":''); 
        break;
      }else{
        break; 
      }
    case 'update':
      if( $http_method === "PUT" ){
        $sql = "update `$table` set $set where id=$key"; 
        break;
      }else{
        break; 
      }
    case 'insert':
      if( $http_method === "POST" ){
        $sql = "insert into `$table` set $set"; 
        break;
      }else{
        break; 
      }
    case 'delete':
      if( $http_method === "DELETE" ){
        $sql = "delete from `$table` where id=$key"; 
        break;
      }else{
        break;
      }
  }
  debug_message($sql, "sql", 1);
} else {
  debug_message("table does not exist", "sql", 1);
  script_return(1, "api does not exist");
}
$script_return_array['sql_method'] = $sql_method;


// execute query
if ( $table_exists ){
  $results = $db -> query($sql);

  // retrieve results
  $res_array = [];
  while ( $row = $results->fetchArray() ) {
    $res_array[] = $row;
  }
  
  // add results to return array
  $script_return_array['result_set'] = $res_array;
  
  // close db connection
  $db->close();
}else{
  // close db connection
  $db->close();
  
  // return
  script_return(1, "api does not exist");
}





/*
// print results, insert id or affected row count
if ($method == 'GET') {
  if (!$key) echo '[';
  for ($i=0;$i<mysqli_num_rows($result);$i++) {
    echo ($i>0?',':'').json_encode(mysqli_fetch_object($result));
  }
  if (!$key) echo ']';
} elseif ($method == 'POST') {
  echo mysqli_insert_id($link);
} else {
  echo mysqli_affected_rows($link);
}
*/

// collect info and return



$script_return_array['rows_affected'] = 'tbd';


script_return(0);
?>