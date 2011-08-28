<?php
$kierrokset = Atomik_Db::query('select kierrokset.*, kentat.nimi as kentannimi from kierrokset, kentat where kierrokset.kentta = kentat.id order by kierrokset.lahtoaika desc');
