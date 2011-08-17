<?php
$tasoitusMaara = 0;
if (count($_POST) > 0) {
  Atomik::needed('filterit');
  $kentat = tarkistaPelaajaSyote($_POST);
  if ($kentat[0] == false) {
    $tasoitusMaara = count($kentat[1])/2-1;
  } else {
    if ($id = Atomik_Db::insert('pelaajat', array_slice($kentat[0], 0, 2))) {
      foreach ($kentat[1] as $knimi => $value) {
        Atomik_Db::insert('tasoitukset', array('pelaaja' => $id, 'tasoitus' => $value, 'lahtien' => $kentat[2][$knimi]));
      }
      Atomik::redirect('/pelaajat');
    } else {
      Atomik::flash("Virhe lisättäessä kenttää " . $kentat[0]['nimi'] . ".", "error");
      $tasoitusMaara = count($kentat[0])/2-1;
    }
  }
}

