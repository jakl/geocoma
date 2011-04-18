<?php
class liteDB{
  public static $sqliteDBname = 'geo.sqlite';

  private $dbhandle;

  private $geoCache;

  public function __construct(){
    //include('point.php'); //Point has already been included in geocoma

    $this->dbhandle = new SQLiteDatabase(liteDB::$sqliteDBname);
  }

  public function init(){
    $this->geoCache = array();

    //unbuffered reads the database sequentially as needed, rather than dumping it immediately to memory
    $query = $this->dbhandle->unbufferedQuery('SELECT street,city,state,zip,lat,lon FROM geo'); // unbuffered result set
    //                      ->query()   <<this is the buffered method option
    //TODO: Consider adding a limit to the query, with this suffix on the SELECT command: LIMIT 250

    $result = $query->fetchAll(SQLITE_ASSOC);
    foreach ($result as $entry) {
      $this->geoCache[$endtry['street'].$entry['city'].$entry['state'].$entry['zip']] = new point($entry['lat'], $entry['lon']);
    }
  }

  public function contains($street, $city, $state, $zip){
    if(!isset($this->geoCache)){
      $this->init();
    }
    return array_key_exists($street.$city.$state.$zip, $this->geoCache);
  }

  public function get($street, $city, $state, $zip){
    if(!isset($this->geoCache)){
      $this->init();
    }
    return $this->geoCache[$street.$city.$state.$zip];
  }

  public function set($street, $city, $state, $zip, $point){
    $lat = $point->lat;
    $lon = $point->lon;
    $this->dbhandle->queryExec("INSERT INTO geo VALUES ('$street', '$city', '$state', '$zip', '$lat', '$lon')");
    $this->geoCache[$street.$city.$state.$zip] = new point($lat, $lon);
  }
}
