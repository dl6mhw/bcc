<pre>
<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");

$mysqli->query("update t_qso set status='-', etext=''");
#exit;

#einige Reperaturen Herbst 2021

#$mysqli->query("update t_qso set nr1='XL' where call1 = 'DK2ZO'");
$mysqli->query("update t_qso set nr1='5XL' where call1 = 'DM5DM'");
#$mysqli->query("delete from t_entry where callsign ='DO7UDO'");

$sql="select distinct callsign from t_entry where callsign <>'' order by callsign";
$result = $mysqli->query($sql);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
  print "\nCheck: $row[0]\n";
  $call1=$row[0];
  print "Step 0 = Punkte nach neuer BCC-Liste neu setzen\n";
  #zunächst Punkte mit isBCC erneut füllen
  $sql2="select q.qid, call2 from t_qso q where call1='$call1'";
  #print "$sql2\n";
  $result2 = $mysqli->query($sql2);
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
    $call2=$row2[1];
	$qid=$row2[0];
	$punkte=punkte($call2);
	$upd="update t_qso set punkte=$punkte, r_punkte=$punkte where qid=$qid";
	#print "$call2 $upd\n";
    $mysqli->query($upd);
  }   

}


print "Step Pre 1: Dupes vor Check und Raw\n";
$sql="select distinct callsign, max(id) from t_entry  where callsign <>'' group by callsign";
$result = $mysqli->query($sql);
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
  $call1=$row[0];
  print "Step pre 1 = Dupes vor Check $call1\n";
  $entry_id=$row[1];
  #Dupes sind gültige QSOs mit Punkten die später stattfinden als andere gültige gleichartige QSOs
  $sql2="select q2.qid, time(q1.zeit) from t_qso q1, t_qso q2 where q1.call1='$call1' and q2.call1='$call1' and q1.call2=q2.call2 and q1.qid!=q2.qid and q1.band = q2.band and q1.txmode=q2.txmode and q2.zeit>q1.zeit";
  #print "DUPE:$sql2\n";
  #if ($call1=='DL6RAI') exit; 
  $result2 = $mysqli->query($sql2); 
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
	 #print_r($row2);
	 $qid2=$row2[0];
	 $mysqli->query("update t_qso set status='D',punkte=0 where qid=$qid2"); 
  }
  
  print "Raw berechnen\n";  
  #jetzt Auswertung
  $r_qso=sql2val("select count(*) from t_qso where call1='$call1' and status<>'D'");
  $r_punkte=sql2val("select sum(r_punkte) from t_qso where call1='$call1' and status<>'D' and r_punkte>0");
  if ($r_punkte=='') $r_punkte=0;
  $r_multi=sql2val("select count(*) from (select distinct nr2,txmode from t_qso where call1='$call1' and nr2<>'nix') as x");
  $r_score=$r_punkte*$r_multi;
  $upd="update t_entry set r_qso=$r_qso, r_punkte=$r_punkte, r_mult=$r_multi, r_score=$r_score where id=$entry_id";
  print "Raw $call1: $upd\n";
  $stmt = $mysqli->prepare($upd);
  $stmt->execute();
  #if ($call1=='DL5XJ') exit;
}

#if ($call1=='DJ9MH') exit;
#Dupes wieder zurück setzen
$mysqli->query("update t_qso set status='-', etext=''");

$sql="select distinct callsign from t_entry where callsign <>'' order by callsign";
$result = $mysqli->query($sql);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
  print "\nCheck: $row[0]\n";
  $call1=$row[0];
