<?php
/*
 Copyright 2010 James Koval

 This file is part of CSVtoKML

 CSVtoKML is free software: you can redistribute it
 and/or modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation, either version 3
 of the License, or (at your option) any later version.

 CSVtoKML is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See
 the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with CSVtoKML. If not, see <http://www.gnu.org/licenses/gpl.html>

 email:  james.ross.koval () gmail dot com
 */

class geocoma{

  private static $kmlStart = "<?xml version='1.0' encoding='UTF-8'?>
   <kml xmlns='http://www.opengis.net/kml/2.2'>
   <Document>";
   
  //$kmlMidFilled = sprintf($kmlMid, $name, $description, $eastLat, $northLong);
  private static $kmlMid = '<Placemark>
  <name>%s</name>
  <description>%s</description>
  <Point>
  <coordinates>%s,%s</coordinates>
  </Point>
  </Placemark>';
   
  private static $kmlEnd = '</Document></kml>';
  
  private $liteDB;

  public function __construct(){
    include('point.php');
    include('liteDB.php');
    
    //Populate $geoCache, lookup table for values of lat,lon knowing the key: city,state
    $this->liteDB = new liteDB();
    $this->liteDB->init();
  }

  public function parse($filePath){
    //Checks and initializes csv file reader
    $csvReader = $this->getCsvReader($filePath); //throws exception

    $outputKml = geocoma::$kmlStart;

    //CSV File Data Columns
    //"county","date","street","city","state","zip",
    //"name","description","month","lat","lon","lastname","firstname","dob"

    //Cycle each line of the csv, and generate kml code
    foreach ($csvReader->data as $row){
      if($row['lat'] != '' and $row['lon'] != ''){
        $outputKml .= sprintf(geocoma::$kmlMid,
        $row["name"],
        $row["description"],
        $row["lat"],
        $row["lon"]);
      }else{
        try{ $point = $this->getPoint($row['city'], $row['state']); }
        catch (Exception $e){ continue; }//skip unparsable data
        $outputKml .= sprintf(geocoma::$kmlMid,
        $row["name"],
        $row["description"],
        $point->lat,
        $point->lon);
      }
    }

    $outputKml .= geocoma::$kmlEnd;

    return $outputKml;
  }

  private function getPoint($city, $state){
    
    //Use the hashmap to translate city, state into lat, lon
    if($this->liteDB->contains($city,$state)){
      return $this->liteDB->get($city,$state);
    }
    
    //Use google to translate city, state into longitude latitude

    $base_url = "http://maps.google.com/maps/geo?output=xml&q=";

    #find city and get longitude latttitude in google's returned xml
    $request_url = $base_url . urlencode("$city,$state");
    $xml = simplexml_load_file($request_url);
    if(!$xml){
      throw new Exception("url not loading");
    }

    $status = $xml->Response->Status->code;
    if (strcmp($status, "200") == 0) {// Successful geocode
      $coordinates = $xml->Response->Placemark->Point->coordinates;
      $coordinatesSplit = split(",", $coordinates);

      $point = new point($coordinatesSplit[0], $coordinatesSplit[1]);
      
      $this->liteDB->set($city, $state, $point);
      
      return $point;

    } else {// failure to geocode
      throw new Exception("Error status $status on city $city failed to geocode");
      //Check it out here http://code.google.com/apis/maps/documentation/geocoding/#StatusCodes
    }
  }
  
  private function savePoint($city, $state, $point){
    $this->geoCache[$city.$state] = $point;
    
    //TODO: Save data to sqlite database
  }

  private function getCsvReader($filePath){
    #Ensure file name is the correct type: .csv
    if (strlen(basename($filePath, ".csv")) <= 4 or #hasn't enough characters for .csv at the end
    substr_compare(strtolower($filePath), ".csv", -4, 4) != 0){ #doesn't end in .csv
      throw new Exception("Please select a CSV file");
    }

    //Initialize reader
    $this->includeCsvLibrary();
    $reader = new parseCSV();
    $reader->auto($filePath);

    #Check for the correct CSV column titles
    $this->checkTitles($reader->titles); //throws exception

    return $reader;
  }


  private function checkTitles($csvColumnHeadings){
    //	    name - optional placemark name
    //		street
    //		city
    //		state
    //		zip
    //		description will be used in the kml description w/ other elements
    //		county (will be added to the kml description see below)
    //		date ( added to description)
    //		month ( will be used to name the kml folder that all results will be listed under)
    //      latitude
    //		longitude

    //    $requiredColumnHeadings = array("name","street","city","state","zip","description",
    //    "county","date", "month", "latitude", "longitude");
    $requiredColumnHeadings = array("county","date","street","city","state","zip",
    "name","description","month","lat","lon","lastname","firstname","dob");//lastname, firstname, dob are new

    foreach($requiredColumnHeadings as $heading){
      if(!(in_array($heading, $csvColumnHeadings))){
        throw new Exception ($heading . " doesn't exist");
      }
    }
  }

