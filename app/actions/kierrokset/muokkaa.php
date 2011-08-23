<?php
if (!isset($_GET['kierros']) || Atomik_Db::count('kierrokset', array('id' => $_GET['kierros'])) != 1) {
  Atomik::flash("Kierrosta ei löydy.", "error");
  Atomik::redirect('/kierrokset');
}
$kierros = Atomik_Db::find('kierrokset', array('id' => $_GET['kierros']));

$kentta = Atomik_Db::find('kentat', array('id' => $kierros['kentta']));
$vaylaInfo = Atomik_Db::findAll('kentan_vaylat', array('kentta' => $kierros['kentta']));
$pelaajat = array();
foreach (Atomik_Db::findAll('pelaajat') as $pelaaja) {
  $pelaajat[$pelaaja['id']] = $pelaaja;
}
if (!empty($_POST)) {
  Atomik::needed('filterit');
  $kentat = tarkistaKierrosSyote($_POST);
  if ($kentat[0] === false) {
    $pelaajienTiedot = $kentat[3];
    $kentat = $kentat[1];
  } else {
    $kentat[1]['kentta'] = $kentta['id'];
    if (Atomik_Db::update('kierrokset', $kentat[1], array('id' => $kierros['id']))) {
      $kannassaOlevatKierroksenPelaajat = array();
      foreach (Atomik_Db::findAll('kierroksen_pelaajat', array('kierros' => $kierros['id'])) as $value) {
        $kannassaOlevatKierroksenPelaajat[$value['pelaaja']] = $value;
      }
      foreach ($kentat[2] as $pelaajaId => $value) {
        if (!isset($kannassaOlevatKierroksenPelaajat[$pelaajaId])) { //ei ole, lisätään
          Atomik_Db::insert('kierroksen_pelaajat', array('kierros' => $kierros['id'], 'pelaaja' => $pelaajaId, 'tii' => $value['tii']));
          foreach ($value['vaylat'] as $vaylanNumero => $tiedot) {
            if (!isset($tiedot['greeniosuma'])) $tiedot['greeniosuma'] = 0;
            else $tiedot['greeniosuma'] = 1;
            if (!isset($tiedot['bunkkerissa'])) $tiedot['bunkkeri'] = 0;
            else $tiedot['bunkkeri'] = $tiedot['bunkkerista'];
            Atomik_Db::insert('pelatut_vaylat', array('kierros' => $kierros['id'], 'pelaaja' => $pelaajaId, 'vayla' => $vaylanNumero, 'tulos' => $tiedot['lyonnit'], 'aloituslyonti' => $tiedot['avaus'], 'greeniosuma' => $tiedot['greeniosuma'], 'putit' => $tiedot['putit'], 'bunkkeri' => $tiedot['bunkkeri'], 'rankkari' => $tiedot['rankkari']));
          }
        } else { //on, päivitetään
          Atomik_Db::update('kierroksen_pelaajat', array('tii' => $value['tii']), array('kierros' => $kierros['id'], 'pelaaja' => $pelaajaId));
          foreach ($value['vaylat'] as $vaylanNumero => $tiedot) {
            if (!isset($tiedot['greeniosuma'])) $tiedot['greeniosuma'] = 0;
            else $tiedot['greeniosuma'] = 1;
            if (!isset($tiedot['bunkkerissa'])) $tiedot['bunkkeri'] = 0;
            else $tiedot['bunkkeri'] = $tiedot['bunkkerista'];
            Atomik_Db::update('pelatut_vaylat', array('tulos' => $tiedot['lyonnit'], 'aloituslyonti' => $tiedot['avaus'], 'greeniosuma' => $tiedot['greeniosuma'], 'putit' => $tiedot['putit'], 'bunkkeri' => $tiedot['bunkkeri'], 'rankkari' => $tiedot['rankkari']), array('kierros' => $kierros['id'], 'pelaaja' => $pelaajaId, 'vayla' => $vaylanNumero));
          }
          unset($kannassaOlevatKierroksenPelaajat[$pelaajaId]);
        }
      }
      foreach ($kannassaOlevatKierroksenPelaajat as $value) { //poistetaan poistetut
        Atomik_Db::delete('pelatut_vaylat', array('kierros' => $kierros['id'], 'pelaaja' => $value['pelaaja']));
        Atomik_Db::delete('kierroksen_pelaajat', array('kierros' => $kierros['id'], 'pelaaja' => $value['pelaaja']));
      }
      Atomik::redirect('kierrokset/katso?kierros=' . $kierros['id']);
    } else {
      Atomik::flash("Virhe muokattaessa kierrosta", "error");
      $pelaajienTiedot = $kentat[2];
      $kentat = $kentat[0];
    }
  }
} else {
  $kierros['lahtoaika'] = date("j.n.Y G:i", $kierros['lahtoaika']);
  $kentat = $kierros;
  $pelaajienTiedot = array();
  foreach (Atomik_Db::findAll('kierroksen_pelaajat', array('kierros' => $kierros['id'])) as $pelaaja) {
    $pelaajienTiedot[$pelaaja['pelaaja']] = array();
    $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_tii'] = $pelaaja['tii'];
    foreach (Atomik_Db::findAll('pelatut_vaylat', array('kierros' => $kierros['id'], 'pelaaja' => $pelaaja['pelaaja'])) as $pelattuVayla) {
      $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_lyonnit'] = $pelattuVayla['tulos'];
      $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_avaus'] = $pelattuVayla['aloituslyonti'];
      if ($pelattuVayla['greeniosuma'] == 1) $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_greeniosuma'] = 'on';
      $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_putit'] = $pelattuVayla['putit'];
      if ($pelattuVayla['bunkkeri'] > 0) {
        $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_bunkkerissa'] = 'on';
      }
      $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_bunkkerista'] = $pelattuVayla['bunkkeri'];
      $kentat['pelaaja_' . $pelaaja['pelaaja'] . '_vayla_' . $pelattuVayla['vayla'] . '_rankkari'] = $pelattuVayla['rankkari'];
    }
  }
}