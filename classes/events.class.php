<?php

class uEvents {
	private static $callbacks = array();
	public static function AddCallback($module, $eventName, $callback) {
		$module = strtolower($module);
		$eventName = strtolower($eventName);
		
		if (isset(self::$callbacks[$module][$eventName]) && in_array($callback,self::$callbacks[$module][$eventName])) return;
		self::$callbacks[$module][$eventName][] = $callback;
	}
	public static function RemoveCallback($module, $eventName, $callback) {
		$module = strtolower($module);
		$eventName = strtolower($eventName);

		if (!isset(self::$callbacks[$module][$eventName])) return;
		$key = array_search($callback,self::$callbacks[$module][$eventName]);
		if ($key !== NULL) unset(self::$callbacks[$module][$eventName][$key]);
	}
	public static function TriggerEvent($object,$eventName,$eventData=null) {
		$module = strtolower(get_class($object));
		$eventName = strtolower($eventName);

		if (!isset(self::$callbacks[$module][$eventName])) return TRUE;
		
		$callbackArgs = array($object,$eventName);
		if ($eventData) $callbackArgs[] = $eventData;
		
		foreach (self::$callbacks[$module][$eventName] as $callback) {
			if (call_user_func_array($callback,$callbackArgs)===FALSE) return FALSE;
		}
		return TRUE;
	}
}