  private function includeCsvLibrary(){


    #wild card include statement to allow easy copy-paste updating of parsecsv
    #  Parsecsv's foldername as of 2010/7/23 is parsecsv-0.4.3-beta
    #  which should be replaced with a non-beta folder ASAP
    #  from http://code.google.com/p/parsecsv-for-php
    #    Simply delete the old folder, and put the new folder, downloaded from google,
    #    in its place
    array_walk(glob('./parsecsv*/parsecsv.lib.php'),create_function('$v,$i', 'return require_once($v);'));


    #          NO LONGER USING THIS parsecsv library
    /*    include('php-csv-utils-0.3/Csv/Dialect.php');
    include('php-csv-utils-0.3/Csv/Exception.php');
    include('php-csv-utils-0.3/Csv/Reader/Abstract.php');
    include('php-csv-utils-0.3/Csv/Reader.php');
    include('php-csv-utils-0.3/Csv/AutoDetect.php');
    include('php-csv-utils-0.3/Csv/Writer.php');
    include('php-csv-utils-0.3/Csv/Dialect/Excel.php');
    include('php-csv-utils-0.3/Csv/Reader/String.php');
    array_walk(glob('php-csv-utils-0.3/Csv/Exception/*.php'),create_function('$v,$i', 'return include($v);'));*/
  }

