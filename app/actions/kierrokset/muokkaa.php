<?php
if (!isset($_GET['kierros']) || Atomik_Db::count('kierrokset', array('id' => $_GET['kierros'])) != 1) {
  Atomik::flash("Kierrosta ei löydy.", "error");
  Atomik::redirect('/kierrokset');
}
Atomik::needed('filterit');
Atomik::needed('tyokaluja');

$kierroksenTiedotKannassa = Atomik_Db::find('kierrokset', array('id' => $_GET['kierros']));
$kentta = Atomik_Db::find('kentat', array('id' => $kierroksenTiedotKannassa['kentta']));
$vaylaInfo = Atomik_Db::findAll('kentan_vaylat', array('kentta' => $kierroksenTiedotKannassa['kentta']));
$pelaajat = arvoIndeksiksi(Atomik_Db::findAll('pelaajat'), 'id');

if (!empty($_POST)) {
  $kentat = tarkistaKierrosSyote($_POST);
  if ($kentat[0] === false) {
    $kierroksenTiedot = $kentat[1];
    $pelaajienTiedot = $kentat[2];
  } else {
    $kierroksenTiedot = $kentat[0];
    $kierroksenTiedot['id'] = $kierroksenTiedotKannassa['id'];
    $pelaajienTiedot = kierrosPelaajatKantaan($kentat[1]);
    $kierroksenTiedot['kentta'] = $kentta['id'];
    if (Atomik_Db::update('kierrokset', $kierroksenTiedot, array('id' => $kierroksenTiedot['id']))) {
      $kannassaOlevatKierroksenPelaajat = arvoIndeksiksi(Atomik_Db::findAll('kierroksen_pelaajat', array('kierros' => $kierroksenTiedot['id'])), 'pelaaja');
      foreach ($pelaajienTiedot as $pelaajaId => $value) {
        if (!isset($kannassaOlevatKierroksenPelaajat[$pelaajaId])) { //ei ole, lisätään
          Atomik_Db::insert('kierroksen_pelaajat', array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId, 'tii' => $value['tii']));
          foreach ($value['vaylat'] as $vaylanNumero => $tiedot) {
            Atomik_Db::insert('pelatut_vaylat', array_merge(array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId, 'vayla' => $vaylanNumero), $tiedot));
          }
        } else { //on, päivitetään
          Atomik_Db::update('kierroksen_pelaajat', array('tii' => $value['tii']), array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId));
          foreach ($value['vaylat'] as $vaylanNumero => $tiedot) {
            Atomik_Db::update('pelatut_vaylat', $tiedot, array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId, 'vayla' => $vaylanNumero));
          }
          unset($kannassaOlevatKierroksenPelaajat[$pelaajaId]);
        }
      }
      foreach ($kannassaOlevatKierroksenPelaajat as $pelaajaId => $value) { //poistetaan poistetut
        Atomik_Db::delete('pelatut_vaylat', array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId));
        Atomik_Db::delete('kierroksen_pelaajat', array('kierros' => $kierroksenTiedot['id'], 'pelaaja' => $pelaajaId));
      }
      Atomik::redirect('kierrokset/katso?kierros=' . $kierroksenTiedot['id']);
    } else {
      Atomik::flash("Virhe muokattaessa kierrosta", "error");
    }
  }
} else {
  $kierroksenTiedot = $kierroksenTiedotKannassa;
  $kierroksenTiedot['lahtoaika'] = date("j.n.Y G:i", $kierroksenTiedot['lahtoaika']);
  $pelaajienTiedot = array();
  foreach (Atomik_Db::findAll('kierroksen_pelaajat', array('kierros' => $kierroksenTiedot['id'])) as $pelaaja) {
    $pelaajaId = $pelaaja['pelaaja'];
    $pelaajienTiedot[$pelaajaId] = array();
    $pelaajienTiedot[$pelaajaId]['tii'] = $pelaaja['tii'];
    $pelaajienTiedot[$pelaajaId]['vaylat'] = arvoIndeksiksi(Atomik_Db::query('select vayla, lyonnit, avaus, greeniosuma, putit, bunkkeri, rankkari from pelatut_vaylat where kierros=? and pelaaja=?', array($kierroksenTiedot['id'], $pelaaja['pelaaja'])), 'vayla', true);
  }
  $pelaajienTiedot = kierrosPelaajatKannasta($pelaajienTiedot);
}
