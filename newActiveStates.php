<?php
$db = new SQLite3('fsmx.db');
$db->query('delete from activeStates');
$db->query('insert into activeStates(activeState) values (20)'); ?>