<pre><?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");
$call1='DL1MGB';
if (isset($_GET['call'])) $call1=strip_tags($_GET['call']);
if (isset($_GET['party'])) $party=strip_tags($_GET['party']);
/*if ($party!='2022_1') {
	print "Die UBNs der März-Party werden Ende September wieder sichtbar gemacht";
	exit;
}
*/
print "BCC Party Herbst 2022

UBN steht für Unique, Bad Call, Not in Log - Bericht. 
Ob die Bezeichnung so passt ... 
... aber es ist ein griffiger und gar nicht falscher Begriff.

"; 
print "<h1>$call1</h1>";
print "<h3>Ergebnis</h3>";

$sql="select * from t_entry where callsign='$call1' and id = (select max(id) from t_entry where callsign='$call1')"; 
#print "$sql\n";
$result = $mysqli->query($sql);	
if ($row = $result->fetch_array(MYSQLI_NUM)) {	
  #print_r($row);
  $q=$row[6];
  $p=$row[7];
  $m=$row[8]; 
  $s=$row[9]; 
  $rs=$s;
  print "RohErgebnis   : $q QSOs\t$p QSO-Punkte \t$m Multis \t$s Gesamtpunkte\n\n";
  $q=$row[10];
  $p=$row[11];
  $m=$row[12]; 
  $s=$row[13]; 
  print "Final-Ergebnis: $q QSOs\t$p QSO-Punkte \t$m Multis \t$s Gesamtpunkte\n\n";
  $delta=round(100*($rs-$s)/$rs,1);
  print "Abzüge: $delta %";
}

print "<h3>Multiplikatoren</h3>";
$sql="select txmode, nr2, count(*) from t_qso where call1='$call1' and status<>'o' and punkte>0 and nr2<>'nix' group by txmode, nr2 order by txmode, nr2 desc";
$result = $mysqli->query($sql);	
$txmode='';
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
  $mu["$row[0]:$row[1]"]=$row[2];
}
foreach (array('XS','S','M','L','XL','2XL','3XL','4XL','5XL') as $t) {
  print "\t$t";	
}	

foreach (array('CW','PH','RY') as $txmode) {
  print "\n$txmode";	
  foreach (array('XS','S','M','L','XL','2XL','3XL','4XL','5XL') as $t) {
	 if (array_key_exists("$txmode:$t",$mu)) print "\tx"; else print "\t."; 
  }	  
}	

#print_r($mu);

print "<h3>QSOs</h3>";

$sql="select * from t_qso where call1='$call1'"; 
$result = $mysqli->query($sql);	
$l=0;
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
if ($l%20==0) print "\nQSO-Nr\tZeit               \tMode\tCall\tT-Shirt\tStatus\tPunkte\tBemerkung\n";
 
$l++;
	#print_r($row);
$status=$row[9];
if ($status=='-') {
	$sstatus='unchecked';	
    $anz=sql2val("select count(*) from t_entry where callsign='$row[5]'");
	if ($anz==0) $sstatus="unchecked kein Log von $row[5]";	
    $anz=sql2val("select count(*) from t_qso where call2='$row[5]'");
	$sstatus.=", $anz mal in anderen Logs gefunden";	
}
else if ($status=='+') $sstatus='checked';	
else if ($status=='N') $sstatus='Nicht im Log';	
else if ($status=='D') $sstatus='Dupe';	
else if ($status=='C') $sstatus='Call Hörfehler';	
else if ($status=='X') $sstatus='T-Shirt Fehler';	
else if ($status=='c') $sstatus='Call Hörfehler bei Partner';	
else if ($status=='x') $sstatus='T-Shirt Fehler bei ';	
$ls=$l;
if (strlen($ls)<3) $ls="0$ls";
if (strlen($ls)<3) $ls="0$ls";
print "#$ls\t$row[1]\t$row[3]\t$row[5]\t$row[7]\t$row[9]\t$row[10]\t$sstatus $row[12]\n";
}

?>
  </pre>
</body>
</html>