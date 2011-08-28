<?php
$pelaajat = array();
foreach (Atomik_Db::findAll('pelaajat') as $pelaaja) {
  $pelaajat[$pelaaja['id']] = $pelaaja;
}