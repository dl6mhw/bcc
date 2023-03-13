<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("lib.php");
$verzeichnis = "2023_1";
$result = $mysqli->query('truncate t_entry');
$result = $mysqli->query('truncate t_qso');
if ($argv[1]!='force') {
  print "Abbruch weil force fehlt\n";
  exit;  
}	
// Test, ob es sich um ein Verzeichnis handelt
if ( is_dir ( $verzeichnis ))
{
    // öffnen des Verzeichnisses
    if ( $handle = opendir($verzeichnis) )
    {
        // einlesen der Verzeichnisses
        while (($file = readdir($handle)) !== false)
        {
            echo "\n-----------------\nDateiname: ";
            echo $file;

            /*echo "Dateityp: ";
            echo filetype( $file );
            echo "\n";
			*/
			print "\n";
			if (preg_match('/log/i',$file)) {
			 $log = file_get_contents("$verzeichnis/$file");
			 #nur einige Logs importieren
			 if (preg_match('/[23456]/i',$file)) {
			   importFile('nocall',$log);
			 }  
			} 
			
        }
        closedir($handle);
    }
}


?>