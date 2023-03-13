<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");
head();
?>
<main role="main" class="container">
<div class="container">
   <div class="row">
     <div class="col-sm-8">
        <h3>Upload Bericht</h3>
     </div>
     <!-- /.col-sm-8 -->
   </div>
   <!-- /.row -->

   <div class="row">
     <div class="col-sm-8">
		
<?php
#print_r($_POST);
#print_r($_FILES);

if (deadline()) {
print "<pre>";	
$target_dir = "eingang/";
$uploadOk = 1;

$f_type=$_FILES['customFile']['type'];


$fileContent = file_get_contents($_FILES['customFile']['tmp_name']);
#print $fileContent;

#hier Test ob Typ in LOG oder CBR oder??? 
// Check if image file is a actual image or fake image
#if(isset($_POST["submit"])) {
#Logentry erzeugen
$callsign=strtoupper(strip_tags($_POST['callsign']));
$email=strip_tags($_POST['email']);
$power=strip_tags($_POST['power']);

$stmt = $mysqli->prepare("update t_qso set status='d' where call1 = ?");
$stmt->bind_param("s", $callsign);
$stmt->execute();
if ($stmt->error) printf("Error: %s.\n", $stmt->error);

$stmt = $mysqli->prepare("insert into t_entry (callsign,email,power,logfile) VALUES (?, ?, ?,?)");
$stmt->bind_param("ssss", $callsign, $email,$power,$fileContent);
$stmt->execute();
$entry_id=$mysqli->insert_id;
print "Interne Log-ID: $entry_id\n";
if ($stmt->error) printf("Error: %s.\n", $stmt->error);
$target_file = $target_dir . $entry_id.'_'.basename($_FILES["customFile"]["name"]);
$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
print "Typ: $fileType<br>\n";
if (preg_match('/log/',$fileType)) print "Logfile gefunden\n";
else "Datei kein log-File ... keine Verarbeitung\n";

if (move_uploaded_file($_FILES["customFile"]["tmp_name"], $target_file)) {
    echo "Die Datei ". htmlspecialchars( basename( $_FILES["customFile"]["name"])). " wurde gespeichert\n";
  } else {
    echo "Das Hochladen hat nicht geklappt";
}

importFile($callsign,$fileContent);


print "<p><h4>fertsch</h4>";
}
print "<a href=list.php>Vorläufige Ergebnisse</a>\n"

?>
</div>
</div>
</div>
</main>
</body>
</html>