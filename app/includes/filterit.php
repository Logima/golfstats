<?php

function tarkistaKenttaSyote($post) {
  $kenttienNimet = array('nimi', 'crslopemv', 'crslopemk', 'crslopenk', 'crslopems', 'crslopens', 'crslopemp', 'crslopenp');
  $kentanTiedot = array_slice($post, 0, 8);
  if (array_keys($kentanTiedot) !== $kenttienNimet) {
    return array(false, array(), array());
  }
  $sanitoidutTiedot = filter_var_array($kentanTiedot, FILTER_SANITIZE_STRING);
  $sanitoidutVaylat = filter_var_array(array_slice($post, 8), FILTER_VALIDATE_INT);
  foreach ($sanitoidutTiedot as $knimi => $kentta) {
    if (strlen($kentta) < 3 || ($knimi != 'nimi' && !preg_match('/^\d+\.?\d+\/\d+$/', $kentta))) {
      Atomik::flash("Kenttiä virheellisesti täytetty.", "error");
      return array(false, $sanitoidutTiedot, $sanitoidutVaylat);
    }
  }
  $vaylienPar = array();
  $vaylienHcp = array();
  foreach ($sanitoidutVaylat as $knimi => $kentta) {
    if (strlen($kentta) === 0) break;
    $knimiOsat = explode('_', $knimi);
    if ($knimiOsat[2] == 'par') {
      if ($kentta < 2 || $kentta > 6) {
        Atomik::flash("Väylän numero " . $knimiOsat[1] . " par ei ole kelvollinen.", "error");
        return array(false, $sanitoidutTiedot, $sanitoidutVaylat);
      }
      $vaylienPar[$knimiOsat[1]] = $kentta;
    } else if ($knimiOsat[2] == 'hcp') $vaylienHcp[$knimiOsat[1]] = $kentta;
  }
  $vaylienHcpCopy = $vaylienHcp;
  sort($vaylienHcpCopy);
  if (count($vaylienHcpCopy) == 0) {
    Atomik::flash("Yhdenkään väylän tietoja ei annettu.", "error");
    return array(false, $sanitoidutTiedot, $sanitoidutVaylat);
  } else if ($vaylienHcpCopy !== range(1,count($vaylienHcpCopy))) {
    Atomik::flash("HCP:t eivät täsmää.", "error");
    return array(false, $sanitoidutTiedot, $sanitoidutVaylat);
  }
  return array($sanitoidutTiedot, $sanitoidutVaylat, $vaylienPar, $vaylienHcp);
}
