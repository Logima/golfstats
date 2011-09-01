<?php
$lisaa = false;
if (!isset($_GET['kentta']) || Atomik_Db::count('kentat', array('id' => $_GET['kentta'])) != 1) {
  $lisaa = true;
}

Atomik::needed('tyokaluja');

if (empty($_POST) && !$lisaa) {
  $kentanTiedot = Atomik_Db::find('kentat', array('id' => $_GET['kentta']));
  $vaylienTiedot = arvoIndeksiksi(Atomik_Db::findAll('kentan_vaylat', array('kentta' => $_GET['kentta'])), 'numero');
} elseif (!empty($_POST)) {
  Atomik::needed('filterit');
  $kentat = tarkistaKenttaSyote($_POST);
  if ($kentat[0] !== false) {
    list($kentanTiedot, $vaylienTiedot) = $kentat;
    if ($lisaa) {
      $kentanId = Atomik_Db::insert('kentat', $kentanTiedot);
      if (!$kentanId) {
        Atomik::flash("Virhe lisättäessä kenttää " . $kentanTiedot['nimi'] . ".", "error");
        $virhe = true;
      }
    } else {
      $kentanId = $_GET['kentta'];
      if (!Atomik_Db::update('kentat', $kentanTiedot, array('id' => $kentanId))) {
        Atomik::flash("Virhe muokattaessa kenttää " . $kentanTiedot['nimi'] . ".", "error");
        $virhe = true;
      }
    }
    
    if (!isset($virhe)) {
      $viimeinenVayla = 0;
      foreach ($vaylienTiedot as $vaylanNumero => $vayla) {
        if (Atomik_Db::count('kentan_vaylat', array('kentta' => $kentanId, 'numero' => $vaylanNumero)) == 1) {
          Atomik_Db::update('kentan_vaylat', array('hcp' => $vayla['hcp'], 'par' => $vayla['par']),  array('kentta' => $kentanId, 'numero' => $vaylanNumero));
        } else {
          A('db:insert into kentan_vaylat values (?, ?, ?, ?)', array($kentanId, $vaylanNumero, $vayla['hcp'], $vayla['par']));
        }
        $viimeinenVayla = $vaylanNumero;
      }
      A('db:delete from kentan_vaylat where kentta = ? and numero > ?', array($kentanId, $viimeinenVayla));
      Atomik::flash("Kentän " . $kentanTiedot['nimi'] . " muokkaus onnistui.");
      Atomik::redirect('/kentat');
    }
  } else {
    list(, $kentanTiedot, $vaylienTiedot) = $kentat;
  }
}

