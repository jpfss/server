<?php

chdir(__DIR__.'/../');
require_once(__DIR__ . '/../bootstrap.php');

//collect vars
$mapName = $argv[1];
$iniFile = $argv[2];
isset($argv[3]) ? $mapName = $mapName.'_'.$argv[3] : $mapName = $mapName;

//Open cache connection
include (kEnvironment::getConfigDir().'/configCacheParams.php');
$cache = new kMemcacheCacheWrapper;
if(!$cache->init(array('host'=>$finalCacheSourceConfiguration['host'],
	 				   'port'=>$finalCacheSourceConfiguration['port'])))
	die ("Fail to connect to cache host {$finalCacheSourceConfiguration['host']} port {$finalCacheSourceConfiguration['port']} ");

//get file info
if(!file_exists($iniFile))
	die ("COnfiguration file does not exist {$iniFile}");
$config = new Zend_Config_Ini($iniFile);
$fileContent  = $config->toArray();
print ("Going to add the following file content to cache - \r\n");
print_r($fileContent);

//Add file to cache
$cache->set($mapName,$fileContent);

//Add file name to map list
$mapList = $cache->get(finalCacheSource::MAP_LIST_KEY);
if(!isset($mapList[$mapName]))
{
	print ("Adding map {$mapName} to map list - \r\n");
	$mapList[] = $mapName;
	$cache->set(finalCacheSource::MAP_LIST_KEY,$mapList);
}

//Change baseConfCache::CONF_CACHE_VERSION_KEY
$newCacheVersion = baseConfCache::generateKey();
if($cache->set(baseConfCache::CONF_CACHE_VERSION_KEY,$newCacheVersion))
	print ("Setting new cache version  {$newCacheVersion}\r\n");

