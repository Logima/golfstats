<?php
$kenttienNimet = array('nimi', 'crslopemv', 'crslopemk', 'crslopenk', 'crslopems', 'crslopens', 'crslopemp', 'crslopenp');
if (array_keys($_POST) == $kenttienNimet) {
  $sanitoituPost = array();
  $virhe = false;
  foreach ($_POST as $knimi => $kentta) {
    $sanitoituPost[$knimi] = filter_var($kentta, FILTER_SANITIZE_STRING);
    if (strlen($kentta) < 3 || ($knimi != 'nimi' && !preg_match('/^\d+\.?\d+\/\d+$/', $kentta))) {
      $virhe = true;
      Atomik::flash("Kenttiä virheellisesti täytetty.", "error");
      break;
    }
  }
  if (!$virhe) {
    if (Atomik_Db::insert('kentat', $sanitoituPost))
      Atomik::flash("Kenttä $sanitoituPost[nimi] lisätty.");
    else
      Atomik::flash("Virhe lisättäessä kenttää $sanitoituPost[nimi].", "error");
  }
}
