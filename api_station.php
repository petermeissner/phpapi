<?php 
function api_station($api_data, $api_data_store){
  // initialize connection to db
  $db    = new SQLite3("api_backend.db");
  $table = $api_data_store->api_data['table'];
    
  // check table exists
  $sql = "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = '$table' COLLATE NOCASE";
  $results = $db->query($sql);
  $table_exists_results = [];
  if ( $row = $results->fetchArray() ) {
    $table_exists_results[] = $row;
  }
  $table_exists = $table_exists_results[0]['COUNT(*)'] == 1;

  $api_data_store->add_debug_message($table_exists_results, "sql_res", 1);
  $api_data_store->add_return_value("feedback/api_exists", $table_exists);


  // determine sql_method to use
  $sql_method = "select";
  $api_data_store->add_debug_message($sql_method, "sql_method", 1);

  // create SQL statements
  if ( $table_exists ){
    $sql = "select * from `$table` 'LIMIT 5'"; 
    $api_data_store->add_debug_message($sql, "sql", 1);
  } else {
    $api_data_store->add_debug_message("table does not exist", "sql", 1);
    $api_data_store->return_and_exit(1, "api does not exist");
  }


  // execute query
  $api_data_store->add_return_value('feedback/rows_affected',0);
  
  if ( $table_exists ){
    $results = $db -> query($sql);
    $row_count = 0;
    
    // retrieve results
    $res_array = [];
    
    while ( $row = $results->fetchArray() ) {
      $row_count++;
      $res_array[] = $row;
    }
    
    $api_data_store->add_return_value('feedback/rows_affected', $row_count);

    // add results to return array
    $api_data_store->add_return_value('result_set', $res_array);
    
    // close db connection
    $db->close();
  }else{
    // close db connection
    $db->close();
    
    // return
    $api_data_store->return_and_exit(1, "api does not exist");
  }


  // return 
  $api_data_store->return_and_exit(0, "");
}
?>