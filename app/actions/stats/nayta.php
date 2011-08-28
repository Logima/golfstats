<?php
if (!isset($_GET['pelaaja']) || Atomik_Db::count('pelaajat', array('id' => $_GET['pelaaja'])) != 1) {
  Atomik::flash("Pelaajaa ei löydy.", "error");
  Atomik::redirect('/stats');
}

$pelaaja = Atomik_Db::find('pelaajat', array('id' => $_GET['pelaaja']));

if (Atomik_Db::count('kierroksen_pelaajat', array('pelaaja' => $_GET['pelaaja'])) == 0) {
  Atomik::flash("Pelaajalla " . $pelaaja['nimi'] . " ei ole yhtäkään kierrosta.", "error");
  Atomik::redirect('/stats');
}

