<?php
Atomik::disableLayout();

if (!isset($_GET['pelaaja'])) {
  atomik::end();
}

$pelaaja = Atomik_Db::find('pelaajat', array('id' => $_GET['pelaaja']));

if ($pelaaja === false || !isset($_GET['graafi'])) {
  Atomik::end();
}

$imgLeveys = 920;

Atomik::needed('gdgraph');
switch ($_GET['graafi']) {
  case 1://tasoitus
    $tasoitukset = array();
    $ajankohdat = array();
    foreach (Atomik_Db::findAll('tasoitukset', array('pelaaja' => $_GET['pelaaja']), 'lahtien') as $tasoitus) {
      $tasoitukset[] = $tasoitus['tasoitus'];
      $ajankohdat[] = date("j.n.Y", $tasoitus['lahtien']);
    }
    $gdg = new GDGraph($imgLeveys, 300, 'Tasoitus');
    $arr = Array(
      $pelaaja['nimi'] => $tasoitukset,
    );
    $colors = Array(
      $pelaaja['nimi'] => Array(180, 0, 0),
    );
    $gdg->line_graph($arr, $colors, $ajankohdat, 'Pvm', 'Tasoitus', false);
    break;
  case 2://kierroksia viikossa
    $arr = array();
    for ($i = date("W")-4; $i <= date("W"); $i++) {
      $q = Atomik_Db::query('select count(1)
      	from kierrokset, kierroksen_pelaajat
      	where kierrokset.id = kierroksen_pelaajat.kierros
      	and kierroksen_pelaajat.pelaaja=?
      	and lahtoaika > ?
      	and lahtoaika < ?', array($pelaaja['id'], strtotime(date('o') . '-W' . $i), strtotime(date('o') . '-W' . ($i+1))))->fetch();
      $arr[" $i "] = array($q[0], 0, 180, 0, 0);
    }
    $gdg = new GDGraph($imgLeveys, 300, "Kierroksia viimeisen 5 viikon aikana");
    $gdg->bar_graph($arr, "Viikko", "Kierroksia", 50);
    break;
  case 3://bogeypisteet kierroksittain
    $pisteetVaylittain = array();
    $ajankohdat = array();
    $q = Atomik_Db::query('select * from kierrokset, kierroksen_pelaajat where kierrokset.id = kierroksen_pelaajat.kierros and kierroksen_pelaajat.pelaaja=? order by kierrokset.lahtoaika', array($pelaaja['id']))->fetchAll();
    Atomik::needed('tyokaluja');
    
    foreach ($q as $kierros) {
      $kentta = Atomik_Db::find('kentat', array('id' => $kierros['kentta']));
      $q2 = Atomik_Db::query('select sum(par) as par from kentan_vaylat where kentta = ?', array($kierros['kentta']))->fetch();
      $kentta['par'] = $q2['par'];
      $pelaajanTasoitus = haeTasoitus($pelaaja['id'], $kierros['lahtoaika']);
      $pelaajanSlope = slope($pelaajanTasoitus, $kentta['crslope' . substr($pelaaja['sukupuoli'], 0, 1) . substr($kierros['tii'], 0, 1)], $kentta['par']);
      $pisteet = 0;
      $q2 = Atomik_Db::query('select * from pelatut_vaylat, kentan_vaylat, kierrokset
      	where pelatut_vaylat.vayla=kentan_vaylat.numero
      	and pelatut_vaylat.kierros=kierrokset.id
      	and kierrokset.kentta=kentan_vaylat.kentta
      	and kierrokset.id=?
      	and pelatut_vaylat.pelaaja=?', array($kierros['id'], $pelaaja['id']));
      foreach ($q2 as $pelattuVayla) {
        $pisteet += bogeyPisteet($pelaajanSlope, $pelattuVayla['hcp'], $pelattuVayla['par'], $pelattuVayla['tulos']);
      }
      $pisteetVaylittain[] = $pisteet;
      $ajankohdat[] = date("j.n.Y", $kierros['lahtoaika']);
    }
    $gdg = new GDGraph($imgLeveys, 300, utf8_decode('Bogeypisteitä kierroksittain'));
    $arr = Array(
      $pelaaja['nimi'] => $pisteetVaylittain,
    );
    $colors = Array(
      $pelaaja['nimi'] => Array(180, 0, 0),
    );
    $gdg->line_graph($arr, $colors, $ajankohdat, 'Pvm', 'Pisteet', false);
    break;
  case 4://putit kierroksittain
    $putitVaylittain = array();
    $ajankohdat = array();
    $q = Atomik_Db::query('select * from kierrokset, kierroksen_pelaajat where kierrokset.id = kierroksen_pelaajat.kierros and kierroksen_pelaajat.pelaaja=? order by kierrokset.lahtoaika', array($pelaaja['id']))->fetchAll();
  
    foreach ($q as $kierros) {
      $kentta = Atomik_Db::find('kentat', array('id' => $kierros['kentta']));
      $putit = 0;
      $q2 = Atomik_Db::query('select * from pelatut_vaylat
        	where kierros=?
        	and pelaaja=?', array($kierros['id'], $pelaaja['id']));
      foreach ($q2 as $pelattuVayla) {
        $putit += $pelattuVayla['putit'];
      }
      $putitVaylittain[] = $putit;
      $ajankohdat[] = date("j.n.Y", $kierros['lahtoaika']);
    }
    $gdg = new GDGraph($imgLeveys, 300, utf8_decode('Putit kierroksittain'));
    $arr = Array(
    $pelaaja['nimi'] => $putitVaylittain,
    );
    $colors = Array(
    $pelaaja['nimi'] => Array(180, 0, 0),
    );
    $gdg->line_graph($arr, $colors, $ajankohdat, 'Pvm', 'Putit', false);
    break;
}

