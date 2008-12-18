<?php
/**

*/

require_once(PATH_t3lib."class.t3lib_cs.php");
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');


class nntpreader_db {
	var $parent;
	
	public function __construct($parent) {
        $this->parent = $parent;
        $this->filefunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
        $this->cs=t3lib_div::makeInstance('t3lib_cs');
    }
    
    public function saveGroups($lookGroups=array()) {
    	$existGroups=array();
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('nntp_name','tx_nntpreader_groups','hidden=0 and deleted=0 '.($lookGroups?' and pull=1 ':''));
		if($res) {
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$existGroups[]=$row['nntp_name'];
			}
		}
		$groups = $this->parent->nntp->getGroups();
		$doGroups=$lookGroups ? explode(',',$lookGroups) : array();
		$action=array();
		if (is_array($groups))	{
			foreach ($groups as $group)	{
				if(!$lookGroups || ($lookGroups && in_array($group,$doGroups))) {
					$messages = $this->parent->nntp->messageCount($group);
					
					if(!in_array($group,$existGroups)) {
									
						$fields_values=array(
							'tstamp' => time(),
							'crdate' => time(),
							'name' => $group,
							'nntp_name' => $group,
							'server' => $this->parent->server['uid'],
							'messages' => $messages,
							'pull' => 0,
							'pid' => $this->pid,
						);
						$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nntpreader_groups',$fields_values,$no_quote_fields=FALSE);
						$action['new']++;
					} else {
						$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_groups','nntp_name="'.$group.'"',array(
							'tstamp' => time(),
							'messages' => $messages,
						),$no_quote_fields=FALSE);
						$action['updated']++;
					}
				}
			}
		
			return $action;
		}
    }
    
    public function updateGroups() {
    	$action=array();
    	$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('nntp_name','tx_nntpreader_groups','hidden=0 and deleted=0 and pull=1');
		if($res) {
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$messages = $this->parent->nntp->messageCount($row);
				$resU=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nntpreader_groups',array('messages'=>$messages,'tstamp'=>time()),$no_quote_fields=FALSE);
				$action['updated']++;
			}
		}
		return $action;	
    }
    
    public function updateMessage($m,$msg) {
    	$textuid=$m['textuid'];
    	
    	$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_text','uid='.$textuid,array(
			'body' => $msg->body['text'],
			'header' => $msg->headerString,
			'subject' => $this->imap_utf8($this->cs->conv($msg->header->subject, 'iso-8859-1', 'utf-8')),
    	),$no_quote_fields=FALSE);
	}
	
	
	public function saveMessage($msg,$group,$pid) {
    	#t3lib_div::debug($msg->uid.': '.$msg->header->date.' ('.strtotime($msg->header->date).') ergibt '.date('d.m.Y H:s',strtotime($msg->header->date)),'DATE'); 
    	
    	if($msg->header->references) {
    		$r=explode("<",$msg->header->references);
    		$reply="<".trim($r[count($r)-1]);
    	}
    	
    	if (is_array($msg->structure->parameters) && strtoupper($msg->structure->parameters[0]->attribute)=='CHARSET') {
			$charset=strtolower($msg->structure->parameters[0]->value);
			if($charset=='us-ascii') {
				$msg->body['text']=utf8_encode($msg->body['text']);
			} else {
				if($charset!='utf-8' ) $msg->body['text']=$this->cs->conv($msg->body['text'],$charset,'utf-8'); #   imap_utf8($msg->body['text']);
			}
		} else {
			$msg->body['text'] = $this->imap_utf8($msg->body['text']);
		}
					
    	
    	$resInsert=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nntpreader_text',array(
            'pid' => $pid,
            'crdate' => time(),
			'tstamp' => strtotime($msg->header->date),
			'body' => $msg->body['text'],
			'header' => $msg->headerString,
			'subject' => $this->imap_utf8($this->cs->conv($msg->header->subject, 'iso-8859-1', 'utf-8')),
			'newsgroup' => $group['uid'],
		));
		$id=$GLOBALS['TYPO3_DB']->sql_insert_id();
		/*
		t3lib_div::debug(array(
			'charset' => $charset,
			'from' => $msg->header->sender[0]->personal,
			'cs from' => $this->cs->conv($msg->header->sender[0]->personal, $charset, 'utf-8'),
            'imap_utf8' => imap_utf8($this->cs->conv($msg->header->sender[0]->personal, $charset, 'utf-8')),
			'utf8' => utf8_encode($this->cs->conv($msg->header->sender[0]->personal, $charset, 'utf-8')),
		),'debug'); */
    	$insertData = array(
			'pid' => $pid,
            'crdate' => time(),
			'tstamp' => time(),
			'msgid' => $msg->header->message_id,
			'msguid' => $msg->uid,
			'msgnumber' => $msg->number,
			'newsgroup' => $group['uid'],
			'emailfrom' => $msg->header->sender[0]->mailbox.'@'.$msg->header->sender[0]->host,
			'namefrom' => $this->imap_utf8($this->cs->conv($msg->header->sender[0]->personal, 'iso-8859-1', 'utf-8')),
			'subject' => $this->imap_utf8($this->cs->conv($msg->header->subject, 'iso-8859-1', 'utf-8')),
			'maildate' => strtotime($msg->header->date),
			'mailsize' => strlen($msg->header->headerString)+strlen($msg->body['text']),
			'msgreference' => $reply, #$msg->header->in_reply_to, #$msg->header->references,
			'type' => $msg->structure->type,
			'textuid' => $id,
			'files' => count($msg->files)>0 ? implode(',',$msg->files) : '',
		);
		if (is_array($msg->header->reply_to)&&is_object($rt = $msg->header->reply_to[0]))	{
			$insertData['emailreplyto'] = $rt->mailbox.'@'.$rt->host;
			$insertData['namereplyto'] = $this->imap_utf8($this->cs->conv($rt->personal, 'iso-8859-1', 'utf-8'));
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nntpreader_messages',$insertData);
		$msgID = $GLOBALS['TYPO3_DB']->sql_insert_id();
		$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_text','uid='.$id,array('parent'=>$msgID));
		
		//calc cHash and clear
		//....
		
    }
    
	public function threadCount($groupUID) {
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)','tx_nntpreader_messages','hidden=0 and deleted=0 and newsgroup='.$groupUID.' and hasChild=0');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}
	
		// Wrapper-function for defect imap_utf8 in PHP5.2.5, see http://bugs.php.net/bug.php?id=44098
	private function imap_utf8($string) {
		return iconv_mime_decode($string,0,"UTF-8");
	}
	    
}

?>