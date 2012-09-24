<?php

class uEvents {
	private static $callbacks = array();
	public static function AddCallback($eventName, $callback, $module = '',$order=NULL) {
		$module = strtolower($module);
		$eventName = strtolower($eventName);
		if (!isset(self::$callbacks[$eventName][$module])) self::$callbacks[$eventName][$module] = array();
		if (self::CallbackExists($eventName, $callback, $module)) return;
		
		if ($order === NULL) $order = count(self::$callbacks[$eventName][$module])+1;

		self::$callbacks[$eventName][$module][] = array('callback'=>$callback,'order'=>$order);
	}
	public static function RemoveCallback($eventName, $callback, $module = '') {
		$cb = self::CallbackExists($eventName, $callback, $module);
		if ($cb) unset($cb);
	}
	public static function &CallbackExists($eventName, $callback, $module = '') {
		$module = strtolower($module);
		$eventName = strtolower($eventName);
		$false = FALSE;
		
		if (!isset(self::$callbacks[$eventName][$module])) return $false;
		foreach (self::$callbacks[$eventName][$module] as $k => $v) {
			if ($v['callback'] === $callback) return self::$callbacks[$eventName][$module][$k];
		}
		return $false;
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
		array_unshift($eventData,&$object,$eventName);

		// accumulate all handlers
		$handlers = array();
		foreach ($process as $module) {
			if (!isset(self::$callbacks[$eventName][$module])) continue;
			$handlers = array_merge($handlers, self::$callbacks[$eventName][$module]);
		}
		// sort handlers
		array_sort_subkey($handlers,'order');
		// execute handlers, break if any return FALSE;
		$return = true;
		foreach ($handlers as $callback) {
			$return = $return && (call_user_func_array($callback['callback'],$eventData) !== FALSE);
		}
		return $return;
	}
}
