<?php

function tarkistaKenttaSyote($post) {
  $kenttienNimet = array('nimi', 'crslopemv', 'crslopemk', 'crslopenk', 'crslopems', 'crslopens', 'crslopemp', 'crslopenp');
  $kentanTiedot = array_slice($post, 0, 8);
  if (array_keys($kentanTiedot) !== $kenttienNimet) {
    return array(false, array(), array());
  }
  $sanitoidutTiedot = filter_var_array($kentanTiedot, FILTER_SANITIZE_STRING);
  $sanitoidutVaylat = filter_var_array(array_slice($post, 8), FILTER_SANITIZE_NUMBER_INT);
  
  $vaylat = array();
  $vaylienHcp = array();
  for ($i = 1; $i < 19; $i++) {
    $par = $sanitoidutVaylat['vayla_' . $i . '_par'];
    $hcp = $sanitoidutVaylat['vayla_' . $i . '_hcp'];
    if (strlen($par) > 0 && strlen($hcp) > 0) {
      $vaylat[$i] = array('par' => $par, 'hcp' => $hcp);
      $vaylienHcp[] = (int)$hcp;
    } else break;
  }
  
  foreach ($sanitoidutTiedot as $knimi => $value) {
    if (strlen($value) < 3 || ($knimi != 'nimi' && !preg_match('/^\d+\.?\d+\/\d+$/', $value))) {
      Atomik::flash("Kenttiä virheellisesti täytetty.", "error");
      return array(false, $sanitoidutTiedot, $vaylat);
    }
  }
  
  sort($vaylienHcp);
  if (count($vaylienHcp) == 0) {
    Atomik::flash("Yhdenkään väylän tietoja ei annettu.", "error");
    return array(false, $sanitoidutTiedot, $vaylat);
  } else if ($vaylienHcp !== range(1, count($vaylienHcp))) {
    Atomik::flash("HCP:t eivät täsmää.", "error");
    return array(false, $sanitoidutTiedot, $vaylat);
  }
  
  return array($sanitoidutTiedot, $vaylat);
}

function tarkistaPelaajaSyote($post) {
  $post = filter_var_array($post, FILTER_SANITIZE_STRING);
  $paivamaarat = array();
  $tasoitukset = array();
  foreach ($post as $knimi => $value) {
    if (strlen($value) == 0) {
      Atomik::flash("Kenttiä ei saa jättää tyhjiksi.", "error");
      return array(false, $post);
    }
    if ($knimi == "nimi" || $knimi == "sukupuoli") continue;
    $knimiOsat = explode('_', $knimi);
    if ($knimiOsat[0] == "tasoitus") {
      if (!preg_match('/^\d{1,2}(\.\d)?$/', $value)) {
        Atomik::flash("Annettu tasoitus ei ole kelvollinen.", "error");
        return array(false, $post);
      } else {
        $tasoitukset[$knimiOsat[1]] = $value;
      }
    }
    if ($knimiOsat[0] == "pvm") {
      $pvm = strtotime($value);
      if ($pvm === false) {
        Atomik::flash("Annettu päivämäärä ei ole kelvollinen.", "error");
        return array(false, $post);
      } else {
        $paivamaarat[$knimiOsat[1]] = $pvm;
      }
    }
  }
  return array($post, $tasoitukset, $paivamaarat);
}

function tarkistaKierrosSyote($post) {
  $post = filter_var_array($post, FILTER_SANITIZE_STRING);
  $kierroksenTiedot = array();
  $pelaajienTiedot = array();
  foreach ($post as $knimi => $value) {
    if ($knimi == 'lahtoaika') {
      $pvm = strtotime($value);
      if ($pvm === false) {
        Atomik::flash("Annettu päivämäärä ei ole kelvollinen.", "error");
        $virhe = true;
      } else {
        $kierroksenTiedot[$knimi] = $pvm;
      }
      continue;
    }
    $palat = explode('_', $knimi);
    if ($palat[0] == $knimi) {
      $kierroksenTiedot[$knimi] = $value;
    } else {
      $pelaajaId = $palat[1];
      if (!isset($pelaajienTiedot[$pelaajaId])) $pelaajienTiedot[$pelaajaId] = array('vaylat' => array());
      if ($palat[2] == 'vayla') {
        if (!isset($pelaajienTiedot[$pelaajaId]['vaylat'][$palat[3]])) $pelaajienTiedot[$pelaajaId]['vaylat'][$palat[3]] = array();
        $pelaajienTiedot[$pelaajaId]['vaylat'][$palat[3]][$palat[4]] = $value;
      } else {
        $pelaajienTiedot[$pelaajaId][$palat[2]] = $value;
      }
    }
  }
  if (isset($virhe)) return array(false, $kierroksenTiedot, $pelaajienTiedot);
  return array($kierroksenTiedot, $pelaajienTiedot);
}
