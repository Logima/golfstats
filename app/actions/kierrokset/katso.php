<?php

Atomik::needed('tyokaluja');

if (!isset($_GET['kierros']) || Atomik_Db::count('kierrokset', array('id' => $_GET['kierros'])) != 1) {
  Atomik::flash("Kierrosta ei löydy.", "error");
  Atomik::redirect('/kierrokset');
}
$kierroksenTiedot = Atomik_Db::find('kierrokset', array('id' => $_GET['kierros']));

$kentta = Atomik_Db::query('select kentat.*, sum(kentan_vaylat.par) as par, count(kentan_vaylat.par) as vaylia from kentat, kentan_vaylat where kentat.id=kentan_vaylat.kentta and id=?', array($kierroksenTiedot['kentta']))->fetch();

$pelaajienTiedot = array();
foreach (Atomik_Db::query('select * from pelaajat, kierroksen_pelaajat where pelaajat.id=kierroksen_pelaajat.pelaaja and kierroksen_pelaajat.kierros=?', array($kierroksenTiedot['id'])) as $pelaaja) {
  $pelaajaId = $pelaaja['pelaaja'];
  $pelaaja['tasoitus'] = haeTasoitus($pelaajaId, $kierroksenTiedot['lahtoaika']);
  $pelaaja['slope'] = slope($pelaaja['tasoitus'], $kentta['crslope' . substr($pelaaja['sukupuoli'], 0, 1) . substr($pelaaja['tii'], 0, 1)], $kentta['par']);
  $q = Atomik_Db::query('select * from pelatut_vaylat, kentan_vaylat, kierrokset
          where pelatut_vaylat.vayla=kentan_vaylat.numero
          and pelatut_vaylat.kierros=kierrokset.id
          and kierrokset.kentta=kentan_vaylat.kentta
          and kierrokset.id=?
          and pelatut_vaylat.pelaaja=?', array($kierroksenTiedot['id'], $pelaajaId));
  $pelaaja['vaylat'] = arvoIndeksiksi($q, 'vayla', true);
  foreach ($pelaaja['vaylat'] as $vaylanNumero => $vayla) {
    $pelaaja['vaylat'][$vaylanNumero]['pisteet'] = bogeyPisteet($pelaaja['slope'], $vayla['hcp'], $vayla['par'], $vayla['lyonnit']);
  }
  if ($kentta['vaylia'] == 18) {
    $pelaaja['total_out'] = yhteensaValilta(1, 9, $pelaaja['vaylat']);
    $pelaaja['total_in'] = yhteensaValilta(10, 18, $pelaaja['vaylat']);
  }
  $pelaaja['total'] = yhteensaValilta(1, $kentta['vaylia'], $pelaaja['vaylat']);
  $pelaajienTiedot[$pelaajaId] = $pelaaja;
}
$pelaajienTiedot = kierrosPelaajatKannasta($pelaajienTiedot);


$saat = array('Aurinkoinen', 'Pilvipouta', 'Sadekuuroja', 'Jatkuva sade');
$bunkkerit = array('', 'Hyvin', 'Kohtalaisesti', 'Huonosti');
