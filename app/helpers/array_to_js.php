<?php
function array_to_js($input, $arrayName, $prefix = "") {
  if (!is_array($input)) return;
  foreach ($input as $nimi => $rivi) {
    if (is_array($rivi)) {
      echo $prefix . $arrayName . '[\'' . $nimi . "'] = new Array();\r\n";
      array_to_js($rivi, $arrayName . '[\'' . $nimi . "']", $prefix);
    } else {
      echo $prefix . $arrayName . '[\'' . $nimi . "'] = '$rivi';\r\n";
    }
  }
}
