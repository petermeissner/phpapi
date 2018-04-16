<?php
// array key exists or null
function array_key_exists_or_default($arr, $key, $default = NULL){
  return array_key_exists($key, $arr) ? $arr[$key] : $default;
}
?>