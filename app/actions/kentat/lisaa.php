<?php

Atomik::needed('filterit');

$kentat = tarkistaKenttaSyote($_POST);

if ($kentat[0] !== false) {
  if ($id = Atomik_Db::insert('kentat', $kentat[0])) {
    foreach ($kentat[2] as $vaylanNumero => $par) {
      A('db:insert into kentan_vaylat values (?, ?, ?, ?)', array($id, $vaylanNumero, $kentat[3][$vaylanNumero], $par));
    }
    Atomik::flash("Kenttä " . $kentat[0]['nimi'] . " lisätty.");
    Atomik::redirect('/kentat');
  } else {
    Atomik::flash("Virhe lisättäessä kenttää " . $kentat[0]['nimi'] . ".", "error");
    $sanitoidutTiedot = $kentat[0];
    $sanitoidutVaylat = $kentat[1];
  }
} else {
  $sanitoidutTiedot = $kentat[1];
  $sanitoidutVaylat = $kentat[2];
}

//täytetään asettamattomat tyhjillä
$kenttienNimet = array('nimi', 'crslopemv', 'crslopemk', 'crslopenk', 'crslopems', 'crslopens', 'crslopemp', 'crslopenp');
foreach ($kenttienNimet as $knimi) {
  if (!isset($sanitoidutTiedot[$knimi])) $sanitoidutTiedot[$knimi] = "";
}
for ($i = 1; $i < 19; $i++) {
  if (!isset($sanitoidutVaylat['vayla_' . $i . '_par'])) $sanitoidutVaylat['vayla_' . $i . '_par'] = "";
  if (!isset($sanitoidutVaylat['vayla_' . $i . '_hcp'])) $sanitoidutVaylat['vayla_' . $i . '_hcp'] = "";
}
