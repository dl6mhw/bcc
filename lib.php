<?php 
$mysqli = new mysqli("localhost", "bcc", "VDs4uGQwjMDmh41K", "bcc");
/* check connection */
if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
    #printf("Connect OK: %s\n", $mysqli->connect_error);



function head() {?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">

    <title>BCC Party</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/starter-template/">

    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="starter-template.css" rel="stylesheet">
  </head>

  <body>
  <!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-primary">
  <a class="navbar-brand" href="index.php">
    <img src="bcc_logo.png" width="60" height="60" class="d-inline-block align-top" alt="" >
    <span  style="color:#FFF;font-size:200%">Bavarian Contest Club - QSO Party Frühling 2023 </span>
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item active">
        <a class="nav-link" href="log_upload.php" style="background-color:#FFF">Log Upload<span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item">.
      </li> 
      <li class="nav-item">
        <a class="nav-link" href="list.php" style="background-color:#FFF">Ergebnisse</a>
      </li>
    </ul>
  </div>
</nav>
<?php }

function deadline() {
  #Küchenzeit .... wird auch beim Upload agezeigt	
  $endzeitS="2023-03-16 21:59:59";
  $endzeit=strtotime($endzeitS);
  $jetzt = time();
  $jetztS = date("d.m.Y - H:i", $jetzt);  
  $endzeitS2 = date("d.m.Y - H:i", $endzeit);  
  #print "Deadline Funktion $jetzt $endzeit $jetztS $endzeitS2" ;
  if ($jetzt>$endzeit) {
  print "<h2>Abgabezeit überschritten ($endzeitS2). Küchenzeit</h2>\n<h3>Bitte per E-Mail an René wenden</h3>\n";
	return false;
  }	
  return true;	
}	

function sql2val($sql) {
	global $mysqli;
	#print $sql;
	$result = $mysqli->query($sql); 
    if ($row = $result->fetch_array(MYSQLI_NUM)) return $row[0];
	return '';
}	

