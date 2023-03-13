<pre><?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");
$sql="select time(zeit), count(*) from t_qso group by time(zeit)";
$result = $mysqli->query($sql);	
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
$min[$row[0]]=$row[1];
}

foreach(array('CW','PH','RY') as $txmode) {
 $sql="select time(zeit), count(*) from t_qso where txmode='$txmode' group by time(zeit)";
 $result = $mysqli->query($sql);	
 while ($row = $result->fetch_array(MYSQLI_NUM)) {	
  $min[$row[0]].=";".$row[1];
 }
}
foreach($min as $m=>$d) {
 $m=substr($m,0,5);	
 print "$m;$d\n";	
}	
?>
  </pre>
</body>
</html>