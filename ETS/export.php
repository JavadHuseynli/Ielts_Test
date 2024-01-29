<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "English";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);


function fiterData(&$str){
    $str = preg_replace("/\t/","\\t",$str);
    $str = preg_replace("/\r?\n/","\\n",$str);
       if(strstr($str,'"')) $str = '"' . str_replace('"','""',$str).'"';

}
$fileName = "members-data_" . date('Y-m-d') . ".xls";
$fields =  array('ID', 'Full Name', 'Qrup', 'Score','Listening score','Reading score','Total score');
$excelData = implode("\t",array_values($fields))."\n";
$qrup =  $_POST['qrup_no'];
$sql = "SELECT * FROM student where student_group LIKE '%$qrup%'";
$result = $conn->query($sql);
if($result->num_rows > 0){
    while($rows = $result->fetch_assoc()){
        $totalscore= $rows['student_score']+$rows['Student_read_score']+$rows['Student_listen_score'];
        $linedata = array($rows['student_id'],$rows['student_name'],$rows['student_group'],$rows['student_score'],$rows['Student_read_score'],$rows['Student_listen_score'], $totalscore);
        array_walk($linedata,'fiterData');
        $excelData .= implode("\t", array_values($linedata))."\n"; 
    }
}else{
    $excelData .= 'No data Found ... '."\n";
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$fileName\"");
echo $excelData;
exit;
?>