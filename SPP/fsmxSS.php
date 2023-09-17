<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"></head>
<table><tr><td width=45%><img  width=100% src=fsmxSPP.png></td><td valign=top width="55%">
<?php
$self=$_SERVER['PHP_SELF'];
$addr=$_SERVER['SERVER_ADDR'];
$db = new SQLite3('fsmxS.db');
$ip= $_SERVER['REMOTE_ADDR'];
header('Content-Type: text/html; charset=windows-1251');
session_start();
$sessionID=session_id();
if(isset($_GET['F'])) {
    $db->query("delete fromm activeStates where sessionID='".$sessionID."'");
    echo '<p><h2>Достигли фиального состояния. Осуществлён сброс в начальное состояние.</h2></p>';
}
if (!empty($_SESSION['role'])){
    echo '<p><a href='.$self.'?Logout=1><font color=fuchsia>Выход роли из системы</font></a></p>';
    echo '<p><a href=newActiveStatesSS.php?sessionID='.$sessionID.'>Сброс в начальное состояние = 20!</a>';
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
$as1 = $db->querySingle("select activeState  from activeStates where sessionID='".$sessionID."'");
if (empty($as1)) {
    echo '<h2><p><font color=maroon> Начинаем с начала!</font></h2>';
    $db->query("insert into activeStates(activeState,sessionID,ip) values (20,'".$sessionID."','".$ip."')");
}
$sql= "select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state and activeStates.sessionID='".$sessionID."'";
$result = $db->query($sql);
echo '<h3><p><font color=lime>Список </font>ролей c активными состояниями (задачими):</h3>';
$i=0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p>Роль <font color=red>'.$r.'</font> имеет активное  состояние (задачу) <font color=red>'.$as.'</font>.';$i++;
}
if ($i==0) echo '<p>В системе нет активных состояний (задач). Выберите Сброс в начальное состояние.';
echo '<h4><p><font color=blue>Предполагается, что  ролям отправляются уведомления (почта, СМС, браузерные извещения) о том, что у них есть задачи.</font></h4>';
if (!empty($_SESSION['role'])) echo '<h2><p><font color=maroon>Вы вошли как роль </font><font color=red>'.$_SESSION['role'].'.</font></h2>';
if (empty($_SESSION['role']))
    echo '<p><h3>Для входа роли в систему введите целое число - номер роли из <font color=lime>списка</font>:</h3><form action='.$self.' method=get> <input type=text name=role size=1><input type=submit Value=Вход></form>';
else	{
    $role=$_SESSION['role'];
    $as = $db->querySingle("select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and r.role=".$role." and a.sessionID='".$sessionID."'");
    if (empty($as)){
        echo '<p><h3>Роль <font color=red>'.$role.'</font> не имеет активного состояния (задачи) и может открывать веб-страницы состояний (задач) только для просмотра.';
        echo '<p>Осуществите <font color=fuchsia>выход роли из системы </font>и зайдите как роль у которой есть активное состояние (задача): смотри <font color=lime>список</font> выше.</h3>';
    }
    else {
        echo '<p><h2><font color=fuchsia>Это иммитация веб-страницы для состояния (задачи)</font> <font color=red>'.$as.'</font>.</h2></p>';
        echo '<p><h3>Здесь располагаются дата-гриды, диаграммы Ганта, таблицы и т. п.</h3>';
        echo '<p><h3>После выполнения всех необходимых действий по выполнению задачи роль выбирает команду:</h3>';
        //Из состояния команда идёт в одно следующее состояние (задачу)
        $sql='select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c=1';
        $result = $db->query($sql);
        //Перебираем команды исходящие из состояния
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $cmd=$row['command'];
            //Формируем ссылку для перехода в другое состояние (задачу)
            $nextState = $db->querySingle('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            echo '<p><a href='.$self.'?command='.$cmd.'&as='.$as.'><h3>Команда = '.$cmd.':</h3> </a>';
            $nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            //Показываем, какое будет после выполнения команды следующее состояние (задача) и связанная с ним роль
            echo ' Следующее состояние (задача) = <font color=red>'.$nextState.'</font>';
            if (!empty($nextRole))  echo ' для роли <font color=red>'.$nextRole.'</font>.';
        }
        //Из состояния команда идёт в несколько следующих состояний
        $result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        //Перебираем команды исходящие из состояния
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $cmd=$row['command'];
            //Формируем ссылку для прехода в другИЕ состояниЯ
            echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'><h3>Команда = <font color=red>'.$cmd.'</font>:</h3> </a>';
            //Показываем, какИЕ будУТ после выполнения команды следующИЕ состояниЯ и связаннЫЕ с нимИ ролИ
            $result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                $nextState = $row1['nextState'];
                $nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
                echo ' Следущее состояние (задача) = <font color=red>'.$nextState.'</font>';
                if (!empty($nextRole))  echo ' для роли <font color=red>'.$nextRole.'</font>.</h2>';
            }
        }
    }
}
//Отрабатываем команду перехода в следующее состояние (задача)

if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //Удаляем состояние (задача) из активных состояний
    $sql="delete from activeStates where activeState=".$as." and sessionID='".$sessionID."'";
    //echo $sql;
    $db->query($sql);
    //Добавляем следующее состояние (задача) в активные состояния
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    if ($nextState != 0)  {
        $db->query("insert into activeStates(activeState,sessionID,ip) values (".$nextState.",'".$sessionID."','".$ip."')");
        //Проверяем, что следующее состояние (задача) - это точка сборки у которой все входы активны
        $gate = $db->querySingle("select gate from gatecnt g, currentgatecntS c where g.cnt=c.cnt and g.gate=".$nextState." and c.sessionID='".$sessionID."'");
        if(isset($gate)) {
            //Удаляем точку сборки из активных состояний
            $db->query("delete from activeStates where activeState=".$gate." and sessionID='".$sessionID."'");
            //Активируем все состояния, выходящие из точки сборки
            $result = $db->query('select nextState from fsmx where state='.$gate);
            while ($row = $result->fetchArray(SQLITE3_ASSOC))
                $db->query("insert into activeStates(activeState,sessionID,ip) values (".$row['nextState'].",'".$sessionID."','".$ip."')");
        }
    }
    header('Location: http://'.$addr.'/'.$self);
}
//Отрабатываем команду перехода в следующИЕ состояниЯ
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //Удаляем состояние (задача) из активных состояний
    $db->query("delete from activeStates where activeState=".$as." and sessionID='".$sessionID."'");
    //Перебираем следующие состояния
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query("insert into activeStates(activeState,sessionID,ip) values (".$nextState.",'".$sessionID."','".$ip."')");
        //Проверяем, что следующее состояние (задача) - это точка сборки у которой все входы активны
        $gate = $db->querySingle('select gate from gatecnt g, currentgatecntS c where g.cnt=c.cnt and g.gate='.$nextState);
        if(isset($gate)) {
            //Удаляем точку сборки из активных состояний
            $db->query("delete from activeStates where activeState=".$gate." and sessionID='".$sessionID."'");
            //Активируем все состояния, выходящие из точку сборки
            $result1 = $db->query('select nextState from fsmx where state='.$gate);
            while ($row1 = $result->fetchArray(SQLITE3_ASSOC))
                $db->query("insert into activeStates(activeState,sessionID,ip) values (".$row1['nextState'].",'".$sessionID."','".$ip."')");
        }
    }
    header('Location: http://'.$addr.'/'.$self);
}  ?>
        </td></tr></table></html>