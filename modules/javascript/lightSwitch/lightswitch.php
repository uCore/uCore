<?php

utopia::AddInputType('itYESNO',array('jQuery_lightswitch','DrawLightswitch'));
utopia::AddInputType('itONOFF',array('jQuery_lightswitch','DrawLightswitch'));

class jQuery_lightswitch extends uBasicModule{
	public function SetupParents() {
		uJavascript::IncludeFile(dirname(__FILE__).'/jquery.lightswitch.js');
		$dir = utopia::GetRelativePath(dirname(__FILE__));
		uJavascript::IncludeText('$(function() {InitJavascript.add(function () {$(".switch-onoff:not(.switched)").addClass("switched").lightSwitch({switchImg:"'.$dir.'/switch.png",switchImgCover:"'.$dir.'/switchplate.png"});});});');
		uJavascript::IncludeText('$(function() {InitJavascript.add(function () {$(".switch-yesno:not(.switched)").addClass("switched").lightSwitch({switchImg:"'.$dir.'/switch-1.png",switchImgCover:"'.$dir.'/switchplate.png"});});});');
	}
	public function RunModule() {}
	static $hasDrawnJS = false;
	static function DrawLightswitch($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		switch ($inputType) {
			case itYESNO: $class = 'switch-yesno'; break;
			case itONOFF: $class = 'switch-onoff'; break;
		}

		if (!isset($attributes['class'])) $attributes['class'] = $class;
		else $attributes['class'] .= ' '.$class;

		return utopia::DrawInput($fieldName,itCHECKBOX,$defaultValue,$possibleValues,$attributes,$noSubmit);
	}
}
