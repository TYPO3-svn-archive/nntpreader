<?php
if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(PATH_t3lib.'class.t3lib_cli.php');

class tx_nntpreader_cli extends t3lib_cli {

	function main($argv) {
		//check for repair references
		if($this->cli_isArg('-r')) {
			$args = $this->cli_getArgArray('-r',$argv);
			echo $this->repairReferences(intval($args[0]));
		}
		
		
	}
	
	function repairReferences($count = 100) {
		$r=array();
		$i=0;$c=0;$good=0;$bad=0;
		$time_start = microtime(true);
		$date = date('Y-m-d H:i').'   ';
		
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'msgreference',
			'tx_nntpreader_messages',
			'hidden=0 and deleted=0 and msgreference!="" and parentid=0',
			'','crdate asc',$count);
		
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$resU1=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','tx_nntpreader_messages','msgid="'.$row['msgreference'].'"');
			if($GLOBALS['TYPO3_DB']->sql_num_rows($resU1)>0) {
				$row1=$GLOBALS['TYPO3_DB']->sql_fetch_row($resU1);
				$id=intval($row1[0]);
				$res1=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_messages','uid="'.$id.'"',array('hasChild'=>1));
	            $res2=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_messages','msgreference="'.$row['msgreference'].'"',array('parentid'=>$id));
	            $good++;
			} else {
				$res1=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_messages','msgreference="'.$row['msgreference'].'"',array('parentid'=>-1));
				$bad++;
			}
		}
		
		$time_end = microtime(true);
		$time = round($time_end - $time_start,2);
		
		return $date.'Updated '.$good.' references, '.$bad.' references lost ('.$time.' sec.)'."\n";

	}
	
	function clearSpecificCache($pid,$cHash=false) {
		$addWhere = $cHash ? ' and cHash = "'.$cHash.'"' : '';
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id = '.$pid.$addWhere);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id = '.$pid.$addWhere);
	}
	
	function getNGPages() {
		return array(
			0 => 8
			1 => 84
			2 => 17
			3 => 16
			4 => 85
			5 => 15
			6 => 94
			7 => 93
			8 => 92
			9 => 108
			10 => 91
			11 => 107
			12 => 14
			13 => 90
			14 => 89
			15 => 106
			16 => 105
			17 => 104
			18 => 102
			19 => 88
			20 => 87
			21 => 86
			22 => 100
			23 => 99
			24 => 103
			25 => 109
			26 => 98
			27 => 97
			28 => 96
			29 => 95
			30 => 101
			31 => 29
			32 => 30
			33 => 31
			34 => 32
			35 => 33
			36 => 34
			37 => 110
			38 => 36
			39 => 37
			40 => 38
			41 => 39
			42 => 40
			43 => 41
			44 => 42
			45 => 43
			46 => 44
			47 => 45
			48 => 46
			49 => 47
			50 => 48
			51 => 49
			52 => 50
			53 => 13
			54 => 51
			55 => 52
			56 => 53
			57 => 54
			58 => 55
			59 => 56
			60 => 57
			61 => 12
			62 => 58
			63 => 59
			64 => 60
			65 => 61
			66 => 62
			67 => 63
			68 => 64
			69 => 11
			70 => 65
			71 => 66
			72 => 67
			73 => 10
			74 => 68
			75 => 69
			76 => 70
			77 => 9
			78 => 71
			79 => 72
			80 => 73
			81 => 74
			82 => 75
			83 => 19
			84 => 76
			85 => 18
			86 => 77
			87 => 78
			88 => 79
			89 => 80
			90 => 81
			91 => 82
			92 => 83
		);
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/class.tx_nntpreader_cli.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/class.tx_nntpreader_cli.php']);
}

	// Call the functionality
$myObj = t3lib_div::makeInstance('tx_nntpreader_cli');
$myObj->main($_SERVER["argv"]);
?>