/*  print "Step 0 = Punkte nach neuer BCC-Liste neu setzen\n";
  #zunächst Punkte mit isBCC erneut füllen
  $sql2="select q.qid, call2 from t_qso q where call1='$call1'";
  #print "$sql2\n";
  $result2 = $mysqli->query($sql2);
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
    $call2=$row2[1];
	$qid=$row2[0];
	$punkte=punkte($call2);
	$upd="update t_qso set punkte=$punkte where qid=$qid";
	#print "$call2 $upd\n";
    $mysqli->query($upd);
  }   
*/
 
  print "Step 1 = Direkte Treffer\n";
  
  $sql2="select q1.qid, q1.nr2, q2.nr1, q1.call2, q2.qid from t_qso q1, t_qso q2 where q1.status='-' and q2.status='-' and q1.call1='$call1' and q2.call2='$call1' and q1.call2=q2.call1 and q1.txmode=q2.txmode and  abs(timestampdiff(minute,q1.zeit,q2.zeit))<6";
  print "$sql2\n";
  $result2 = $mysqli->query($sql2);
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
	 #print_r($row2); 
	 if ($row2[1]!=$row2[2]) {
	   print "Hörfehler $row2[3] $row2[1]<>$row2[2]\n";
	   $etext="$row2[3] $row2[1]<>$row2[2]";
       $mysqli->query("update t_qso set status='X', etext='$etext', punkte=0 where qid=$row2[0]");
       $mysqli->query("update t_qso set status='x', etext='Partner: $etext' where qid=$row2[4]");
     } else {
       #print "update t_qso set status='c' where qid=$row2[0]";
       print "update t_qso set status='+' where qid=$row2[0]\n";
       $mysqli->query("update t_qso set status='+' where qid=$row2[0]");
       $mysqli->query("update t_qso set status='+' where qid=$row2[4]");
	 }		 
  }	
}
  #alle ungeprüften QSOs wenn Log vorhanden ... 
  # ... dort dann mit levenstein prüfen ob in Gegenlog Callhörfehler.... 	
  
  #sinnvolle Statuswerte
  # - nicht geprüft
  # + erfolgreicher check
  # x,X - exchange falsch durch Prüfung
  # c,C - Call falsch durch Prüfung mit Levensthein < 2
  # n,N - not in Log für vorhandenes Log

  
$sql="select distinct callsign from t_entry where callsign <>'' order by callsign";
$result = $mysqli->query($sql);

while ($row = $result->fetch_array(MYSQLI_NUM)) {
  $call1=$row[0];
  print "Step 2 = Call Hörfehler:\n";
  
  $sql2="select qid, call2, zeit, band, txmode from t_qso where call1='$call1' and status='-' and call2 in (select callsign from t_entry)";
  $result2 = $mysqli->query($sql2);
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
	 #print_r($row2); 
	 $qid=$row2[0];
	 $call2=$row2[1];
	 $zeit=$row2[2];
	 $band=$row2[3];
	 $txmode=$row2[4];
	 $sql3="select call2, qid from t_qso where call1='$call2' and band='$band' and txmode='$txmode' and abs(timestampdiff(minute,zeit,'$zeit'))<6 and status<>'+'";
	 #if ($call1=='DJ8VH') print "Nux:$sql3\n";
     
	 $result3 = $mysqli->query($sql3);
     $merker='-';		
     while ($row3 = $result3->fetch_array(MYSQLI_NUM)) {
		$badcall=$row3[0];
		$qid2=$row3[1];
        $delta=levenshtein($call1,$badcall);
		print "Ähnlichkeit $call1 - $badcall = $delta\n";
	    if ($delta <3 and $delta >0) {
		  #hier wird der Hörfehler von DF8VO vermerkt		
		  $etext="$call1<>$badcall";
		  $mysqli->query("update t_qso set status='c', etext='$call1<>$badcall' where qid=$qid");
		  #hier wird der Hörfehler bei DF8V= bestraft
		  $mysqli->query("update t_qso set status='C', punkte=0, etext='$call1<>$badcall' where qid=$qid2");
		  $merker='x';
		  break;	
		}
		# wenn Log existiert aber QSO auch nicht mit ähnlichkeit dann eigener Callfehler	
	 }
     if ($merker=='-') {
      print "xxxx Merker MINUS\n"; 		 
	  $mysqli->query("update t_qso set status=h, punkte=0 where qid=$qid");
	 }	   
  }	

}


