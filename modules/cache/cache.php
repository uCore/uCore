<?php

class uCache {
	/**
	 * Cached files are stale if older than four weeks by default
	 */
	public static $staleTime = 2419200;
	
	/**
	 * Get path to cache file if file exists and is not stale
	 * 
	 * @param $identifiers Unique information about this data, used to generate hash
	 * @param $staleTime NULL to use default, FALSE to ignore file age, integer to specify maximum age in seconds
	 * @return False if cache not found, otherwise returns absolute path to cache file
	 */
	public static function retrieve($identifiers,$staleTime = null) {
		$path = self::getPath($identifiers);
		if (!file_exists($path)) return false;
		if ($staleTime === null) $staleTime = self::$staleTime;
		if ($staleTime !== false && (filemtime($path) < (time()-$staleTime))) return false;
		return $path;
	}
	
	/**
	 * Saves data in a cache file and returns the path
	 * 
	 * @param $identifiers Unique information about this data, used to generate hash
	 * @return absolute path to cache file
	 */
	public static function store($identifiers,$data) {
		$path = self::getPath($identifiers);
		file_put_contents($path,$data);
		return $path;
	}
	
	/**
	 * Get path to cache file
	 * 
	 * @param $identifiers Unique information about this data, used to generate hash
	 * @return absolute path to cache file
	 */
	private static function getPath($identifiers) {
		$cachePath = PATH_ABS_CORE.'.cache/';
		$checksum = utopia::checksum($identifiers);
		$cachePath .= substr($checksum,0,3).'/'.substr($checksum,3,3).'/';
		if (!file_exists($cachePath)) mkdir($cachePath,0777,true);
		return $cachePath.$checksum;
	}
}
