<?php

if (empty($_POST)) {
  $kentat = Atomik_Db::find('pelaajat', array('id' => $_GET['pelaaja']));
  $tasoituksetQuery = Atomik_Db::findAll('tasoitukset', array('pelaaja' => $_GET['pelaaja']));
  $tasoitusMaara = 0;
  foreach ($tasoituksetQuery->toArray() as $value) {
    $kentat["tasoitus_" . $tasoitusMaara] = $value['tasoitus'];
    $kentat["pvm_" . $tasoitusMaara] = date("j.n.Y", $value['lahtien']);
    $tasoitusMaara++;
  }
} else {
  Atomik::needed('filterit');
  $kentat = tarkistaPelaajaSyote($_POST);
  if ($kentat[0] !== false) {
    if (Atomik_Db::update('pelaajat', array_slice($kentat[0], 0, 2), array('id' => $_GET['pelaaja']))) {
      A('db:delete from tasoitukset where pelaaja = ?', array($_GET['pelaaja']));
      foreach ($kentat[1] as $knimi => $value) {
        Atomik_Db::insert('tasoitukset', array('pelaaja' => $_GET['pelaaja'], 'tasoitus' => $value, 'lahtien' => $kentat[2][$knimi]));
      }
      Atomik::flash("Pelaajan " . $kentat[0]['nimi'] . " muokkaus onnistui.");
      Atomik::redirect('/pelaajat');
    } else {
      Atomik::flash("Virhe muokattaessa pelaajaa " . $kentat[0]['nimi'] . ".", "error");
      $tasoitusMaara = count($kentat[0])/2-1;
    }
  } else {
    $tasoitusMaara = count($kentat[1])/2-1;
    $kentat = $kentat[1];
  }
}