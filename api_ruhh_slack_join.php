<?php
function api_ruhhslack($api_data_store){



  // debug logging
  $api_data_store->add_debug_message($sql_method, "name",  $_GET['name']);
  $api_data_store->add_debug_message($sql_method, "email", $_GET['email']);


  if($api_data_store->api_data['query']['name'] & $api_data_store->api_data['query']['email']){
    // doing duty to do
    include('/home/peter/executables/ruhh_slack_join_request.php');
    header('Location: https://rusershamburg.github.io/');

    // tell data store that api exists
    $api_data_store->add_return_value("feedback/api_exists", true);
  }

 // return
 $api_data_store->return_and_exit(0, "");
}
?>
