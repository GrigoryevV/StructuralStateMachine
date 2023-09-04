<html><table><tr><td><img  width="85%" src=fsmx.png></td><td valign="top">
<p><a href=newActiveStatesS.php>Reset to initial state!</a>

<?php
$self=$_SERVER['PHP_SELF'];
$addr=$_SERVER['SERVER_ADDR'];
$db = new SQLite3('fsmx.db');
session_start();
echo '<p><a href='.$self.'?Logout=1>Logout.</a></p>';
if(isset($_GET['role'])) {
	$role=$_GET['role'];
	$_SESSION['role']=$role;
	header('Location: http://'.$addr.$self);
	}
if(isset($_GET['Logout'])) {
	session_destroy();
	header('Location: http://'.$addr.$self);
	}
//���������� �������� ��������� � ����
$result = $db->query('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state');
echo '<h2><p>Active states:</h2>';
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p>State='.$as.' for role='.$r;}      
if (!empty($_SESSION['role'])) echo '<h2><p>Logged in role = '.$_SESSION['role'].'.</h2>';
if (empty($_SESSION['role']))
	echo '<p>Login as role from "Active states" list: <form action='.$self.' method=get> <input type=text" name=role><input type=submit Value=LogIn></form>';
else	{
	$role=$_SESSION['role'];
	
    	$as = $db->querySingle('select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and   r.role='.$role);
	if (empty($as)){
		echo '<p><h3>Role '.$role.' has no active states and cannot open any web page.';
        	echo '<p>Logout and login with a role that has active states: see list "Active states".</h3>'; 
	} 

        else {
		echo '<p><h2>-----------------------------------------------------------------------------</h2></p>';
		echo '<p><h2>This is the web page for state='.$as.' and role='.$role.'.</h2></p>';
        	echo '<p><h3>There will be many web elements for input and editing: data grids, charts, etc.</h3>';
        	echo '<p><h3>Commands for transition to other states:</h3>';
        	//�� ��������� ������� ��� � ���� ��������� ���������
		$sql='select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c=1';
        	$result = $db->query($sql);
        	//���������� ������� ��������� �� ���������
        	while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//��������� ������ ��� �������� � ������ ���������
            	$nextState = $db->querySingle('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	echo '<p><a href='.$self.'?command='.$cmd.'&as='.$as.'>Command = '.$cmd.'. </a>';
            	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            	//����������, ����� ����� ����� ���������� ������� ��������� ��������� � ��������� � ��� ����
            	echo ' Next state = '.$nextState;
		if (!empty($nextRole))  echo ' for role = '.$nextRole.'.';
		}
        	//�� ��������� ������� ��� � ��������� ��������� ���������
        	$result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        	//���������� ������� ��������� �� ���������
       	 while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//��������� ������ ��� ������� � ������ ���������
            	echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'>Command = '.$cmd.'. </a>';
            	//����������, ����� ����� ����� ���������� ������� ��������� ��������� � ��������� � ���� ����
            	$result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                	$nextState = $row1['nextState'];
                	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            		echo ' Next state = '.$nextState;
			if (!empty($nextRole))  echo ' for role = '.$nextRole.'.';}
        	}
	}
} 

//������������ ������� �������� � ��������� ���������
if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //������� ��������� �� �������� ���������
    $db->query('delete from activeStates where activeState='.$as);
    //��������� ��������� ��������� � �������� ���������
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    $db->query('insert into activeStates(activeState) values ('.$nextState.')');
    //���������, ��� ��������� ��������� - ��� ����� ������ � ������� ��� ����� �������
    $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
    if(isset($gate)) {
        //������� ����� ������ �� �������� ���������
        $db->query('delete from activeStates where activeState='.$gate);
        //���������� ��� ���������, ��������� �� ����� ������
        $result = $db->query('select nextState from fsmx where state='.$gate);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
            $db->query('insert into activeStates(activeState) values ('.$row['nextState'].')');}
    header('Location: http://'.$addr.'/'.$self); }
    //header('Location: '.'/'.$self); }

//������������ ������� �������� � ��������� ���������
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //������� ��������� �� �������� ���������
    $db->query('delete from activeStates where activeState='.$as);
    //���������� ��������� ���������
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query('insert into activeStates(activeState) values ('.$nextState.')');
        //���������, ��� ��������� ��������� - ��� ����� ������ � ������� ��� ����� �������
        $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
        if(isset($gate)) {
            //������� ����� ������ �� �������� ���������
            $db->query('delete from activeStates where activeState='.$gate);
            //���������� ��� ���������, ��������� �� ����� ������
            $result1 = $db->query('select nextState from fsmx where state='.$gate);
            while ($row1 = $result->fetchArray(SQLITE3_ASSOC))
                $db->query('insert into activeStates(activeState) values ('.$row1['nextState'].')');}}
    header('Location: http://'.$addr.'/'.$self);   }  ?> 
</td></tr></table></html>