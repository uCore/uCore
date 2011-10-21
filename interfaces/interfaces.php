<?php
interface iUtopiaModule {}
interface iAdminModule {}

interface iWidget {
        // for adding custom fields etc
        static function Initialise($sender);
        static function DrawData($data);
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
