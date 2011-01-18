<?php

uJavascript::IncludeFile(dirname(__FILE__).'/jquery.lightswitch.js');

utopia::AddInputType('itYESNO',array('jQuery_lightswitch','DrawLightswitch'));
utopia::AddInputType('itONOFF',array('jQuery_lightswitch','DrawLightswitch'));

class jQuery_lightswitch {
	static $hasDrawnJS = false;
	static function DrawLightswitch($fieldName,$inputType,$defaultValue='',$possibleValues=NULL,$attributes = NULL,$noSubmit = FALSE) {
		$dir = utopia::GetRelativePath(dirname(__FILE__));
                $initOnOff = ".lightSwitch({switchImg:'$dir/switch.png',switchImgCover:'$dir/switchplate.png'})";
		$initYesNo = ".lightSwitch({switchImg:'$dir/switch-1.png',switchImgCover:'$dir/switchplate.png'})";
		if (!self::$hasDrawnJS) {
			self::$hasDrawnJS = true;
			$dir = utopia::GetRelativePath(dirname(__FILE__));
			utopia::AppendVar('script_include',<<<FIN
$(function () {
	$('.switch-onoff')$initOnOff;
	$('.switch-yesno')$initYesNo;
});
FIN
);	
		}

		preg_match('/sql\[.+\]\[(.+)\]/',$fieldName,$matches);
		$enc_name = $matches[1];

		switch ($inputType) {
			case itYESNO:
				$class = 'switch-yesno';
				$ajax = "$('[name*=$enc_name]')".$initYesNo;
				break;
			case itONOFF:
				$class = 'switch-onoff';
				$ajax = "$('[name*=$enc_name]')".$initOnOff;
				break;
			default: return;
		}
		if (!isset($attributes['class'])) $attributes['class'] = $class;
		else $attributes['class'] .= ' '.$class;

		$ajax = array_key_exists('__ajax',$_REQUEST) ? "<script>$ajax</script>" : '';
		//AjaxEcho($ajax);
		return utopia::DrawInput($fieldName,itCHECKBOX,$defaultValue,$possibleValues,$attributes,$noSubmit).$ajax;
	}
}

?>
