<html><table><tr><td><img  width="85%" src=fsmx.png></td><td valign="top">
<p><a href=newActiveStates.php>Reset to initial state!</a>
<h2><p>Active states:</h2>
<?php
$self=$_SERVER['PHP_SELF'];
$addr=$_SERVER['SERVER_ADDR'];
$db = new SQLite3('fsmx.db');
//Показываем ссылки для перехода в активные состояния
$result = $db->query('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state');
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p><a href='.$self.'?role='.$r.'&as='.$as.'>State='.$as.' for role='.$r.'</a>';}
echo '<p>Select to go!</p>';
//Показываем выбранное  активное состояние
if(isset($_GET['role'])) {
    $role=$_GET['role']; $as=$_GET['as'];
    //Определяем активное состяние
    $asTrue = $db->querySingle('select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and   r.role='.$role);
    if ($asTrue==$as){
        echo '<p><h2>----------------------------------------------------------</h2></p>';
        echo '<p><h2>This is the web page for state='.$as.' and role='.$role.'.</h2></p>';
        echo '<p><h3>There will be many web elements for input and editing: data grids, charts, etc.</h3>';
        echo '<p><h3>Commands for transition to other states:</h3>';
        //Из состояния команда идёт в одно следующее состояние
        $result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c=1');
        //Перебираем команды исходящие из состояния
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $cmd=$row['command'];
            //Формируем ссылку для перехода в другое состояние
            $nextState = $db->querySingle('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            echo '<p><a href='.$self.'?command='.$cmd.'&as='.$as.'>Command = '.$cmd.'. </a>';
            $nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            //Показываем, какое будет после выполнения команды следующее состояние и связанная с ним роль
            echo ' Next state = '.$nextState;
	    if (!empty($nextRole))  echo ' for role = '.$nextRole.'.';}
        //Из состояния команда идёт в несколько следующих состояний
        $result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        //Перебираем команды исходящие из состояния
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $cmd=$row['command'];
            //Формируем ссылку для прехода в другИЕ состояниЯ
            echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'>Command = '.$cmd.'. </a>';
            //Показываем, какИЕ будУТ после выполнения команды следующИЕ состояниЯ и связаннЫЕ с нимИ ролИ
            $result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                $nextState = $row1['nextState'];
                $nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            	echo ' Next state = '.$nextState;
		if (!empty($nextRole))  echo ' for role = '.$nextRole.'.';}
        }
    }
    else {//Состояние неактивно или привязано к другой роли
        echo '<p><h2>This is the web page for state='.$as.' which is inactive or not assigned to role '.$role.'.</h2></p>';
        echo '<p><h3>There will be many read only web elements : data grids, charts, etc.</h3>';
        echo '<p><h3>There are no commands for transition to other states.</h3>'; }
}
//Отрабатываем команду перехода в следующее состояние
if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //Удаляем состояние из активных состояний
    $db->query('delete from activeStates where activeState='.$as);
    //Добавляем следующее состояние в активные состояния
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    $db->query('insert into activeStates(activeState) values ('.$nextState.')');
    //Проверяем, что следующее состояние - это точка сборки у которой все входы активны
    $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
    if(isset($gate)) {
        //Удаляем точку сборки из активных состояний
        $db->query('delete from activeStates where activeState='.$gate);
        //Активируем все состояния, выходящие из точки сборки
        $result = $db->query('select nextState from fsmx where state='.$gate);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
            $db->query('insert into activeStates(activeState) values ('.$row['nextState'].')');}
    header('Location: http://'.$addr.'/'.$self); }
    //header('Location: '.'/'.$self); }

//Отрабатываем команду перехода в следующИЕ состояниЯ
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //Удаляем состояние из активных состояний
    $db->query('delete from activeStates where activeState='.$as);
    //Перебираем следующие состояния
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query('insert into activeStates(activeState) values ('.$nextState.')');
        //Проверяем, что следующее состояние - это точка сборки у которой все входы активны
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