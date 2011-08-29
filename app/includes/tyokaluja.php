<?php

function slope($tasoitus, $crslope, $kentanPar) {
  $crslope = explode('/', $crslope);
  return round($tasoitus * ($crslope[1]/113) + ($crslope[0]-$kentanPar));
}

function haeTasoitus($pelaajaId, $aika) {
  $q = Atomik_Db::query('select tasoitus from tasoitukset where pelaaja=? and lahtien < ? order by lahtien desc limit 1', array($pelaajaId, $aika))->fetch();
  if ($q === false) return 54;
  else return $q['tasoitus'];
}

function bogeyPisteet($pelaajanSlope, $hcp, $par, $lyonnit) {
  $pelaajanPar = $par;
  if ($pelaajanSlope >= 36) {
    $pelaajanPar++;
    $pelaajanSlope -= 18;
  }
  if ($pelaajanSlope >= 18) {
    $pelaajanPar++;
    $pelaajanSlope -= 18;
  }
  $pelaajanPar += (int)($pelaajanSlope >= $hcp);
  $pisteet = ($pelaajanPar - $lyonnit +2);
  if ($pisteet > 0) return $pisteet;
  return 0;
}

function kierrosPelaajatKantaan($pelaajienTiedot) {
  foreach ($pelaajienTiedot as $pelaajaId => $pelaaja) {
    foreach ($pelaaja['vaylat'] as $vaylaNro => $vayla) {
      $pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkeri'] = (isset($vayla['bunkkerissa']) && $vayla['bunkkerissa'] == 'on') ? $vayla['bunkkerista'] : 0;
      unset($pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkerissa']);
      unset($pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkerista']);
      $pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['greeniosuma'] = (isset($vayla['greeniosuma'])) ? 1 : 0;
    }
  }
  return $pelaajienTiedot;
}

function kierrosPelaajatKannasta($pelaajienTiedot) {
  foreach ($pelaajienTiedot as $pelaajaId => $pelaaja) {
    foreach ($pelaaja['vaylat'] as $vaylaNro => $vayla) {
      $pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkerissa'] = ($vayla['bunkkeri'] > 0) ? 'on' : 0;
      $pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkerista'] = $vayla['bunkkeri'];
      unset($pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['bunkkeri']);
      if ($vayla['greeniosuma'] == '1') $pelaajienTiedot[$pelaajaId]['vaylat'][$vaylaNro]['greeniosuma'] = 'on';
    }
  }
  return $pelaajienTiedot;
}

function arvoIndeksiksi($array, $arvo, $poistaPienet = false) {
  $out = array();
  foreach ($array as $value) {
    if ($poistaPienet) {
      foreach ($value as $kentanNimi => $kentta) {
        if (strlen($kentanNimi) < 3) unset($value[$kentanNimi]);
      }
    }
    $out[$value[$arvo]] = $value;
    unset($out[$value[$arvo]][$arvo]);
  }
  return $out;
}

function yhteensaValilta($ensimmianen, $viimeinen, $array) {
  $out = array();
  for ($i = $ensimmianen; $i <= $viimeinen; $i++) {
    foreach ($array[$i] as $kentanNimi => $kentta) {
      if (!isset($out[$kentanNimi])) $out[$kentanNimi] = 0;
      if ($kentanNimi == 'bunkkeri' && $kentta > 0) $out[$kentanNimi]++; 
      else $out[$kentanNimi] += $kentta;
    }
  }
  return $out;
}
