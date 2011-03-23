function chkCsvName() {
	name = document.getElementById("csvfile").value;
	name = name.toLowerCase();
	ext = /\.csv$/;// regex to match .csv at the end of a filename
	if (name.match(ext)) {
		document.getElementById("csvcheck").value = "Good file name";
		return true;
	} else {
		document.getElementById("csvcheck").value = "Bad file extension : .csv required";
		return false;
	}
}