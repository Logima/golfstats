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
