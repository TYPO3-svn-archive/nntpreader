<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Steffen Kamper <info@sk-typo3.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once (PATH_t3lib . 'class.t3lib_basicfilefunc.php');

class nntpreader_nntp {
	var $server;
	var $group;
	var $conId;
	var $mimeTypes = array ("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
	var $encoding = array ("7BIT", "8BIT", "BINARY", "BASE64", "QUOTED-PRINTABLE", "OTHER");
	var $fileDir;
	
	public function __construct($server, $group) {
		$this->server = $server;
		$this->group = $group;
		$this->conId = $this->connect($this->group);
		$this->filefunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
	}
	
	public function connect($group = '') {
		$this->conId = @imap_open($this->msgBoxName($group), '', '', OP_ANONYMOUS | ((strlen($this->group) || strlen($group)) ? 0 : OP_HALFOPEN));
		return $this->conId;
	}
	
	public function close() {
		imap_close($this->conId);
		$this->conId = NULL;
	}
	
	public function getGroups() {
		$lists = imap_getmailboxes($this->conId, $this->msgBoxName(), '*');
		if (! is_array($lists)) {
			$this->errors[] = imap_errors();
			return false;
		}
		$ret = array ();
		foreach ($lists as $list) {
			list (, $name) = explode('}', $list->name, 2);
			$ret[] = $name;
		}
		return $ret;
	}
	
	public function messageCount($group) {
		$status = imap_status($this->conId, $this->msgBoxName($group), SA_MESSAGES);
		if (is_object($status)) {
			return $status->messages;
		}
		return - 1;
	}
	
	public function getMessage($group, $startNumber, $useID = false) {
		if ($useID) {
			$msg->uid = $startNumber;
			$msg->structure = imap_fetchstructure($this->conId, $startNumber, FT_UID);
		} else {
			$startNumber = $startNumber == 0 ? 1 : $startNumber + 1;
			$uid = intval(@imap_uid($this->conId, $startNumber));
			if (! $uid)
				return false;
			$msg->uid = $uid;
			$msg->number = $startNumber;
			$msg->structure = imap_fetchstructure($this->conId, $startNumber);
		}
		
		$headerString = imap_fetchheader($this->conId, $uid, FT_UID);
		$msg->headerString = $headerString;
		$msg->header = imap_rfc822_parse_headers($headerString);
		
		$msg->body['text'] = $this->get_part($startNumber, 'TEXT/PLAIN');
		$msg->mailSize += strlen($msg->body['text']);
		
		/*
		if (is_array($msg->structure->parameters) && strtoupper($msg->structure->parameters[0]->attribute)=='CHARSET') {
			$charset=strtolower($msg->structure->parameters[0]->value);
			if($charset!='utf-8' ) $msg->body['text']=imap_utf8($msg->body['text']);
		} else {
			$msg->body['text']=imap_utf8($msg->body['text']);
		}
		*/
		
		#t3lib_div::debug($charset,'charset'); 	
		//attachements
		$f = $this->extractFiles($msg);
		if ($f)
			$msg->file[] = $f;
		
		return $msg;
	
	}
	
	private function extractFiles(&$msg) {
		if (strpos($msg->body['text'], 'begin 666') > 0) {
			$files = $this->uudecode($msg->body['text']);
			#t3lib_div::debug($files,'files'); 
			$msg->body['text'] = substr($msg->body['text'], 0, strpos($msg->body['text'], 'begin 666') - 1);
			if (count($files) > 0) {
				foreach ($files as $f) {
					$uname = $this->filefunc->getUniqueName($f['filename'], $this->fileDir);
					if (t3lib_div::verifyFilenameAgainstDenyPattern($f['filename'])) {
						t3lib_div::writeFile($uname, $f['filedata']);
						$msg->files[] = basename($uname);
					}
				}
			}
		}
		
		//get files from part
		$contentParts = count($msg->structure->parts);
		if ($contentParts >= 2) {
			for($i = 2; $i <= $contentParts; $i ++) {
				$att[$i - 2] = imap_bodystruct($this->conId, $msg->number, $i);
				
				if (is_array($att[$i - 2]->dparameters)) {
					$filename = $att[$i - 2]->dparameters[0]->value;
					if (t3lib_div::verifyFilenameAgainstDenyPattern($filename)) {
						$content = $this->decodePart(imap_fetchbody($this->conId, $msg->number, $i), $att[$i - 2]->encoding);
						if (strlen($content)) {
							$uname = $this->filefunc->getUniqueName($filename, $this->fileDir);
							t3lib_div::writeFile($uname, $content);
							$msg->files[] = basename($uname);
							$msg->mailSize += strlen($content);
						}
					}
				}
			}
		
		}
	
	}
	
	private function decodePart($str, $encoding) {
		switch ($encoding) {
			case 1 :
				return imap_8bit($str);
				break;
			case 2 :
				return imap_binary($str);
				break;
			case 3 :
				return imap_base64($str);
				break;
			case 4 :
				return quoted_printable_decode($str);
				break;
			default :
				return $str;
				break;
		}
	}
	
	private function uudecode($input) {
		// Find all uuencoded sections
		preg_match_all("/begin ([0-7]{3}) (.+)\r?\n(.+)\r?\nend/Us", $input, $matches);
		#t3lib_div::debug($matches,'debug'); 
		for($j = 0; $j < count($matches[3]); $j ++) {
			$str = $matches[3][$j];
			$filename = $matches[2][$j];
			$fileperm = $matches[1][$j];
			$file = '';
			$str = preg_split("/\r?\n/", trim($str));
			$strlen = count($str);
			
			for($i = 0; $i < $strlen; $i ++) {
				$pos = 1;
				$d = 0;
				$len = (int) (((ord(substr($str[$i], 0, 1)) - 32) - ' ') & 077);
				while ( ($d + 3 <= $len) and ($pos + 4 <= strlen($str[$i])) ) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
					$c3 = (ord(substr($str[$i], $pos + 3, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
					
					$file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));
					$file .= chr(((($c2 - ' ') & 077) << 6) | (($c3 - ' ') & 077));
					$pos += 4;
					$d += 3;
				}
				if (($d + 2 <= $len) && ($pos + 3 <= strlen($str[$i]))) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
					$file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));
					$pos += 3;
					$d += 2;
				}
				if (($d + 1 <= $len) && ($pos + 2 <= strlen($str[$i]))) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);
					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
				}
			}
			$files[] = array ('filename' => $filename, 'fileperm' => $fileperm, 'filedata' => $file);
		}
		return $files;
	}
	
	private function get_part($msg_number, $mime_type, $structure = false, $part_number = false) {
		
		if (! $structure) {
			$structure = imap_fetchstructure($this->conId, $msg_number);
		}
		if ($structure) {
			if ($mime_type == $this->get_mime_type($structure)) {
				if (! $part_number) {
					$part_number = "1";
				}
				$text = imap_fetchbody($this->conId, $msg_number, $part_number);
				
				#t3lib_div::debug("$text ($part_number)",'get_part (text)'); 
				

				if ($structure->encoding == 3) {
					return imap_base64($text);
				} else if ($structure->encoding == 4) {
					return imap_qprint($text);
				} else {
					return $text;
				}
			}
			
			if ($structure->type == 1) /* multipart */ {
				while ( list ($index, $sub_structure) = each($structure->parts) ) {
					if (! $part_number) {
						#$part_number = "1";
					}
					if ($part_number) {
						$prefix = $part_number . '.' . ($index + 1);
					}
					#t3lib_div::debug("$msg_number | $mime_type | $part_number || $index | $prefix",'get_part'); 
					#t3lib_div::debug($sub_structure,'$sub_structure'); 
					$data = $this->get_part($msg_number, $mime_type, $sub_structure, $prefix);
					if ($data) {
						#t3lib_div::debug($data,'DATA'); 
						return $data;
					}
				}
			}
		}
		return false;
	}
	
	private function msgBoxName($group = '') {
		return '{' . $this->server['address'] . ':' . $this->server['port'] . '/nntp}' . $group;
	}
	
	private function get_mime_type($structure) {
		if ($structure->subtype) {
			return $this->mimeTypes[(int) $structure->type] . '/' . $structure->subtype;
		}
		return "TEXT/PLAIN";
	}

}

?>
