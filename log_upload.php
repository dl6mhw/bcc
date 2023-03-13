<?php 
include("lib.php");
head();
?>
<main role="main" class="container">
<div class="container">
   <div class="row">
     <div class="col-sm-8">
        <h3>BCC-Party Upload</h3>
		
<?php

  if (deadline()) {
?>  
<form action="do_upload.php" method=POST  enctype="multipart/form-data">
  <div class="form-group row">
    <div class="col-sm-2"><label for="callsign" class="col-form-label">Call</label></div>
    <div class="col-sm-10"><input id="callsign" name="callsign" pattern=".{4,12}" type="text" class="form-control"></div>
  </div>
  <div class="form-group row">
    <div class="col-sm-2"><label for="email" class="col-form-label">E-Mail</label></div>
    <div class="col-sm-10"><input id="email" name="email" type="email" class="form-control" required></div>
  </div>
  <div class="form-group row">
    <div class="col-sm-2"><label for="power" class="col-form-label">Power</label></div>
	<div class="col-sm-10">	
	  <div class="custom-control custom-radio custom-control-inline">
		<input type="radio" class="custom-control-input" id="QRP" name="power" value="QRP">
		<label class="custom-control-label" for="QRP">QRP (max 5 Watts)</label>
	  </div>
	  <div class="custom-control custom-radio custom-control-inline">
		<input type="radio" class="custom-control-input" id="LOW" name="power" value="LOW" checked>
		<label class="custom-control-label" for="LOW">Low (max 100 Watts)</label>
	  </div>  
	  <div class="custom-control custom-radio custom-control-inline">
		<input type="radio" class="custom-control-input" id="HIGH" name="power" value="HIGH">
		<label class="custom-control-label" for="HIGH">High (> 100 Watts)</label>
	  </div>  
	</div>	
  </div>
  <div class="form-group row">
    <div class="col-sm-2"><label for="custom-file" class="col-form-label">Log-Datei</label></div>
	<div class="col-sm-10">	
		<div class="custom-file">
		<input type="file" class="custom-file-input" id="customFile" name="customFile" required>
		<label class="custom-file-label" for="customFile">Choose file</label>
		</div>
	</div>
  </div>
  <div class="form-group row">
    <div class="col-sm-2"></div>
    <div class="col-sm-10">
	  <button type="submit" class="btn btn-primary">Log einreichen</button>
    </div>
  </div>
  
  
</form>
  <?php }?>
</div>
     <!-- /.col-sm-8 -->
     <div class="col-sm-4">
<?php
print "<h4>Aktuell</h4>\n";
print "<p>QSOs: ".sql2val("select count(*) from t_qso where status <>'o'")."<br>";
print "Logs: ".sql2val("select count(distinct callsign) from t_entry")."</p>";
print "<p>Aktuelle Uploads</p>";
$result = $mysqli->query("select callsign, date_format(l_date,'%H:%i:%s') from t_entry order by l_date desc limit 40");	
while ($row = $result->fetch_array(MYSQLI_NUM)) {	
	print "$row[0] ($row[1]) | ";
}
?>
     </div>
     <!-- /.col-sm-4 -->
   </div>
   <!-- /.row -->
  </div>
  <!-- /.container -->
 




    </main><!-- /.container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="../../assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
    <script src="https://getbootstrap.com/docs/4.0/assets/js/vendor/popper.min.js"></script>
    <script src="https://getbootstrap.com/docs/4.0/dist/js/bootstrap.min.js"></script>
<script>
// Add the following code if you want the name of the file appear on select
$(".custom-file-input").on("change", function() {
	
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});
</script>


  </body>
</html>
