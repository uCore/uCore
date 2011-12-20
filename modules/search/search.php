<?php
class uSearch extends uBasicModule {
	function SetupParents() {
		$this->SetRewrite('{q}');
		uCSS::IncludeFile(dirname(__FILE__).'/search.css');
	}
	function GetUUID() { return 'search'; }
	function GetTitle() { return (isset($_GET['q']) ? $_GET['q'].' - ' : '').utopia::GetDomainName().' search'; }
	private static $recipients = array();
	static function AddSearchRecipient($module, $searchFields, $titleField, $descField) {
		if (!is_array($searchFields)) $searchFields = array($searchFields);
		self::$recipients[$module] = array($searchFields,$titleField,$descField);
	}
	function RunModule() {
		$query = isset($_GET['q']) ? $_GET['q'] : '';
		echo '<form method="GET" action="'.$this->GetURL(array()).'">Search: <input type="text" name="q" value="'.$query.'" /><input type="submit" value="Search" /></form>';

		if (!$query) return;
		echo '<h1>Search Results for '.$query.'</h1>';
		$scores = self::RunSearch($query);

		foreach ($scores as $row) {
			$score = $row[0];
			$module = $row[1];
			$pkVal = $row[2];
			$info = $row[3];
			$obj = utopia::GetInstance($module);
			$data = $obj->LookupRecord($pkVal);
			$url = $obj->GetURL($pkVal);
			$title = word_trim(html2txt($data[$info[1]]),10,true);
			$desc = word_trim(html2txt($data[$info[2]]),50,true);
			echo '<div class="searchResult"><a href="'.$url.'">'.$title.'</a><div>'.$desc.'</div></div>';
		}
	}

	static function RunSearch($q) {
		$scores = array();

		foreach (self::$recipients as $module => $info) {
			$fields = $info[0];
			$obj = utopia::GetInstance($module);
			$pk = $obj->GetPrimaryKey();
			$rows = $obj->GetRows();
			foreach ($rows as $row) {
				$score = 0;
				foreach ($fields as $field) {
					if (!isset($row[$field])) continue;
					$score += self::SearchCompareScore($_GET['q'],$row[$field]);
				}
				if ($score > 0) {
					$scores[] = array($score,$module,$row[$pk],$info);
				}
			}
		}
		array_sort_subkey($scores,0,'>');
		return $scores;
	}

	static function SplitSearchWords($string) {
		if (!is_string($string)) return $string;
		$string = strip_tags($string);
	
		$preg = '/[^a-z0-9]/i';
		$minLength = 2;
	
		// convert string into words
		$words = preg_split($preg,$string);
		//	echo 'ss';print_r($words);echo'zz';
		//	$words = array();
		//	if (strpos($preg,$string) == FALSE) $words[] = $string;
		//	else $words = preg_split($preg,$string);
	
		//$not_search_word = array('if', 'for', 'in', 'on', 'and', 'with', 'the'); //add words as needed
	
		$word_end_strip = '('.join('|',array('ing','er','ings','ers','s')).')$';
	
		foreach ($words as $i => $word) {
			//if (strlen($word) < $minLength) { unset($words[$i]); continue; }
			$words[$i] = preg_replace('/'.$word_end_strip.'/i','',$word);
		}
		return array_unique($words);
	}
	
	static function SearchCompareScore($word1,$word2, $sensativity = 75) {
		if ($word1 === true || $word2 === true) return 1;
		if (!$word1 || !$word2) return 0;
		if ($word1 === $word2) return 1;
	
		$searchwords = self::SplitSearchWords($word1);
		$subjwords = self::SplitSearchWords($word2);
	
		$searchCount = count($searchwords);
		$totalScores = 0;
	
		$distance = 0;
		$count = 0;
		$divCount = 0;
		foreach ($searchwords as $ser) {
			foreach ($subjwords as $sub) {
				$ser = strtolower($ser); $sub = strtolower($sub);
				$percent = 0;
				if (strlen($ser) === 1){
					if ($ser === $sub) $percent = 100;
					else $percent = 0;
				} else {
					similar_text($ser,$sub,$percent);
				}
				$startMatch = substr($sub,0,strlen($ser)) == $ser;
				$count++;
	
				$score = 0;
	
				if ($percent >= $sensativity) {
					$divCount++;
					$score++;
				} elseif ($startMatch) {
					$score = $score + 0.5;
				}
//				if ($percent >= 95) {
//					$score = $score +5;
//				}
				$score = $score*$percent;
	
//				echo "$ser+$sub=$score @ $percent<br>";
	
				$distance += $score / 600;
			}
		}
//		$csea = count($searchwords);
		$csub = count($subjwords);
		$distance = ($distance / $csub);
//		if (count($searchwords) == count($subjwords) && $score > (10*95))
//		$distance += 10;
//		echo "== $distance // $divCount<br>";
		//$distance = ($distance / $divCount);
		return $distance;
	}
}
