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
	echo '<p><a href=newActiveStatesS1.php>����� � ��������� ��������� = 20!</a>'; 
	echo '<p><a href='.$self.'?Logout=1>����� ���� �� �������</a></p>';
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
//���������� �������� ��������� � ����
$result = $db->query('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state');
echo '<h4><p>������ ����� c ��������� ����������� (��������):</h4>';
$i=0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p>���� '.$r.' ����� ��������  ��������� (������) '.$as.'.';$i++;} 
if ($i==0) echo '<p>� ������� ��� �������� ��������� (�����).';   
echo '<h4><p>��������������, ���  ����� ���������� ����������� (�����, ���, ���������� ���������) � ���, ��� � ��� ���� ������.</h4>';
if (!empty($_SESSION['role'])) echo '<h2><p>�� ����� ��� ���� '.$_SESSION['role'].'.</h2>';
if (empty($_SESSION['role']))
	echo '<p>��� ����� ���� � ������� ������� ����� ����� - ����� ���� �� ������:<form action='.$self.' method=get> <input type=text name=role size=1><input type=submit Value=����></form>';
else	{
	$role=$_SESSION['role'];
	
    	$as = $db->querySingle('select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and   r.role='.$role);
	if (empty($as)){
		echo '<p><h3>���� '.$role.' �� ����� ��������� ��������� (������) � �� ����� ������� ���-�������� ��� ���������� ��������.';
        	echo '<p>������� � ������� ��� ���� � ������� ���� �������� �������� (������): ������ ������ ����.</h3>'; 
	} 

        else {
		//echo '<p><h2>-----------------------------------------------------------------------------</h2></p>';
		echo '<p><h2>��� ���-�������� ��� ��������� (������) '.$as.'.</h2></p>';
        	echo '<p><h3>����� ������������� ����-�����, ��������� �����, ������� � �. �.</h3>';
        	echo '<p><h3>����� ���������� ������ ���� �������� �������:</h3>';
        	//�� ��������� ������� ��� � ���� ��������� ��������� (������)
		$sql='select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c=1';
        	$result = $db->query($sql);
        	//���������� ������� ��������� �� ���������
        	while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//��������� ������ ��� �������� � ������ ��������� (������)
            	$nextState = $db->querySingle('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	echo '<p><a href='.$self.'?command='.$cmd.'&as='.$as.'><h3>������� = '.$cmd.':</h3> </a>';
            	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            	//����������, ����� ����� ����� ���������� ������� ��������� ��������� (������) � ��������� � ��� ����
            	echo ' ��������� ��������� (������) = '.$nextState;
		if (!empty($nextRole))  echo ' ��� ���� '.$nextRole.'.';
		}
        	//�� ��������� ������� ��� � ��������� ��������� ���������
        	$result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        	//���������� ������� ��������� �� ���������
       	 while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            	$cmd=$row['command'];
            	//��������� ������ ��� ������� � ������ ���������
            	echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'><h3>������� = '.$cmd.':</h3> </a>';
            	//����������, ����� ����� ����� ���������� ������� ��������� ��������� � ��������� � ���� ����
            	$result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            	while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                	$nextState = $row1['nextState'];
                	$nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
            		echo ' �������� ��������� (������) = '.$nextState;
			if (!empty($nextRole))  echo ' ��� ���� '.$nextRole.'.</h2>';}
        	}
	}
} 

//������������ ������� �������� � ��������� ��������� (������)
if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //������� ��������� (������) �� �������� ���������
    $db->query('delete from activeStates where activeState='.$as);
    //��������� ��������� ��������� (������) � �������� ���������
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    $db->query('insert into activeStates(activeState) values ('.$nextState.')');
    //���������, ��� ��������� ��������� (������) - ��� ����� ������ � ������� ��� ����� �������
    $gate = $db->querySingle('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate='.$nextState);
    if(isset($gate)) {
        //������� ����� ������ �� �������� ���������
        $db->query('delete from activeStates where activeState='.$gate);
        //���������� ��� ���������, ��������� �� ����� ������
        $result = $db->query('select nextState from fsmx where state='.$gate);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
            $db->query('insert into activeStates(activeState) values ('.$row['nextState'].')');}
    header('Location: http://'.$addr.'/'.$self); }

//������������ ������� �������� � ��������� ���������
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //������� ��������� (������) �� �������� ���������
    $db->query('delete from activeStates where activeState='.$as);
    //���������� ��������� ���������
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query('insert into activeStates(activeState) values ('.$nextState.')');
        //���������, ��� ��������� ��������� (������) - ��� ����� ������ � ������� ��� ����� �������
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