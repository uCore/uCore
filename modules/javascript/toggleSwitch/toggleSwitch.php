<?php

utopia::AddInputType('itYESNO','ToggleSwitch::DrawToggleSwitch');
utopia::AddInputType('itONOFF','ToggleSwitch::DrawToggleSwitch');

uEvents::AddCallback('BeforeInit','ToggleSwitch::Init');
class ToggleSwitch {
	public static function Init() {
		uCSS::IncludeFile(dirname(__FILE__).'/toggleSwitch.css');
		uJavascript::IncludeFile(dirname(__FILE__).'/toggleSwitch.js');
		uJavascript::IncludeText(<<<FIN
utopia.Initialise.add(function () { $(".inputtype-itONOFF:not(.switched)").addClass("switched").toggleSwitch('onoff');});
utopia.Initialise.add(function () { $(".inputtype-itYESNO:not(.switched)").addClass("switched").toggleSwitch('yesno');});
FIN
);
	}
	static function DrawToggleSwitch($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		return utopia::DrawInput($fieldName,itCHECKBOX,$defaultValue,$possibleValues,$attributes,$noSubmit);
	}
}
