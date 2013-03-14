<?php

utopia::AddInputType('itYESNO',array('jQuery_toggleSwitch','DrawToggleSwitch'));
utopia::AddInputType('itONOFF',array('jQuery_toggleSwitch','DrawToggleSwitch'));

class jQuery_toggleSwitch extends uBasicModule{
	public function SetupParents() {
		uCSS::IncludeFile(dirname(__FILE__).'/toggleSwitch.css');
		uJavascript::IncludeFile(dirname(__FILE__).'/toggleSwitch.js');
		uJavascript::IncludeText(<<<FIN
utopia.Initialise.add(function () { $(".inputtype-itONOFF:not(.switched)").addClass("switched").toggleSwitch('onoff');});
utopia.Initialise.add(function () { $(".inputtype-itYESNO:not(.switched)").addClass("switched").toggleSwitch('yesno');});
FIN
);
	}
	public function RunModule() {}
	static function DrawToggleSwitch($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		return utopia::DrawInput($fieldName,itCHECKBOX,$defaultValue,$possibleValues,$attributes,$noSubmit);
	}
}