  private function oldCode(){
    function display_all($arr){
      echo "<table cellpadding='5' border><tr><th>Column Name</th><th>Column Index</th>";
      echo "<th>Column Data -></th></tr>";
      foreach($arr as $spot){
        echo "<tr>";
        foreach($spot as $spotdeux){
          echo "<td align='center'>";
          echo $spotdeux;
          echo "</td>";
        }
        echo "</tr>";
      }
      echo "</tr> </table>";
    }

    print_r($argv);

    #if no input, default input
    if(! $argv[1]){ $argv[1] = 'sample'; }
    if(! $argv[2]){ $argv[2] = 'all'; }
    if(! $argv[3]){ $argv[3] = 'city'; }
    if(! $argv[4]){ $argv[4] = 'wv'; }
    if(! $argv[5]){ $argv[5] = ''; }
    if(! $argv[6]){ $argv[6] = ''; }
    #if running from php cli, set vars accordingly
    #  or if no input was sent from the user, default it
    if(! $_GET["file"] ){ $_GET["file"] = $argv[1]; }
    if(! $_GET["columns"] ){ $_GET["columns"] = $argv[2]; }
    if(! $_GET["city"] ){ $_GET["city"] = $argv[3]; }
    if(! $_GET["state"] ){ $_GET["state"] = $argv[4]; }
    if(! $_GET["kml"] ){ $_GET["kml"] = $argv[5]; }
    if(! $_GET["showcity"] ){ $_GET["showcity"] = $argv[6]; }

    #wild card include statement to allow easy copy-paste updating of parsecsv
    #  Parsecsv's foldername as of 2010/7/23 is parsecsv-0.4.3-beta
    #  which should be replaced with a non-beta folder ASAP
    #  from http://code.google.com/p/parsecsv-for-php
    #    Simply delete the old folder, and put the new folder, downloaded from google,
    #    in its place
    array_walk(glob('./parsecsv*/parsecsv.lib.php'),create_function('$v,$i', 'return require_once($v);'));

    $csv = new parseCSV();
    $csv->auto($_GET["file"].'.csv');

    #Testing csv writing for making a lookup table with cities and coordinates
    #$csv->save('testFile.csv', array('parkersburg','wv','kaos','1.1'),false);

    $columns = split(" ", $_GET["columns"]);

    #delete whitespace around column names, and check for the all columns option
    for($i = count ( $columns )-1; $i >= 0; $i--){
      if(! trim($columns[$i])){
        unset($columns[$i]);#delete whitespace
      }
      if(strcmp($columns[$i],'all')==0){
        $columns = $csv->titles;#grab all the columns
        break;
      }
    }

    #Hold column name in 0, column index in 1, and data in 2+
    #check if exists in csv file, then set column name as per user's request
    foreach ($columns as $column){#columns the user wants
      foreach ($csv->titles as $title){#columns/titles that actually exist
        if(strcmp($title,$column)==0){#only add if column is a title
          $displayData[][0] = $column;
          #print "<br /><br />Just added $column because it matches $title, and here is the full table:<br /><br />";
          #display_all($displayData);
        }
      }
    }

    if(!$displayData[0][0]){#if no data, -- no requested columns matched actual titles in csv
      foreach($csv->titles as $title){#gather actual columns
        $titles .= "$title<br />";
      }
      foreach ($columns as $column){#gather columns the user wants
        $columnsRequested .= "$column<br />";
      }
      die("No reason to continue if your requests:<br /><br />$columnsRequested<br />don't match actual column titles:<br /><br />$titles");
    }

    #set column index
    for ($i = 0; $i < count($csv->titles); $i++){#cycle column names in csv file
      for ($j = 0; $j < count ($displayData); $j++){#cycle desired columns -- from input
        if(strcmp($csv->titles[$i],$displayData[$j][0])==0){ #case insensitive equality
          $displayData[$j][1] = $i;#set the second row to the column index
          #print "<br /><br />Found the index to ".$displayData[$j][0].", and here is the table so far:";
          #display_all($displayData);
        }
      }
    }

    #put data in index 2 onward in $displayData
    foreach ($csv->data as $key => $row){
      $j = -1;
      foreach ($row as $value){
        $j++;
        for ($i = 0; $i < count($displayData); $i++){
          if($displayData[$i][1] == $j){
            $displayData[$i][] = $value;
            #print "<br /><br />Found the data to ".$displayData[$i][1].", and here is the table so far:";
            #display_all($displayData);
          }
        }
      }
    }

    #this will show all the data in a table, rather than KML
    if(! $_GET['kml']){
      print '<title>CSVtoKML Data</title><head><style type=text/css>.bg{color:00FF00;background:black;}</style></head><body class=bg>';
      display_all($displayData);
      die();#we musn't want kml, and that is everything below this point
    }

    //String Beginning for KML
    $kmlStart=
  '<?xml version="1.0" encoding="UTF-8"?>
  <kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>';
    //Middle section for longitutde lattitude
    $kmlMid=
  '<Placemark>
  <name>NAMEregex</name>
  <description>DESCRIPTIONregex</description>
  <Point>
  <coordinates>EASTregex,NORTHregex</coordinates>
  </Point>
  </Placemark>';
    //String End for KML
    $kmlEnd=
  '</Document></kml>';

    //switch < and > with compatable alternatives for output in html
    //  throughout the entire kml framework
    #$kmlStart= preg_replace(array('/</','/>/'),array('&lt;','&gt;<br />'),$kmlStart);
    #$kmlMid= preg_replace(array('/</','/>/'),array('&lt;','&gt;<br />'),$kmlMid);
    #$kmlEnd= preg_replace(array('/</','/>/'),array('&lt;','&gt;<br />'),$kmlEnd);
    //print $kmlStart;print $kmlMid;print $kmlEnd;


    #grab the index for the city
    for($i = 0; $i< count ($displayData); $i++){
      if(strcmp($displayData[$i][0],$_GET["city"]) == 0){
        $cityIndex = $displayData[$i][1];
        break;
      }
    }
    #print "<br />City column, " . $_GET['city'] . " is at index $cityIndex, for use in geo-coding<br />";
    if(! isset($cityIndex)){
      foreach($csv->titles as $title){#gather actual columns
        $titles .= "$title<br />";
      }
      foreach ($columns as $column){#gather columns the user wants
        $columnsRequested .= "$column<br />";
      }
      print "<br />Could not find city column, ".$_GET["city"];
      print ", in your selected columns:<br />$columnsRequested";
      print "<br />Is it somewhere in the total CSV file's columns?<br />$titles";
      die;
    }

    $cities = $displayData[$cityIndex];
    #unset ($displayData[$cityIndex]);

    $base_url = "http://maps.google.com/maps/geo?output=xml&q=";
    for($i = 2; $i< count ($displayData[0]); $i++){

      #setup the description to replace, using a RegEx, DESCRIPTIONregex in $kmlMid
      $description = "";
      for($j = 0; $j < count ($displayData); $j++){
        if($j !== $cityIndex){
          $description .= $displayData[$j][0] . " : " . $displayData[$j][$i] . "<br />";
        }
        elseif($_GET['showcity']) {
          $description .= $cities[0] . " : " . $cities[$i] . "<br />";
        }
      }

      #find city and get longitude latttitude in google's returned xml
      $request_url = $base_url . urlencode($cities[$i].",".$_GET['state']);
      $xml = simplexml_load_file($request_url) or die("url not loading");

      $status = $xml->Response->Status->code;
      if (strcmp($status, "200") == 0) {// Successful geocode
        $coordinates = $xml->Response->Placemark->Point->coordinates;
        $coordinatesSplit = split(",", $coordinates);

        $lattitude = $coordinatesSplit[1];
        $longitude = $coordinatesSplit[0];
        $replaceThis = array('/DESCRIPTIONregex/','/NAMEregex/','/EASTregex/','/NORTHregex/');
        $withThis =    array($description, $i-1, $longitude, $lattitude);

        #Take the constant kml mid template, and save a modified version to put on the end of
        #  mid. This puts a description, a name, and cordinates in kml format
        $mid .= preg_replace($replaceThis, $withThis, $kmlMid);

      } else {// failure to geocode
        echo "<br />City: ", $cities[$i] ,"  failed to geocode.<br />";
        echo "<br />Received status $status from google<br />";
        print "Check it out here http://code.google.com/apis/maps/documentation/geocoding/#StatusCodes <br />";
        print "Are you sure you have the city column correct?<br />";
        die();
      }
    }

    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="infects.kml"');
    print $kmlStart;
    print $mid;
    print $kmlEnd;

    #this will show how long the script took
    #echo "<br />",xdebug_time_index(), "  <--End Time<br />";
  }
}