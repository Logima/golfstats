<?php
if (isset($_GET['kentta']) && Atomik_Db::count('kentat', array('id' => $_GET['kentta'])) == 1) {
  $kentta = Atomik_Db::find('kentat', array('id' => $_GET['kentta']));
  $vaylaInfo = Atomik_Db::findAll('kentan_vaylat', array('kentta' => $_GET['kentta']));
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
      if ($id = Atomik_Db::insert('kierrokset', $kentat[1])) {
        foreach ($kentat[2] as $pelaajaId => $value) {
          Atomik_Db::insert('kierroksen_pelaajat', array('kierros' => $id, 'pelaaja' => $pelaajaId, 'tii' => $value['tii']));
          foreach ($value['vaylat'] as $vaylanNumero => $tiedot) {
            if (!isset($tiedot['greeniosuma'])) $tiedot['greeniosuma'] = 0;
            else $tiedot['greeniosuma'] = 1;
            if (!isset($tiedot['bunkkerissa'])) $tiedot['bunkkeri'] = 0;
            else $tiedot['bunkkeri'] = $tiedot['bunkkerista'];
            Atomik_Db::insert('pelatut_vaylat', array('kierros' => $id, 'pelaaja' => $pelaajaId, 'vayla' => $vaylanNumero, 'tulos' => $tiedot['lyonnit'], 'aloituslyonti' => $tiedot['avaus'], 'greeniosuma' => $tiedot['greeniosuma'], 'putit' => $tiedot['putit'], 'bunkkeri' => $tiedot['bunkkeri'], 'rankkari' => $tiedot['rankkari']));
          }
        }
        Atomik::redirect('kierrokset/katso?kierros=' . $id);
      } else {
        Atomik::flash("Virhe lisättäessä kierrosta", "error");
        $pelaajienTiedot = $kentat[2];
        $kentat = $kentat[0];
      }
    }
  }
} else {
  $pelaajia = Atomik_Db::count('pelaajat');
  $kentat = Atomik_Db::findAll('kentat');
}