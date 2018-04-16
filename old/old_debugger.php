<?php
// helper function: print out objects for debugging
$debug_messages = [];
function debug_message( $to_be_printed, $label = "", $min_debug_level = 1){
  global $debug_level;
  global $debug_messages;
  if( empty($to_be_printed) ){
    $to_be_printed = NULL;
  }
  if ( $debug_level >= $min_debug_level ){
    if( $label !== "" ){
      $debug_messages[$label] = $to_be_printed;
    }else{
      $debug_messages[] = $to_be_printed;
    }
  }
}
?>