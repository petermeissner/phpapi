<?php
/**
 * set_array_by_key_path
 * 
 * @param array $data the array to manipulate 
 * @param string $path the key path 
 * @param mixed $value the value to store under key path
 * 
 * @return void
 */
function set_array_by_key_path(array &$data, $path, $value) {
    $temp = &$data;
    foreach(explode("/", $path) as $key) {
        $temp = &$temp[$key];
    }
    $temp = $value;
}
?>