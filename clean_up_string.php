<?php
// helper function: clean up parameter strings for SQL usage
function clean_up_string( &$string )
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
?>