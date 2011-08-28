<?php

Atomik::needed('tyokaluja');

if (!isset($_GET['kierros']) || Atomik_Db::count('kierrokset', array('id' => $_GET['kierros'])) != 1) {
  Atomik::flash("Kierrosta ei löydy.", "error");
  Atomik::redirect('/kierrokset');
}
$kierros = Atomik_Db::find('kierrokset', array('id' => $_GET['kierros']));

$kentta = Atomik_Db::find('kentat', array('id' => $kierros['kentta']));
$q = Atomik_Db::query('select sum(par) as in_par from kentan_vaylat where numero > 9 and kentta = ?', array($kentta['id']))->fetch();
$kentta['in_par'] = $q['in_par'];
$q = Atomik_Db::query('select sum(par) as out_par from kentan_vaylat where numero < 10 and kentta = ?', array($kentta['id']))->fetch();
$kentta['out_par'] = $q['out_par'];
$kentta['par'] = $kentta['out_par'] + $kentta['in_par']; 
$vaylaInfo = array();
foreach (Atomik_Db::findAll('kentan_vaylat', array('kentta' => $kierros['kentta'])) as $vayla) {
  $vaylaInfo[$vayla['numero']] = $vayla;
}
$pelaajat = array();
foreach (Atomik_Db::findAll('pelaajat') as $pelaaja) {
  $pelaajat[$pelaaja['id']] = $pelaaja;
}

$kentat = $kierros;
$kentat['lahtoaika'] = date("j.n.Y G:i", $kierros['lahtoaika']);
$pelaajienTiedot = array();
foreach (Atomik_Db::findAll('kierroksen_pelaajat', array('kierros' => $kierros['id'])) as $pelaaja) {
  $pelaajaId = $pelaaja['pelaaja'];
  $pelaajienTiedot[$pelaajaId] = array();
  $pelaajienTiedot[$pelaajaId]['tasoitus'] = haeTasoitus($pelaajaId, $kierros['lahtoaika']);
  $pelaajienTiedot[$pelaajaId]['slope'] = slope($pelaajienTiedot[$pelaajaId]['tasoitus'], $kentta['crslope' . substr($pelaajat[$pelaajaId]['sukupuoli'], 0, 1) . substr($pelaaja['tii'], 0, 1)], $kentta['par']);
  
  $pelaajienTiedot[$pelaajaId]['out_lyonnit'] = 0;
  $pelaajienTiedot[$pelaajaId]['out_pisteet'] = 0;
  $pelaajienTiedot[$pelaajaId]['out_greeniosumat'] = 0;
  $pelaajienTiedot[$pelaajaId]['out_putit'] = 0;
  $pelaajienTiedot[$pelaajaId]['out_bunkkerit'] = 0;
  $pelaajienTiedot[$pelaajaId]['out_rankkarit'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_lyonnit'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_pisteet'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_greeniosumat'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_putit'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_bunkkerit'] = 0;
  $pelaajienTiedot[$pelaajaId]['in_rankkarit'] = 0;
  $kentat['pelaaja_' . $pelaajaId . '_tii'] = $pelaaja['tii'];
  foreach (Atomik_Db::findAll('pelatut_vaylat', array('kierros' => $kierros['id'], 'pelaaja' => $pelaajaId)) as $pelattuVayla) {
    $liite = ($pelattuVayla['vayla'] > 9)?'in_':'out_';
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_lyonnit'] = $pelattuVayla['lyonnit'];
    $pelaajienTiedot[$pelaajaId][$liite . 'lyonnit'] += $pelattuVayla['lyonnit'];
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_avaus'] = $pelattuVayla['avaus'];
    if ($pelattuVayla['greeniosuma'] == 1) {
      $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_greeniosuma'] = 'on';
      $pelaajienTiedot[$pelaajaId][$liite . 'greeniosumat']++;
    }
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_putit'] = $pelattuVayla['putit'];
    $pelaajienTiedot[$pelaajaId][$liite . 'putit'] += $pelattuVayla['putit'];
    if ($pelattuVayla['bunkkeri'] > 0) {
      $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_bunkkerissa'] = 'on';
      $pelaajienTiedot[$pelaajaId][$liite . 'bunkkerit']++;
    }
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_bunkkerista'] = $pelattuVayla['bunkkeri'];
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_rankkari'] = $pelattuVayla['rankkari'];
    $pelaajienTiedot[$pelaajaId][$liite . 'rankkarit'] += $pelattuVayla['rankkari'];
    $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_pisteet'] = bogeyPisteet($pelaajienTiedot[$pelaajaId]['slope'], $vaylaInfo[$pelattuVayla['vayla']]['hcp'], $vaylaInfo[$pelattuVayla['vayla']]['par'], $pelattuVayla['lyonnit']);
    $pelaajienTiedot[$pelaajaId][$liite . 'pisteet'] += $kentat['pelaaja_' . $pelaajaId . '_vayla_' . $pelattuVayla['vayla'] . '_pisteet'];
    
  }
  $pelaajienTiedot[$pelaajaId]['lyonnit'] = $pelaajienTiedot[$pelaajaId]['out_lyonnit'] + $pelaajienTiedot[$pelaajaId]['in_lyonnit'];
  $pelaajienTiedot[$pelaajaId]['pisteet'] = $pelaajienTiedot[$pelaajaId]['out_pisteet'] + $pelaajienTiedot[$pelaajaId]['in_pisteet'];
  $pelaajienTiedot[$pelaajaId]['greeniosumat'] = $pelaajienTiedot[$pelaajaId]['out_greeniosumat'] + $pelaajienTiedot[$pelaajaId]['in_greeniosumat'];
  $pelaajienTiedot[$pelaajaId]['putit'] = $pelaajienTiedot[$pelaajaId]['out_putit'] + $pelaajienTiedot[$pelaajaId]['in_putit'];
  $pelaajienTiedot[$pelaajaId]['bunkkerit'] = $pelaajienTiedot[$pelaajaId]['out_bunkkerit'] + $pelaajienTiedot[$pelaajaId]['in_bunkkerit'];
  $pelaajienTiedot[$pelaajaId]['rankkarit'] = $pelaajienTiedot[$pelaajaId]['out_rankkarit'] + $pelaajienTiedot[$pelaajaId]['in_rankkarit'];
}

$saat = array('Aurinkoinen', 'Pilvipouta', 'Sadekuuroja', 'Jatkuva sade');
$bunkkerit = array('', 'Hyvin', 'Kohtalaisesti', 'Huonosti');
