<?php
// helper function that returns all data gathered and end script 
require_once 'set_array_by_key_path.php';

class Api_data_store {
  
  
  // storage for values
  private $script_return_array = [];
  private $debug_messages = [];
  public  $debug_level = 0;
  public  $api_data = [];



  // initialize
  public function __construct($debug_level = 0)
  {
    $this->debug_level = $debug_level;

    $this->script_return_array['feedback'] = [
      "api_exists"   => NULL,
      "exit_message" => NULL,
      "exit_value"   => 1
    ];

    $this->script_return_array['debug']      = [];
    $this->script_return_array['result_set'] = [];
  }



  public function api_gather_request_data($server){
    // HTTP method
    $http_method  = array_key_exists_or_default($server, 'REQUEST_METHOD');
    
    // get URL
    $http_uri   = array_key_exists_or_default($server, 'REQUEST_URI'); 
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
  
    // request from localhost or not
    $request_from_localhost = $server['REMOTE_ADDR'] === '127.0.0.1' || $server['REMOTE_ADDR'] === "::1";
  


    // debug info
    $this->add_debug_message($server, "server", 2);
    $this->add_debug_message(
      $to_be_printed   = array_key_exists_or_default($server,'REQUEST_URI'),
      $label           = "request_uri",
      $min_debug_level = 1
    );
     
    $this->add_debug_message($http_method, "http_method", 1);
    
    $this->add_debug_message(
      $value = $parsed_url, 
      $label = "parsed_url", 
      $min_debug_level = 1
    );
  
    $this->add_debug_message($parsed_query_string,    "parsed_query_string", 1);
    $this->add_debug_message($request, "request", 1);
    
    
    $this->add_debug_message($input, "input", 1);
    
    $this->add_debug_message($table, "table", 1);
    $this->add_debug_message($request_from_localhost, "request_from_localhost", 2);
    
    
    // return info 
    $this->add_return_value('http_method', $http_method);
    $this->add_return_value('api', $parsed_url['path']);
  
    // api info
    $this->api_data['http_method']            = $http_method;
    $this->api_data['api']                    = $parsed_url['path'];
    $this->api_data['query']                  = $parsed_url['query'];
    $this->api_data['table']                  = $table;
    $this->api_data['input']                  = $input;
    $this->api_data['request_from_localhost'] = $request_from_localhost;
  }



  // adding messages
  public function add_debug_message( $to_be_printed, $label = "", $min_debug_level = 1)
  {
    $to_be_printed = $to_be_printed ? $to_be_printed : NULL;
    if ( $this->debug_level >= $min_debug_level ){
      if( $label !== "" ){
        $this->debug_messages[$label] = $to_be_printed;
      }else{
        $this->debug_messages[] = $to_be_printed;
      }
    }
  }




  // get state
  public function get_debug_info(){
    return [
      'debug_level'    => $this->debug_level,
      'debug_messages' => $this->debug_messages
    ];
  } 




  // add to return values
  public function add_return_value($key = "", $value = NULL)
  {
    if( $key !== "" ){
      $this->script_return_array[$key] = $value;
    }else{
      $this->script_return_array[] = $value;
    }
  }




  // return and stop script execution
  public function return_and_exit($exit_value = 0, $exit_message = "ok"){
    // decide if to return debug messages or not
    if( $this->debug_level > 0 ){
      $this->script_return_array['debug'] = $this->get_debug_info();
    }

    // check if result set exists already otherwise add it
    if ( !array_key_exists('result_set', $this->script_return_array) ){
      $this->script_return_array['result_set'] = [];
    }

    // add exit code to return
    $this->script_return_array['feedback']['exit_message'] = $exit_message;

    // add exit message to return
    $this->script_return_array['feedback']['exit_value'] = $exit_value;

    // set return type
    header('Content-Type: application/json');

    // return info to user
    echo 
      json_encode(
        $this->script_return_array, 
        JSON_UNESCAPED_UNICODE | 
          JSON_UNESCAPED_SLASHES | 
          JSON_NUMERIC_CHECK | 
          JSON_PRETTY_PRINT
        );
        
        // end script 
        exit($exit_value);
  }
}
?>