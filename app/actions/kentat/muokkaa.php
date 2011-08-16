<?php


if (count($_POST) == 0) {
  $tiedot = Atomik_Db::find('kentat', array('id' => $_GET['kentta']));
  $vaylienTiedotQuery = Atomik_Db::findAll('kentan_vaylat', array('kentta' => $_GET['kentta']));
  $vaylienTiedot = array();
  foreach ($vaylienTiedotQuery->toArray() as $value) {
    $vaylienTiedot["vayla_" . $value['numero'] . "_par"] = $value['par'];
    $vaylienTiedot["vayla_" . $value['numero'] . "_hcp"] = $value['hcp'];
  }
} else {
  Atomik::needed('filterit');
  $kentat = tarkistaKenttaSyote($_POST);
  if ($kentat[0] !== false) {
    if (Atomik_Db::update('kentat', $kentat[0], array('id' => $_GET['kentta']))) {
      $viimeinenVayla = 0;
      foreach ($kentat[2] as $vaylanNumero => $par) {
        if (Atomik_Db::count('kentan_vaylat', array('kentta' => $_GET['kentta'], 'numero' => $vaylanNumero)) == 1) {
          Atomik_Db::update('kentan_vaylat', array('hcp' => $kentat[3][$vaylanNumero], 'par' => $par),  array('kentta' => $_GET['kentta'], 'numero' => $vaylanNumero));
        } else {
          A('db:insert into kentan_vaylat values (?, ?, ?, ?)', array($_GET['kentta'], $vaylanNumero, $kentat[3][$vaylanNumero], $par));
        }
        $viimeinenVayla = $vaylanNumero;
      }
      A('db:delete from kentan_vaylat where kentta = ? and numero > ?', array($_GET['kentta'], $viimeinenVayla));
      Atomik::flash("Kentän " . $kentat[0]['nimi'] . " muokkaus onnistui.");
      Atomik::redirect('/kentat');
    } else {
      Atomik::flash("Virhe muokattaessa kenttää " . $kentat[0]['nimi'] . ".", "error");
      $tiedot = $kentat[0];
      $vaylienTiedot = $kentat[1];
    }
  } else {
    $tiedot = $kentat[1];
    $vaylienTiedot = $kentat[2];
  }
}