$sql="select distinct callsign from t_entry";
$result = $mysqli->query($sql);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
  $call1=$row[0];
  print "Step vor3 = unchecked für vorhandene Logs Call Hörfehler\n";
  $sql2="select qid, call2 from t_qso where call1='$call1' and status='-' and call2 in (select callsign from t_entry)"; 
  print "$sql2\n";
  $result2 = $mysqli->query($sql2);
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
    $qid=$row2[0];
    $call2=$row2[1];
	$upd="update t_qso set status='N', punkte=0, etext=', $call2 Log vorhanden' where qid=$qid";
  print "$upd\n";
	$mysqli->query($upd);
  }  
  
  print "Step 3 = Dupes $call1\n";
    $call1=$row[0];
  #Dupes sind gültige QSOs mit Punkten die später stattfinden als andere gültige gleichartige QSOs
  $sql2=" select q2.qid, time(q1.zeit) from t_qso q1, t_qso q2 where q1.call1='$call1' and q2.call1='$call1' and q1.call2=q2.call2 and q1.qid!=q2.qid and q1.band = q2.band and q1.txmode=q2.txmode and q2.zeit>q1.zeit and q1.punkte>0 and q2.punkte>0";
  #print "DUPE:$sql2\n";
  #if ($call1=='DL6RAI') exit; 
  $result2 = $mysqli->query($sql2); 
  while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
	 #print_r($row2);
	 $qid2=$row2[0];
	 $mysqli->query("update t_qso set status='D', etext='vorheriges QSO $row2[1]', punkte=0 where qid=$qid2"); 
	 #print "DUPE: update t_qso set status='D', punkte=0 where qid=$qid2\n"; 
  }
}
$sql="select distinct callsign, max(id) from t_entry group by callsign";
$result = $mysqli->query($sql);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
  $call1=$row[0];
  $entry_id=$row[1];
  print "Step 4 = Ergebnis berechnen\n";
  #jetzt Auswertung
  $r_qso=sql2val("select count(*) from t_qso where call1='$call1' and status<>'D'");
  $f_qso=sql2val("select count(*) from t_qso where call1='$call1' and punkte>0");
  $f_punkte=sql2val("select sum(punkte) from t_qso where call1='$call1'  and punkte>0");
  #print "FPunkte=:$f_punkte:";
  if ($f_punkte=='') $f_punkte=0;
  $r_punkte=sql2val("select sum(r_punkte) from t_qso where call1='$call1' and status<>'D' and r_punkte>0");
  if ($r_punkte=='') $r_punkte=0;
  $r_multi=sql2val("select count(*) from (select distinct nr2,txmode from t_qso where call1='$call1'  and nr2<>'nix') as x");
  $f_multi=sql2val("select count(*) from (select distinct nr2,txmode from t_qso where call1='$call1'  and punkte>0 and nr2<>'nix') as x");
  $f_score=$f_punkte*$f_multi;
  $r_score=$r_punkte*$r_multi;
  $dupe_anz=sql2val("select count(*) from t_qso where call1='$call1' and binary status = 'D'");
  $call_err=sql2val("select count(*) from t_qso where call1='$call1' and binary status = 'C'");
  $xchg_err=sql2val("select count(*) from t_qso where call1='$call1' and binary status = 'X'");
  $nil=sql2val("select count(*) from t_qso where call1='$call1' and binary status = 'N'");
  print "RES: $call1\t$r_qso\t$f_qso\t $f_multi*$f_punkte = $f_score \t$r_multi*$r_punkte = $r_score\n";
  $upd="update t_entry set f_qso=$f_qso, f_mult=$f_multi, f_punkte=$f_punkte, f_score=$f_score, dupe=$dupe_anz, call_error=$call_err , xchg_error=$xchg_err, nil=$nil where id=$entry_id";
  print "$upd\n";
	$stmt = $mysqli->prepare($upd);
	$stmt->execute();

};

?>