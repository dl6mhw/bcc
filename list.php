<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");
head();

print '
<main role="main" class="container">
<div class="container">
   <div class="row">
     <div class="col-sm-8">
        <h3>Ergebnisse (Final)</h3>
     </div>
     <!-- /.col-sm-8 -->
   </div>
   <!-- /.row -->

   <div class="row">
     <div class="col-sm-8">
	  <pre>';


#print "Ergebnisse werden jede Minute neu berechnet<br>"
#print "Es laufen zur Zeit noch einige Korrekturen<br>"; 




print "Spät engereichte Logs sind mit * gekennzeichnet";

foreach (array('LOW','HIGH','QRP') as $power) {
	foreach (array('BCC','nonBCC') as $bcc) {
		if ($bcc=='BCC') $xbcc=1; else $xbcc=0;
		$sql="select callsign, f_score, f_qso, f_punkte, f_mult, dupe d, call_error c, xchg_error x, nil, r_score, r_qso, r_punkte, r_mult, round(100*f_score/r_score,1)-100 red, id  from t_entry where power='$power' and bcc=$xbcc 
		and id in (select max(id) from t_entry group by callsign) order by f_score desc";
		$result = $mysqli->query($sql);	
		print "<h3>$power $bcc</h3>";
		print "           Endergebnis                         Vor Abzügen\n";  
		print "Call       Score  QSO  Pkt  Mul  D  F  X  N    Score  QSO  Pkt  Mul    Red  UBN\n";  
		print "-------------------------------------------------------------------------------\n";
		while ($row = $result->fetch_array(MYSQLI_NUM)) {	
     		if ($row[14]>305) $late='*'; else $late=''; 
			#print_r($row);
			$url="<a href=\"ubn.php?call=$row[0]&party=2023_1\">UBN</a>";
			printf("%-10s %5d  %3d  %3d   %2d %2d %2d %2d %2d   %5d   %3d  %3d   %2d %5s%%  %5s\n","$row[0]$late",$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$url);
		}
		print "-------------------------------------------------------------------------------\n";
	}
}
?>
  </pre>
  </div>
  <div class="col-sm-4"><pre>
<?php

print "<h3>Statistik</h3>\n";
$logs=sql2val("select count(distinct callsign) from t_entry where callsign <>''");
print "Logs     \t\t$logs\t100 %\n";

$qsos=sql2val("select count(*) from t_qso where call1 in (select callsign from t_entry)");
print "QSOs     \t\t$qsos\t100 %\n";

$sql="select binary status, count(*) from t_qso where call1 in (select callsign from t_entry) group  by binary status";
$result = $mysqli->query($sql);	
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
  $proz=round(100*$row[1]/$qsos,2);
  $t='?';
  if ($row[0]=='+') $t='geprüft       ';
  if ($row[0]=='-') $t='ungeprüft     ';
  if ($row[0]=='X') $t='T-Shirt       ';
  if ($row[0]=='N') $t='Nicht im Log  ';
  if ($row[0]=='C') $t='Call-Fehler   ';
  if ($row[0]=='D') $t='Dupe    ';
  if ($row[0]=='x' or $row[0]=='c') continue;

  print "$t\t$row[0]\t$row[1]\t$proz %\n";
  
}
?>  
</pre>
Geprüft wird:
<p>
Direkter Match des QSOs (Band, Mode, Zeit<5 Min) 
	--> ok
	--> XChg falsch --> X, x</p>
<p>	
Rest - wenn Log vorhanden
	--> ähnliches Call --> Hörfehler bei Partner C,c
	--> kein ähnliches Call --> Hörfehler im eigenen Log C,c
</p>
<p>	
Dupes - ein gleichartiges gültiges QSO wird zu Dupe D
</p>   
  
  </div>

</div>
</div>
</main>
</body>
</html>