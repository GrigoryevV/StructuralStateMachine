<html>
<!-- <script language = 'javascript'>  setInterval("document.location.href='http://127.0.0.1/fsmxS1.php'", 3000);</script>    -->
<script language = 'javascript'>  setInterval("document.location.href='http://192.168.1.163/fsmxS1.php'", 5000);</script>
<head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"></head>
<table><tr><td><img width="100%" src=fsmxSPP.png></td><td valign="top">

<?php
$self=$_SERVER['PHP_SELF'];
$addr=$_SERVER['SERVER_ADDR'];
$db = new SQLite3('fsmxS1.db');

header('Content-Type: text/html; charset=windows-1251');
session_start();
if (!empty($_SESSION['role'])){
	echo '<p><a href=newActiveStatesS1.php>Сброс в начальное состояние = 20!</a>'; 
	echo '<p><a href='.$self.'?Logout=1>Выход роли из системы</a></p>';
}
if(isset($_GET['role'])) {
	$role=$_GET['role'];
	$_SESSION['role']=$role;
	header('Location: http://'.$addr.$self);
	}
if(isset($_GET['Logout'])) {
	session_destroy();
	header('Location: http://'.$addr.$self);
	}
//Показываем активные состояния и роли
$result = $db->query('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state');
echo '<h4><p>Список ролей c активными состояниями (задачами):</h4>';
$i=0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p>Роль '.$r.' имеет активное  состояние (задачу) '.$as.'.';$i++;} 
if ($i==0) echo '<p>В системе нет активных состояний (задач).';   
echo '<h4><p>Предполагается, что  ролям отправлены уведомления (почта, СМС, браузерные извещения) о том, что у них есть задачи.</h4>';
if (!empty($_SESSION['role'])) echo '<h2><p>Вы вошли как роль '.$_SESSION['role'].'.</h2>';
if (empty($_SESSION['role']))
	echo '<p>Для входа роли в систему введите целое число - номер роли из списка:<form action='.$self.' method=get> <input type=text name=role size=1><input type=submit Value=Вход></form>';
else	{
	$role=$_SESSION['role'];
	
    	$as = $db->querySingle('select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and   r.role='.$role);
	if (empty($as)){
		echo '<p><h3>Роль '.$role.' не имеет активного состояния (задачи) и не может открыть веб-страницу для выполнения действий.';
        	echo '<p>Выйдите и зайдите как роль у которой есть активное сосояние (задача): смотри список выше.</h3>'; 
	} 

        else {
		//echo '<p><h2>-----------------------------------------------------------------------------</h2></p>';
		echo '<p><h2>Это веб-страница для состояния (задачи) '.$as.'.</h2></p>';
        	echo '<p><h3>Здесь располагаются дата-гриды, диаграммы Ганта, таблицы и т. п.</h3>';
        	echo '<p><h3>После выполнения задачи роль выбирает команду:</h3>';
        	//Из состояния команда идёт в одно следующее состояние (задача)
		$sql='select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c=1';
        	$result = $db->query($sql);
        	//Перебираем команды исходящие из состояния
        	while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//Формируем ссылку для перехода в другое состояние (задача)
            	$nextState = $db->querySingle('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	echo '<p><a href='.$self.'?command='.$cmd.'&as='.$as.'><h3>Команда = '.$cmd.':</h3> </a>';
            	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            	//Показываем, какое будет после выполнения команды следующее состояние (задача) и связанная с ним роль
            	echo ' Следующее состояние (задача) = '.$nextState;
		if (!empty($nextRole))  echo ' для роли '.$nextRole.'.';
		}
        	//Из состояния команда идёт в несколько следующих состояний
        	$result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        	//Перебираем команды исходящие из состояния
       	 while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//Формируем ссылку для прехода в другИЕ состояниЯ
            	echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'><h3>Команда = '.$cmd.':</h3> </a>';
            	//Показываем, какИЕ будУТ после выполнения команды следующИЕ состояниЯ и связаннЫЕ с нимИ ролИ
            	$result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                	$nextState = $row1['nextState'];
                	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            		echo ' Следущее состояние (задача) = '.$nextState;
			if (!empty($nextRole))  echo ' для роли '.$nextRole.'.</h2>';}
        	}
	}
} 

//Отрабатываем команду перехода в следующее состояние (задача)
if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //Удаляем состояние (задача) из активных состояний
    $db->query('delete from activeStates where activeState='.$as);
    //Добавляем следующее состояние (задача) в активные состояния
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    $db->query('insert into activeStates(activeState) values ('.$nextState.')');
    //Проверяем, что следующее состояние (задача) - это точка сборки у которой все входы активны
    $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
    if(isset($gate)) {
        //Удаляем точку сборки из активных состояний
        $db->query('delete from activeStates where activeState='.$gate);
        //Активируем все состояния, выходящие из точки сборки
        $result = $db->query('select nextState from fsmx where state='.$gate);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
            $db->query('insert into activeStates(activeState) values ('.$row['nextState'].')');}
    header('Location: http://'.$addr.'/'.$self); }

//Отрабатываем команду перехода в следующИЕ состояниЯ
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //Удаляем состояние (задача) из активных состояний
    $db->query('delete from activeStates where activeState='.$as);
    //Перебираем следующие состояния
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query('insert into activeStates(activeState) values ('.$nextState.')');
        //Проверяем, что следующее состояние (задача) - это точка сборки у которой все входы активны
        $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
        if(isset($gate)) {
            //Удаляем точку сборки из активных состояний
            $db->query('delete from activeStates where activeState='.$gate);
            //Активируем все состояния, выходящие из точку сборки
            $result1 = $db->query('select nextState from fsmx where state='.$gate);
            while ($row1 = $result->fetchArray(SQLITE3_ASSOC))
                $db->query('insert into activeStates(activeState) values ('.$row1['nextState'].')');}}
    header('Location: http://'.$addr.'/'.$self);   }  ?> 
</td></tr></table></html>