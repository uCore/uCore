<?php

/**
 * iInit: impliment static initialiser run before all events
 */
interface iInit {
	public static function Initialise();
}

/**
 * iUtopiaModule: Identifying interface for utopia modules
 */
interface iUtopiaModule extends iInit {}

/**
 * iRestrictedAccess: used to define modules which utilise User Roles
 */
interface iRestrictedAccess {}

/**
 * iAdminModule: Is an iRestrictedAccess class which also forces ADMIN_TEMPLATE to be used
 */
interface iAdminModule extends iRestrictedAccess {}


/**
 * iWidget: defines a class as a widget and requires the developer to specify the structure and output for the widget
 */
interface iWidget {
	/**
	 * Provides quick access to set up additional fields for the widget instance
	 * @param object $sender uWidget instance
	 */
	static function Initialise($sender);
	
	/**
	 * Echo output based on data
	 * @param array $data uWidget record with all populated information
	 */
	static function DrawData($data);
}


/**
 * iDashboardWidget: displayed on the dashboard, customisable dashboard per user.
 * Three different sizes of widget: Full(100), Half(50), Quarter(25)
 */
interface uDashboardWidget extends iUtopiaModule,iRestrictedAccess {
	static function GetTitle();
	//static function Draw100() { }
	//static function Draw50() { }
	//static function Draw25() { }
}
 
 
/*
 interface iOutput {
 // ShowData is the main function which processes each field in turn and returns the resulting html to be output to the browser.
 function ShowData();

 // per record
 function OutputRecord();

 function OutputField();

 // returns the cell, including <span id="">
 //	function GetFieldSpan();
 //	function GetCellData();
 }
 */
