<?php
//When parsing a file, offer it for download immediately
if(isset($_POST['Parse']) and isset($_POST['file'])){
  include 'geocoma.php';
  $geo = new geocoma();
  $csvFileName = $_POST['file'];
  
  try{
    $kmlFile = $geo->parse($csvFileName);//throws exception

    //try to write kml file to local directory, accepting any silent nonfatal errors
    fwrite(fopen(basename($csvFileName, ".csv").".kml", 'w+'), $kmlFile);

    header('Content-type: application/kml');
    header('Content-Disposition: attachment; filename="geocomaTesting.kml"');
    print $kmlFile;
    exit;
  }
  catch (Exception $e) { print $e->getMessage(); }
}
?>

<!DOCTYPE HTML>
<html>
<head>
<?php include 'header.php'?>
</head>
<body>
<script type="text/javascript" src="chkCsvName.js"></script>
<?php
#Handle a file upload if a file was selected
if(isset($_POST['upload'])){
  include 'uploader.php';
  echo uploader::upload($_FILES['file']);
}

include 'listFiles.php';
print "<form action='index.php' method='post'>";
print fileLister::listFiles('radio');
print "<br /><input type='submit' name='Parse' value='Parse' />";
print "</form>";
print fileLister::listFiles('', ".kml");
?>
<form action="index.php" method="post" enctype="multipart/form-data"
	onsubmit="return chkCsvName()"><label for="file">Filename:</label> <input
	type="file" name="file" id="csvfile" />
<input type="submit" name="upload" value="submit" /></form>
<?php include 'footer.php'?>
</body>
</html>