function importFile($call,$log) {
  global $mysqli;

#QSOs erzeugen ... evtl. replace
$qsos=preg_split('/\n/',$log);
$qanz=0;
$psum=0;
$cwmulti=array();
$ssbmulti=array();
$rttymulti=array();
	foreach ($qsos as $q) {
		if (preg_match('/^CALLSIGN:\s+([A-Z0-9\/]+)/',$q,$m)) {
			print "Rufzeichen aus Log: $m[1]\n";
			$callsign=$m[1];
            if ($call!='nocall' and $callsign!=$call) {
			  print "Fehler: Call $call [Formular] stimmt nicht mit $callsign [Log-Datei] überein\n kein Import, Abbruch\n";
			  exit;
			}	
			#alte QSOs auf Status d setzen
			$stmt = $mysqli->prepare("update t_qso set call1=concat('~',call1), punkte=0 where call1 = ?");
			$stmt->bind_param("s", $callsign);
			$stmt->execute();
			if ($stmt->error) printf("Error: %s.\n", $stmt->error);
		 
			$email='no';
			#nur für Massenimport hier t_entry anlegen
			if ($call=='nocall') {
     			$power='LOW';
				$stmt = $mysqli->prepare("insert into t_entry (callsign,email,logfile,power) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("ssss", $callsign, $email,$log,$power);
				$stmt->execute();
				$entry_id=$mysqli->insert_id;
				print "Entry-ID: $entry_id\n";
				if ($stmt->error) printf("Error: %s.\n", $stmt->error);
			} else $callsign=$call;	
		}	

		if (preg_match('/^QSO:/',$q)) {
			#print $q;
			$d=preg_split('/\s+/',$q);
			#print_r($d);
			if ($d[3]!='2023-03-16') {
				print "QSO $qanz - Falsches QSO-Datum $d[3]\n";
				$qanz++;
			    continue;
			}
			$zeit=$d[3]." ".substr($d[4],0,2).':'.substr($d[4],2,2);
			$txmode=$d[2];
			$call1=$d[5];
			#test ob Logcall passt
			/*
			if ($call1!=$callsign) {
				print "ERROR: $callsign ungleich $call1\n";
				exit;	
			}
			*/	
			$call2=$d[8];
			$nr1=$d[7];
			$nr2=$d[10];
			$nr2=formatMulti($nr2);
			$nr1=formatMulti($nr1);

			$qrg=$d[1];
			if ($txmode=='CW' and $nr2<>'nix')
				if (array_key_exists($nr2,$cwmulti)) $cwmulti["$nr2"]++; else $cwmulti["$nr2"]=1; 
			if ($txmode=='PH' and $nr2<>'nix')
				if (array_key_exists($nr2,$ssbmulti)) $ssbmulti["$nr2"]++; else $ssbmulti["$nr2"]=1; 
			if ($txmode=='RY' and $nr2<>'nix')
				if (array_key_exists($nr2,$rttymulti)) $rttymulti["$nr2"]++; else $rttymulti["$nr2"]=1; 
			$punkte=punkte($call2);
			$psum=$psum+$punkte;
			$band=80; if ($qrg>4000) $band=40; ### hier mal ganz einfach
			$stmt = $mysqli->prepare("insert into t_qso (zeit,band,txmode,call1,call2,nr1,nr2,qrg,punkte,r_punkte) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?)");
			$stmt->bind_param("ssssssssii", $zeit,$band,$txmode,$call1,$call2,$nr1,$nr2,$qrg,$punkte,$punkte);
			$stmt->execute();
			#printf("Error: %s.\n", $stmt->error);
			$qanz++;
		}	  
	}	
	print  "$qanz QSOs in die Datenbank eingefügt\n";
	print  "$psum QSO-Punkte\n";
	print  "CW-Multis:".sizeof($cwmulti)." [ ";
	ksort($cwmulti);
	foreach ($cwmulti as $s=>$m) print "$s=$m "; 
	print "]\n";
	print  "SSB-Multis:".sizeof($ssbmulti)." [ ";
	ksort($ssbmulti);
	foreach ($ssbmulti as $s=>$m) print "$s=$m "; 
	print "]\n";
	print  "RTTY-Multis:".sizeof($rttymulti)." [ ";
	ksort($rttymulti);
	foreach ($rttymulti as $s=>$m) print "$s=$m "; 
	print "]\n";
	$multisum=sizeof($cwmulti)+sizeof($ssbmulti)+sizeof($rttymulti);
	print "Multi gesamt:$multisum\n";
	$score=$multisum*$psum;
	print "Ergebnis:$psum * $multisum = $score\n";
	#Ausgabe machen
	$bcc=isBCC($call1);

	$entry_id=sql2val("select max(id) from t_entry where callsign='$callsign'");
	$stmt = $mysqli->prepare("update t_entry set r_qso=$qanz, r_mult=$multisum, r_punkte=$psum, r_score=$score, shirt='$nr1', bcc=$bcc where id=$entry_id");
	$stmt->execute();
}

function formatMulti($m) {
  if ($m=='S' or $m=='XS' or $m=='M'or $m=='L'or $m=='XL'or $m=='2XL'or $m=='3XL' or $m=='4XL' or $m=='5XL') return $m;
  if ($m=='XXL') return "2XL";
  if ($m=='XXXL') return "3XL";
  if ($m=='XXXXL') return "4XL";
  if ($m=='XXXXXL') return "5XL";
  #print $m;
  return "nix";  	
}	
function punkte($call) {
 if ($call=='DA0BCC') return 5;
 if (isBCC($call)) return 2;
 else return 1; 
}	

function isBCC($call) {
	#print "$call ";
	if ($call=='F/DJ4MZ') return true;
	if (strpos($call,'/')>0) return false;
	
#Liste von BCC Seite
$txt='
DA0BCC OMYL
AJ9C Mike
YN2CC Mike
PZ5M Mike
BA4TB Dale
DB1WA Alex
DB2WD Jens
DN1MIA Jens
DB6JG Peter
DB7MA Mario
DB8NI Andreas
DC2KN Robert
DC2VE Frank
DN1VN Frank
DC2YY Markus
DC6RI Andreas
DC8YZ Michael
DD1JN Karsten
DD1MAT Niko
DD2ML Ulli
DD5FZ Toby
DD5KG Gabor
HA5NR Gabor
DF1LK Kevin
DN6LK Kevin
DF1LX Peter
DF2DR Hermann
DF2FM Peter
DF2LH Thomas
DF2MM Swen
DF2PH Pit
AE5MD Pit
DF0WI Pit
DK0WI Pit
DF2RG Gary
DF2TT Gerd
DF3CB Bernd
DF3IAL Anders
SM6CNN Anders
DF3QL Michael
HB9DFD Michael
DF3TJ Christoph
DF3VM Mike
DM5M Mike
KK6DXJ Mike
DF4SA Con
DF4XX Kurt
DF5MA Andreas
DF6RI Dr. Alfred
DF8DX Bodo
HB9EHJ Bodo
KT3Q Bodo
DF8VO Bert
DF9GR Rene
CP6/DF9GR Rene
DF9LJ Jörg
DF9MP Harry
DF9MV Sven
DF9TS Gerd
DF9XV Klaus
DG0ZB Knut
DG1HXJ Stephan
DN2DB Stephan
DG2NMF Markus
DG2NMH Herbert
DG3FK Tom
DR1F Tom
DR1H Tom
DG4NDV Joerg
DG5MEX Michael
DG7RO Torsten
V31TF Torsten
DG8AM Alex
DH0GHU Ulrich
DH1NHI Mirco
DH1TST Tom
DH1TW Toby
DH2WQ Olli
DH5MFD Frieso
DH8BQA Olli
DH8VV Paul
HA1Q Paul
HA8VV Paul
DH8WR Andreas
DJ0DX Claus
OE6CLD Claus
EI7JZ Claus
DJ0IP Rick
NJ0IP Rick
DJ0MDR Michael
DJ0ZY Franta
DD5M Franta
DJ1AT Hartmut
DJ1MM Sergej
DJ1OJ Heijo
EA8OM Heijo
DJ2MX Mario
9A4MX Mario
DP4X Mario
E73DX Mario
N0MX Mario
DJ2VA Michael
DJ3CQ Jo
DJ3NG Siegfried
DJ3NY Klaus
DJ3TF Wolfgang
DJ3WE Rudolf
DR4T Rudolf
DJ4KW Gerd
DJ4MF Daniel
DJ4MX Sven
9A5MX Sven
DJ4MZ Simon
F4VVG Simon
DJ4WT Christian
DQ9L Christian
DF0SC Christian
DJ5AN Jan
PA1TT Jan
DJ5CL Ingo
DJ5CW Fabian
SO5CW Fabian
DJ5FI Hans Joachim
DJ5IW Gerd
DJ5MN Bernhard
DJ5MO Jelmer
DJ5MW Fred
DR0W Fred
DJ5MY Harald
DJ5TT Gerd
DJ6RN Eckhard
DJ6TB Tom
DJ7EO Markus
DJ7HH Hans-Henning
DJ8EW Lothar
DJ8QA Christoph
DJ8QP Gus
DJ9DZ Vasily
DJ9KH Web
DJ9MH Hajo
DJ9RR Heye
DK1AF Ingo
DK1A Ingo
DN1AE Ingo
F/DK1AF Ingo
DK1AX Klaus
DK1FT Eduard
DK1FW Wolf
DK1GO Thomas
DK1IP Wolf
DK1KC Michael
DK1MFI Ingo
DK1MM Stefan
DK1NO Hannes
DK1WU Hans
DK2AT Dieter
DK2CX Markus
DK2GZ Harry
DK2LO Olaf
DK2OY Manfred
DK2PZ Fred
KD2PZ Fred
DK2WU Werner
DK0TL Werner
DK2YL Siggi
DK2ZO Wolfgang
DK2ZZ Karlheinz
DK3GI Roland
DK3HV Hanno
DK3QJ Georg
DK3WE Pit
KU6I Pit
DK3WW Uwe
DK3YD Hans
DN3YD Hans
DK4AA Flo
DK4VW Ulli
DK4WA Andy
DK4YJ Matthias
DK5MB Tom
DK5ON Andy
DK5OS Olaf
DK5PD Heinz Lothar
DK5TA Thomas
DK5TT Jörn
DK5TX Ulf
DK6AH Andreas
DK6CQ Otto
DK6NP Peter
DK6QX Kurt
DK6SP Philipp
DK6WL Helmut
DK7AM Uwe
DK7CH Karl-Heinz
DK7MCX Wolfgang
DK8AF Pit
DM0G Pit
DK8FD Alexander
DK8MM Mark
K1XAQ Mark
DN4MM Mark
DK8MZ Wolfgang
DK8NT Gerd
DK9BM Mike
DK9IP Winfried
DK9OV Roland
DK9TN Christoph
DL1BUG Red
DL1DVE Thomas
DL1GNM Michael
DL1GWS Waldy
DL1HCM Mike
DL1HTY Heiko
DL1IAO Stefan
DM1A Stefan
DL1II Michael
DL1MAJ Alex
HA1BC Alex
DF0BV Alex
DL1MDZ Pit
DL1Z Pit
DL1MGB Chris
KO2WW Chris
DL1MHJ Torsten
DP5P Torsten
DN1MSF Torsten
DL1NEO Markus
DL1NKS Stefan
DL1PSK Stefan
DL1QQ Sandy
N0QQ Sandy
DL1REM Frank
DL1RTL Heiko
DL1TS Thomas
DL1VDL Hardy
DL0DA Hardy
DL2AA Maik
DL2AGB Andy
DR7B Andy
DL2CC Frank
DL2JRM Rene
DL2LAR Richard
DL2LDE Dany
YO2LDE Dany
DL2MIJ Robert
DL2MLU Luise
DL2NBU Peter
DL2NBY Tom
DL2OAP Thomas
DL2OE Mike
DL2PR Peter
DL2QT Heinz
DN2MQT Heinz
DL2RMC Thomas
DL2SKY Stefan
DL2VFR Enrico
DL2YL Melanie
DL2ZA Hans
DL2ZAV Udo
DL3ABL Andrea
DL3BPC Ron
DL3DW Daniel
DL3DXX Dietmar
DL3LAB Wolfgang
DL3LBA Kai
DK3A Kai
DN1BUX Kai
DL3MBG Christian
DL3MXX Olaf
DL3NC Marcus
DL3RY Achim
DL4FN Peter
DL4GBA Wilfried
DL4HG Olaf
DL4LAM Peter
DQ5T Peter
DL4MCF Thomas
DL4MDO Wolf
DL4MM Mathias
P40AA Mathias
DL4NAC Martin
DC4A Martin
DL4NER Werner
DP4N Werner
DL4RCK Walter
DL4R Walter
DL4RDJ Jörg
DL4VK Valentin
DL4YAO Christoph
DL4ZA Willi
DL5CW Andreas
DL5GAC Robert
DL5IC Hans-Jürgen
DL5JS Michael
DL5KUT Holger
DL5LYM Thomas
DL5MBU Karl
DL5MFF Andy
K2AO Andy
DL5MX Mike
DL5NAM Chris
DL5NDX Ulrich
DL5NEN Tom
DL5RCW Lars
DL5RDO Dieter
DL5RMH Martin
DL5RU Rudi
DL5SDK Axel
DL5SE Dan
5P5CW Dan
OK8SE Dan
DL5XAT Holger
DL5XJ Nick
UA0KW Nick
LY1DC Nick
DL5YYM Guenter
DK0SAX Guenter
DL6DCX Chris
DL6DH Henning
DL6EZ Dieter
DL6FBL Ben
DL6IAK Mike
HK1MK Mike
AG4RS Mike
DL6KVA Axel
4K0CW Axel
DL6MFK Robert
DL6MHW Michael
DL6NBC Harry
DL6NCY Stefan
DL6NDW Horst
DP8M Horst
DL6RAI Ben
DL6RBH Josef
DL6RBO Toni
DL6RDR Stefan
DL7AT Andy
DA0T Andy
OZ0TX Andy
DL7AV Thomas
DL7CX Olaf
DL7LIN Andre
DL7ON Fritz
DL7UGN Mike
DL7URH Ragnar
DL8DXL Fred
DL8DYL Irina
DL8JDX Dr. Volker
DL8LAS Andy
DR5X Andy
DL8LR Frank
DK8R Frank
DL8MAS Bernd
DM7W Bernd
DL8OH Dieter
DP6A Dieter
DN1OH Dieter
DL8RB Ruben
DL8RDL Lenz
EI6JY Lenz
DN6JY Lenz
DL8TG Klaus
DM4G Klaus
DL8UAT Andreas
DL8UD Uwe
5P2C Uwe
DL9DRA Ralf
DL9EE Holger
DL9GTB Torsten
DL9MFY Bodo
DL9NCR Reiner
DL9NDS Uwe
DP9N Uwe
DL9NDV Michael
DL9NEI Norbert
DL9UP Patrick
DL9YAJ Bernd
DM2WB Markus
DM5EE Ulrich
DM5JBN Andreas
DM5TI Hartmut
DM5XX Mike
DM6DX Robby
DM6EE Lutz
DK0VLP Lutz
DM7XX Robert
DM8FW Fritz
DM9CM Carsten
DO1NPF Peter
DO2WW Anja
DO2XX Robin
DO4DXA Marc
V31MA Marc
V3A Marc
OZ1MDX Marc
OU4U Marc
9A3DXA Marc
DO4OD Matze
DO6SR Robert
9A5TU Robert
EA3KU Fernando
EC3A Fernando
F5MZN Olivier
F5NGA Francois
HA1AG Zoli
PA1AG Zoli
HB9BGV Martin
HB9BJL Chris
W9BJL Chris
HS0ZNI Chris
HB9DDO Stephan
WS9O Stephan
HB9DQL Juerg
HB9ELV Chris
HB9EE Chris
JK3GAD Kazu
M0CFW Kazu
MJ0CFW Kazu
K3LR Tim
KC1XX Matt
KU1CW Alex
P40C Alex
EU1CW Alex
V31CW Alex
KU7T Andy
LX1ER Joel
LX1MK Raymond
NN7CW Wolf
OE1EMS Braco
E77DX Braco
E7DX Braco
OE1TKW Helmut
N0XW Helmut
OE2GEN Gerald
OE2LCM Günther
OE2VEL Wolfgang
OE5OHO Oliver
OE7AJT Andy
OE9MON Carl
OE2MON Carl
OK1DX Pavel
OK1FCJ Petr
OL8R Petr
OK1IC Tom
OK5MM Vit
OK2WH Vit
OK5M Vit
OL3W Vit
ON4CAS Egbert
N1TOI Egbert
ON6CC Marc
OR3A Marc
ON6NL Anton
PA9AUQ Anton
ON7WM Werner
OT5L Werner
OZ1ADL Jan
OZ7AM Alex
VE2OXA Alex
OU4X Alex
5P7Y Alex
PA0BWL Wil
PA0JED Jan
PA1AW Alex
PA1TX Gerard
PA3EWP Ronald
PA4OES André
EI5VAT André
PA4VHF Dick
PA5MW Mark
PA9M Marcel
PA3FGI Marcel
PB0AED Marcel
PB7Z Bernard
PC5A Aurelio
PD1RP Peet
SP5XVY Robert
V51W Martin
DJ7KP Martin
V51WH Gunter
DK2WH Gunter
W7VJ Andrew
ZL3IO Holger
A31IO Holger
ZM2Y Holger
ZM4T,Holger';
if (preg_match("/$call/",$txt)) return 1;
return 0;	
}	



?>
