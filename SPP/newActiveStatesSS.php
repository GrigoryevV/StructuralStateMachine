<?php
$sessionID=$_GET['sessionID'];
$db = new SQLite3('fsmxS.db');
$ip= $_SERVER['REMOTE_ADDR'];
$db->query("delete from activeStates where sessionID='".$sessionID."'");
$db->query("insert into activeStates(activeState,sessionID,ip) values (20,'".$sessionID."','".$ip."')");
$addr=$_SERVER['SERVER_ADDR'];
header('Location: http://'.$addr.'/fsmxSS.php');
?>
