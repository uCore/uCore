<?php

class uEvents {
	private static $callbacks = array();
	public static function AddCallback($eventName, $callback, $module = '') {
		$module = strtolower($module);
		$eventName = strtolower($eventName);
		
		if (isset(self::$callbacks[$eventName][$module]) && in_array($callback,self::$callbacks[$eventName][$module])) return;
		self::$callbacks[$eventName][$module][] = $callback;
	}
	public static function RemoveCallback($module, $eventName, $callback) {
		$module = strtolower($module);
		$eventName = strtolower($eventName);

		if (!isset(self::$callbacks[$eventName][$module])) return;
		$key = array_search($callback,self::$callbacks[$eventName][$module]);
		if ($key !== NULL) unset(self::$callbacks[$eventName][$module][$key]);
	}
	public static function TriggerEvent($eventName,$object=null,$eventData=null) {
		$module = null;
		if (is_object($object)) $module = get_class($object);
		if (is_string($object)) {
			$module = $object;
			$object = utopia::GetInstance($object);
		}
		$module = strtolower($module);
		$eventName = strtolower($eventName);

		if (!isset(self::$callbacks[$eventName])) return TRUE;
		$process = array_unique(array($module,''));
		
		if (!is_array($eventData)) $eventData = array($eventData);
		array_unshift($eventData,$object,$eventName);

		$return = true;
		foreach ($process as $module) {
			if (!isset(self::$callbacks[$eventName][$module])) continue;
			foreach (self::$callbacks[$eventName][$module] as $callback) {
				$return = $return && (call_user_func_array($callback,$eventData) !== FALSE);
			}
		}
		return $return;
	}
}
