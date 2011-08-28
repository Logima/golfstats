<?php

if (isset($_GET['kentta']) && Atomik_Db::count('kentat', array('id' => $_GET['kentta'])) == 1) {
  if ($id = Atomik_Db::insert('kierrokset', array('kentta' => $_GET['kentta'], 'lahtoaika' => time()))) {
    Atomik::redirect('kierrokset/muokkaa?kierros=' . $id);
  } else {
    Atomik::flash("Virhe lisättäessä kierrosta", "error");
  }
}

$kentat = Atomik_Db::findAll('kentat');
