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

// setting debug_level to use throughout script for logging to user facing response
//   0 = off
//   1 = minor
//   2 = full
$debug_level = 0  ;
$debug_messages = [];

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



// get the HTTP method
$http_method  = $_SERVER['REQUEST_METHOD'];

// get URL
$parsed_url = parse_url($_SERVER['REQUEST_URI']);

// get query string
parse_str($parsed_url['query'], $parsed_query_string); 

// get sql_method
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


    
// get api path
$request = explode("/", $parsed_url['path']);
array_shift($request);
array_shift($request);
array_walk($request, 'clean_up_string');


// get request body
$input   = json_decode(file_get_contents('php://input'),true);
if (!$input) $input = array();


// process path
$table = $request[0];



// log to user page 
debug_message($_SERVER,                "server",              2);
debug_message($parsed_url,             "parsed_url",          1);
debug_message($parsed_query_string,    "parsed_query_string", 1);
debug_message($http_method,            "sehttp_methodrver",   1);
debug_message($sql_method,             "sql_method",          1);
debug_message($request,                "request",             1);
debug_message($_SERVER['REQUEST_URI'], "request_uri",         1);
debug_message($input,                  "input",               1);
debug_message($table,                  "table",               1);

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

// execute query
  $results = $db -> query($sql);

// retrieve results
  $res_array = [];
  while ( $row = $results->fetchArray() ) {
    $res_array[] = $row;
  }
} else {
  $res_array = [];
  debug_message("table does not exist", "sql", 1);
}

// close db connection
$db->close();

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
$arr = 
  array(
    'http-method' => $http_method, 
    'sql_method' => $sql_method, 
    'api' => $parsed_url['path'],
    'api_exists' => $table_exists,
    'rows_affected' => 'tbd',
    'result' => $res_array 
  );


if( $debug_level > 0 ){
  $arr['debug_messages'] = $debug_messages;
}

echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
?>