<?php
$lisaa = false;
if (!isset($_GET['pelaaja']) || Atomik_Db::count('pelaajat', array('id' => $_GET['pelaaja'])) != 1) {
  $lisaa = true;
}

$tasoitusMaara = 0;
$tasoitukset = array();
if (empty($_POST) && !$lisaa) {
  $pelaajanTiedot = Atomik_Db::find('pelaajat', array('id' => $_GET['pelaaja']));
  $tasoitukset = Atomik_Db::findAll('tasoitukset', array('pelaaja' => $_GET['pelaaja']), 'lahtien')->fetchAll();
  foreach ($tasoitukset as $knimi => $tasoitus) {
    $tasoitukset[$knimi]['lahtien'] = date("j.n.Y", $tasoitus['lahtien']);
  }
  $tasoitusMaara = count($tasoitukset);
} elseif (!empty($_POST)) {
  Atomik::needed('filterit');
  $kentat = tarkistaPelaajaSyote($_POST);
  if ($kentat[0] !== false) {
    list($pelaajanTiedot, $tasoitukset) = $kentat;
    $tasoitusMaara = count($tasoitukset);
    if ($lisaa) {
      $pelaajanId = Atomik_Db::insert('pelaajat', $pelaajanTiedot);
      if (!$pelaajanId) {
        Atomik::flash("Virhe lisättäessä pelaajaa " . $pelaajanTiedot['nimi'] . ".", "error");
        $virhe = true;
      }
    } else {
      $pelaajanId = $_GET['pelaaja'];
      if (!Atomik_Db::update('pelaajat', $pelaajanTiedot, array('id' => $pelaajanId))) {
        Atomik::flash("Virhe muokattaessa pelaajaa " . $pelaajanTiedot['nimi'] . ".", "error");
        $virhe = true;
      }
    }
    
    if (!isset($virhe)) {
      A('db:delete from tasoitukset where pelaaja = ?', array($pelaajanId));
      foreach ($tasoitukset as $tasoitus) {
        Atomik_Db::insert('tasoitukset', array('pelaaja' => $pelaajanId, 'tasoitus' => $tasoitus['tasoitus'], 'lahtien' => strtotime($tasoitus['lahtien'])));
      }
      Atomik::flash("Pelaajan " . $pelaajanTiedot['nimi'] . (($lisaa)?" lisäys":" muokkaus") . " onnistui.");
      Atomik::redirect('/pelaajat');
    }
  } else {
    list($pelaajanTiedot, $tasoitukset) = $kentat;
    $tasoitusMaara = count($tasoitukset);
  }
}