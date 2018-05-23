<?php
// phpcs:ignore
// includes - functions --------------------------------------------------------

require_once 'array_key_exists_or_default.php';
require_once 'clean_up_string.php';
require_once 'api_gather_request_data.php';


// include api(s)

require_once 'api_station.php';
require_once 'api_wikisaur.php';
require_once 'api_ruhh_slack_join.php';

// includes - classes ----------------------------------------------------------

require_once 'Api_data_store.php';



// options ---------------------------------------------------------------------

// setting debug_level to use throughout script for logging to user facing response
//   0 = off
//   1 = minor
//   2 = full
$debug_level = 0 ;



// doing-duty-to-do ------------------------------------------------------------


// initialize api data storage
$api_data_store = new Api_data_store($debug_level);

// gather relevant data
$api_data_store->api_gather_request_data($_SERVER);

var_dump($api_data_store->api_data['api']);


// route to api
switch ( $api_data_store->api_data['api'] ) {
  
  case 'wikisaur':
    api_wikisaur($api_data_store);
    break;
  
  case 'station':
    api_station($api_data_store);
    break;

  case 'ruhhslack':
    api_ruhhslack($api_data_store);

  default:
    break;
}



// fallback return
$api_data_store->return_and_exit(2, "no api route found");
?>
