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
$debug_level = 0;

// get the HTTP method
$http_method  = $_SERVER['REQUEST_METHOD'];

// get URL
$parsed_url = parse_url($_SERVER['REQUEST_URI']);

// get query string
parse_str($parsed_url['query'], $parsed_query_string); 

// get sql_method
$sql_method = "select";
switch ($parsed_query_string['sql_method']) {
  case 'select':
    $sql = "select * from `$table`".($key?" WHERE id=$key":''); break;
  #case 'PUT':
  #  $sql = "update `$table` set $set where id=$key"; break;
  case 'insert':
    $sql = "insert into `$table` set $set"; break;
  #case 'DELETE':
  #  $sql = "delete from `$table` where id=$key"; break;
}

// get api path
function clean_up_path(&$string)
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

#$request = explode("/", array_shift(explode('?', $_SERVER['REQUEST_URI'])));
$request = explode("/", $parsed_url['path']);
array_shift($request);
array_shift($request);
array_walk($request, 'clean_up_path');


// get request body
$input   = json_decode(file_get_contents('php://input'),true);
if (!$input) $input = array();


// process path
$table = $request[0];



// log to user page 
if ( $debug_level > 0 ){
echo "<pre>";
  
    if ( $debug_level > 1 ){
      echo PHP_EOL . "server: " . PHP_EOL;
      print_r($_SERVER);
    }

    echo PHP_EOL . "parsed_url: " . PHP_EOL;
    print_r($parsed_url);

    echo PHP_EOL . "parsed_query_string: " . PHP_EOL;
    print_r($parsed_query_string);

    echo PHP_EOL . "http_method: " . PHP_EOL;
    print_r($http_method);

    echo PHP_EOL . "sql_method: " . PHP_EOL;
    print_r($sql_method);

    echo PHP_EOL .  "request: " . PHP_EOL;
    print_r($request);

    echo PHP_EOL .  "request uri: " . PHP_EOL;
    print_r($_SERVER['REQUEST_URI']);
    
    echo PHP_EOL .  "input:" . PHP_EOL;
    print_r($input);
    
    echo PHP_EOL .  "table:" . PHP_EOL;
    print_r($table);

    echo PHP_EOL;
  echo "</pre>";
}



// initialize connection to db
$db = new SQLite3("api_backend.db");

// check table exists
$sql = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' and name = '$table' COLLATE NOCASE";

// execute SQL statement
if ( $debug_level > 0 ){
  echo "<pre>";
  echo PHP_EOL .  "sql:" . PHP_EOL;
  print_r($sql);
}

$results = $db -> query($sql);
$table_exists_results = [];
if ( $row = $results->fetchArray() ) {
  $table_exists_results[] = $row;
}
$table_exists = $table_exists_results[0]['COUNT(*)'] == 1;

// execute SQL statement
if ( $debug_level > 0 ){
  echo PHP_EOL .  "sql res:" . PHP_EOL;
   print_r($table_exists_results);
  echo "</pre>";
}


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



if ( $table_exists ){
  // create SQL based on HTTP method
  switch ($http_method) {
    case 'GET':
      $sql = "select * from `$table`".($key?" WHERE id=$key":''); break;
    #case 'PUT':
    #  $sql = "update `$table` set $set where id=$key"; break;
    case 'POST':
      $sql = "insert into `$table` set $set"; break;
    #case 'DELETE':
    #  $sql = "delete from `$table` where id=$key"; break;
  }

  // execute SQL statement
  if ( $debug_level > 0 ){
    echo "<pre>";
    print_r($sql);
    echo "</pre>";
  }

  $results = $db -> query($sql);
  $res_array = [];
  while ( $row = $results->fetchArray() ) {
    $res_array[] = $row;
  }
} else {
  $res_array = [];
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

// close mysql connection
mysqli_close($link);
*/

$arr = 
  array(
    'http-method' => $http_method, 
    'sql_method' => $sql_method, 
    'api' => $parsed_url['path'],
    'api_exists' => $table_exists,
    'result' => $res_array 
  );
echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
?>