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
    echo '<p><h2>�������� ��������� ���������. ���������� ����� � ��������� ���������.</h2></p>';
}
if (!empty($_SESSION['role'])){
    echo '<p><a href='.$self.'?Logout=1><font color=fuchsia>����� ���� �� �������</font></a></p>';
    echo '<p><a href=newActiveStatesSS.php?sessionID='.$sessionID.'>����� � ��������� ��������� = 20!</a>';
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
$as1 = $db->querySingle("select activeState  from activeStates where sessionID='".$sessionID."'");
if (empty($as1)) {
    echo '<h2><p><font color=maroon> �������� � ������!</font></h2>';
    $db->query("insert into activeStates(activeState,sessionID,ip) values (20,'".$sessionID."','".$ip."')");
}
$sql= "select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state and activeStates.sessionID='".$sessionID."'";
$result = $db->query($sql);
echo '<h3><p><font color=lime>������ </font>����� c ��������� ����������� (��������):</h3>';
$i=0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)){
    $r=$row['role']; $as=$row['activeState'];
    echo '<p>���� <font color=red>'.$r.'</font> ����� ��������  ��������� (������) <font color=red>'.$as.'</font>.';$i++;
}
if ($i==0) echo '<p>� ������� ��� �������� ��������� (�����). �������� ����� � ��������� ���������.';
echo '<h4><p><font color=blue>��������������, ���  ����� ������������ ����������� (�����, ���, ���������� ���������) � ���, ��� � ��� ���� ������.</font></h4>';
if (!empty($_SESSION['role'])) echo '<h2><p><font color=maroon>�� ����� ��� ���� </font><font color=red>'.$_SESSION['role'].'.</font></h2>';
if (empty($_SESSION['role']))
    echo '<p><h3>��� ����� ���� � ������� ������� ����� ����� - ����� ���� �� <font color=lime>������</font>:</h3><form action='.$self.' method=get> <input type=text name=role size=1><input type=submit Value=����></form>';
else	{
    $role=$_SESSION['role'];
    $as = $db->querySingle("select activeState  from activeStates a, roleStates r  where a.activeState=  r.state and r.role=".$role." and a.sessionID='".$sessionID."'");
    if (empty($as)){
        echo '<p><h3>���� <font color=red>'.$role.'</font> �� ����� ��������� ��������� (������) � ����� ��������� ���-�������� ��������� (�����) ������ ��� ���������.';
        echo '<p>����������� <font color=fuchsia>����� ���� �� ������� </font>� ������� ��� ���� � ������� ���� �������� ��������� (������): ������ <font color=lime>������</font> ����.</h3>';
    }
    else {
        echo '<p><h2><font color=fuchsia>��� ��������� ���-�������� ��� ��������� (������)</font> <font color=red>'.$as.'</font>.</h2></p>';
        echo '<p><h3>����� ������������� ����-�����, ��������� �����, ������� � �. �.</h3>';
        echo '<p><h3>����� ���������� ���� ����������� �������� �� ���������� ������ ���� �������� �������:</h3>';
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
            echo ' ��������� ��������� (������) = <font color=red>'.$nextState.'</font>';
            if (!empty($nextRole))  echo ' ��� ���� <font color=red>'.$nextRole.'</font>.';
        }
        //�� ��������� ������� ��� � ��������� ��������� ���������
        $result = $db->query('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state='.$as.' and role='.$role.' group by command having c>1 order by command');
        //���������� ������� ��������� �� ���������
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){
            $cmd=$row['command'];
            //��������� ������ ��� ������� � ������ ���������
            echo '<p><a href='.$self.'?commandX='.$cmd.'&as='.$as.'><h3>������� = <font color=red>'.$cmd.'</font>:</h3> </a>';
            //����������, ����� ����� ����� ���������� ������� ��������� ��������� � ��������� � ���� ����
            $result1 = $db->query('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state='.$as.' and role='.$role.' and command='.$cmd);
            while ($row1 = $result1->fetchArray(SQLITE3_ASSOC)){
                $nextState = $row1['nextState'];
                $nextRole = $db->querySingle('select role from roleStates where state='.$nextState);
                echo ' �������� ��������� (������) = <font color=red>'.$nextState.'</font>';
                if (!empty($nextRole))  echo ' ��� ���� <font color=red>'.$nextRole.'</font>.</h2>';
            }
        }
    }
}
//������������ ������� �������� � ��������� ��������� (������)

if(isset($_GET['command'])) {
    $as=$_GET['as'];
    //������� ��������� (������) �� �������� ���������
    $sql="delete from activeStates where activeState=".$as." and sessionID='".$sessionID."'";
    //echo $sql;
    $db->query($sql);
    //��������� ��������� ��������� (������) � �������� ���������
    $nextState = $db->querySingle('select nextState from fsmx where state='.$as.' and command ='.$_GET['command']);
    if ($nextState != 0)  {
        $db->query("insert into activeStates(activeState,sessionID,ip) values (".$nextState.",'".$sessionID."','".$ip."')");
        //���������, ��� ��������� ��������� (������) - ��� ����� ������ � ������� ��� ����� �������
        $gate = $db->querySingle("select gate from gatecnt g, currentgatecntS c where g.cnt=c.cnt and g.gate=".$nextState." and c.sessionID='".$sessionID."'");
        if(isset($gate)) {
            //������� ����� ������ �� �������� ���������
            $db->query("delete from activeStates where activeState=".$gate." and sessionID='".$sessionID."'");
            //���������� ��� ���������, ��������� �� ����� ������
            $result = $db->query('select nextState from fsmx where state='.$gate);
            while ($row = $result->fetchArray(SQLITE3_ASSOC))
                $db->query("insert into activeStates(activeState,sessionID,ip) values (".$row['nextState'].",'".$sessionID."','".$ip."')");
        }
    }
    header('Location: http://'.$addr.'/'.$self);
}
//������������ ������� �������� � ��������� ���������
if(isset($_GET['commandX'])) {
    $cmd=$_GET['commandX'];
    $as=$_GET['as'];
    //������� ��������� (������) �� �������� ���������
    $db->query("delete from activeStates where activeState=".$as." and sessionID='".$sessionID."'");
    //���������� ��������� ���������
    $result = $db->query('select nextState from fsmx where state='.$as.' and command ='.$cmd);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)){
        $nextState=$row['nextState'];
        $db->query("insert into activeStates(activeState,sessionID,ip) values (".$nextState.",'".$sessionID."','".$ip."')");
        //���������, ��� ��������� ��������� (������) - ��� ����� ������ � ������� ��� ����� �������
        $gate = $db->querySingle('select gate from gatecnt g, currentgatecntS c where g.cnt=c.cnt and g.gate='.$nextState);
        if(isset($gate)) {
            //������� ����� ������ �� �������� ���������
            $db->query("delete from activeStates where activeState=".$gate." and sessionID='".$sessionID."'");
            //���������� ��� ���������, ��������� �� ����� ������
            $result1 = $db->query('select nextState from fsmx where state='.$gate);
            while ($row1 = $result->fetchArray(SQLITE3_ASSOC))
                $db->query("insert into activeStates(activeState,sessionID,ip) values (".$row1['nextState'].",'".$sessionID."','".$ip."')");
        }
    }
    header('Location: http://'.$addr.'/'.$self);
}  ?>
        </td></tr></table></html>