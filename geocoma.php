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
        try{ $point = $this->getPoint($row['street'], $row['city'], $row['state'], $row['zip']); }
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

  private function getPoint($street, $city, $state, $zip){
    
    //Use the hashmap to translate stree, city, state, zip into lat, lon
    if($this->liteDB->contains($street, $city, $state, $zip)){
      return $this->liteDB->get($street, $city, $state, $zip);
    }
    
    //Use google to translate city, state into longitude latitude

    $base_url = "http://maps.google.com/maps/geo?output=xml&q=";

    #find city and get longitude latttitude in google's returned xml
    $request_url = $base_url . urlencode("$street $city $zip $state");
    $xml = simplexml_load_file($request_url);
    if(!$xml){
      throw new Exception("url not loading");
    }

    $status = $xml->Response->Status->code;
    if (strcmp($status, "200") == 0) {// Successful geocode
      $coordinates = $xml->Response->Placemark->Point->coordinates;
      $coordinatesSplit = split(",", $coordinates);

      $point = new point($coordinatesSplit[0], $coordinatesSplit[1]);
      
      $this->liteDB->set($stree, $city, $state, $zip, $point);
      
      return $point;

    } else {// failure to geocode
      throw new Exception("Error status $status on $street, $city, $state, $zip failed to geocode");
      //Check it out here http://code.google.com/apis/maps/documentation/geocoding/#StatusCodes
    }
  }
  
  private function savePoint($street, $city, $state, $zip, $point){
    $this->geoCache[$street.$city.$state.$zip] = $point;
    
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
  }
}
