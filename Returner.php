<?php
// helper function that returns all data gathered and end script 
require_once 'set_array_by_key_path.php';

class Returner {
  // storage for values
  private $script_return_array = [];
  private $debug_messages = [];
  public  $debug_level = 0;

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

  // return
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