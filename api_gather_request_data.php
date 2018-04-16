<?php
function api_gather_request_data(&$data, &$returner){
  global $_SERVER;

  // HTTP method
  $http_method  = array_key_exists_or_default($_SERVER, 'REQUEST_METHOD');
  
  // get URL
  $http_uri   = array_key_exists_or_default($_SERVER, 'REQUEST_URI'); 
  $parsed_url = parse_url($http_uri);
  $parsed_url['path']  = array_key_exists_or_default($parsed_url, 'path');
  $parsed_url['query'] = array_key_exists_or_default($parsed_url, 'query');
  parse_str($parsed_url['query'], $parsed_query_string); 
  
  // get api path
  $request = explode("/", $parsed_url['path']);
  array_shift($request);
  array_shift($request);
  array_walk($request, 'clean_up_string');

  // get input data
  $input   = json_decode(file_get_contents('php://input'), true);
  if (!$input) $input = array();

  // process path
  $table = array_key_exists_or_default($request, 0);


  // debug info
  $returner->add_debug_message($_SERVER, "server", 2);
  $returner->add_debug_message(
    $to_be_printed   = array_key_exists_or_default($_SERVER,'REQUEST_URI'),
    $label           = "request_uri",
    $min_debug_level = 1
  );
   
  $returner->add_debug_message($http_method, "http_method", 1);
  
  $returner->add_debug_message(
    $value = $parsed_url, 
    $label = "parsed_url", 
    $min_debug_level = 1
  );

  $returner->add_debug_message($parsed_query_string,    "parsed_query_string", 1);
  $returner->add_debug_message($request, "request", 1);
  
  
  $returner->add_debug_message($input, "input", 1);
  
  $returner->add_debug_message($table, "table", 1);
  
  // return info 
  $returner->add_return_value('http_method', $http_method);
  $returner->add_return_value('api', $parsed_url['path']);

  // api info
  $data['http_method'] = $http_method;
  $data['api']         = $parsed_url['path'];
  $data['query']       = $parsed_url['query'];
  $data['table']       = $table;
  $data['input']       = $input;
}
?>