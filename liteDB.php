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
    $query = $this->dbhandle->unbufferedQuery('SELECT city,state,lat,lon FROM geo'); // unbuffered result set
    //                ->query()   <<this is the buffered method option
    //TODO: Consider adding a limit to the query, with this suffix on the SELECT command: LIMIT 250

    $result = $query->fetchAll(SQLITE_ASSOC);
    foreach ($result as $entry) {
      $this->geoCache[$entry['city'].$entry['state']] = new point($entry['lat'], $entry['lon']);
    }
  }

  public function contains($city, $state){
    if(!isset($this->geoCache)){
      $this->init();
    }
    print_r(array_key_exists($city.$state, $this->geoCache));
    return array_key_exists($city.$state, $this->geoCache);
  }

  public function get($city, $state){
    if(!isset($this->geoCache)){
      $this->init();
    }
    return $this->geoCache[$city.$state];
  }

  public function set($city, $state, $point){
    $lat = $point->lat;
    $lon = $point->lon;
    $this->dbhandle->queryExec("INSERT INTO geo VALUES ('$city', '$state', '$lat', '$lon')");
    $this->geoCache[$city.$state] = new point($lat, $lon);
  }
}