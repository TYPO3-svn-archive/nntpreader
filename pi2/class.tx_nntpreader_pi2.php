<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Steffen Kamper <info@sk-typo3.de>
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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('nntpreader').'nntp/nntp.php');
#require_once(t3lib_extMgm::extPath('nntpreader').'nntp/fetch.php');
require_once(t3lib_extMgm::extPath('nntpreader').'nntp/db.php');


/**
 * Plugin 'NNTP Admin' for the 'nntpreader' extension.
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package	TYPO3
 * @subpackage	tx_sktnntpreader
 */
class tx_nntpreader_pi2 extends tslib_pibase {
	var $prefixId      = 'tx_nntpreader_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_nntpreader_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'nntpreader';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		
		//storagePid
		$this->pid = intval($this->conf['storagePID'])>0 ? intval($this->conf['storagePID']) : 4;
		
		$this->server=$this->pi_getRecord('tx_nntpreader_server',1);
		
		$this->nntp = new nntpreader_nntp($this->server,'');
    	$this->db = new nntpreader_db($this);
    	$this->nntp->fileDir='uploads/tx_nntpreader/';
    	$this->db->pid = 4;
    	
    	$this->cs=t3lib_div::makeInstance('t3lib_cs');
        
		
		if($this->piVars['convert']) {
            $this->msg[]=$this->convert();
        }
        
		if($this->piVars['configuregroups']) {
            $this->msg[]=$this->configureGroups();
        }
        if($this->piVars['readgroups']) {
            $this->msg[]=$this->readGroups(intval($this->piVars['readgroups']));
        }
        if($this->piVars['updategroups']) {
            $this->msg[]=$this->updateGroupMsg(intval($this->piVars['updategroups']));
        }
        if($this->piVars['group']) {
            $this->msg[]=$this->showGroups(intval($this->piVars['group']));
        }
        if($this->piVars['repair']) {
            $this->msg[]=$this->repairReferences();
        }
        if($this->piVars['fetchall']) {
			$this->msg[]=$this->fetchAll();
		}
		
		
		//main menu 
		//cron
		
		
		$this->actionlinks[]=$this->pi_linkTP('Gruppen einlesen [alle]',array($this->prefixId.'[readgroups]'=>'all'));
        $this->actionlinks[]=$this->pi_linkTP('Gruppen aktualisieren',array($this->prefixId.'[updategroups]'=>'1'));
        $this->actionlinks[]=$this->pi_linkTP('News fetchen (alle Gruppen)',array($this->prefixId.'[fetchall]'=>'1'));
        $this->actionlinks[]=$this->pi_linkTP('Referenzen reparieren ('.$this->missingReferences().' ohne Referenzen)',array($this->prefixId.'[repair]'=>'1',
			));
        $this->actionlinks[]=$this->pi_linkTP('Gruppen ausw&auml;hlen (Sichtbarkeit)',array($this->prefixId.'[configuregroups]'=>'1'));
        $this->actionlinks[]=$this->pi_linkTP('Gruppen anzeigen',array($this->prefixId.'[group]'=>'all'));
		$this->actionlinks[]='&nbsp;';
		
		
		
		$content='<h3>Actions</h3><ul>';
		foreach($this->actionlinks as $al) {$content.='<li>'.$al.'</li>';}
		$content.='</ul><hr>';
		
		$content.='<h3>Messages</h3><div>';
		if($this->msg) foreach($this->msg as $m) {$content.='<div>'.$m.'</div>';}
		$content.='</div>';
		
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function convert() {
		$i=0;
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
		'*',
		'tx_nntpreader_messages',
		'hidden=0 and deleted=0 and textuid=0',
		'','uid','5000');
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$resInsert=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nntpreader_text',array(
                    'pid' => $row['pid'],
                    'crdate' => time(),
					'tstamp' => time(),
					'body' => $row['body'],
					'header' => $row['header'],
				));
				$id=$GLOBALS['TYPO3_DB']->sql_insert_id();
				$resUpdate=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_messages','uid='.$row['uid'],array('textuid'=>$id));
				$i++;
			}
		return "converted $i records.";
	}
	
	function configureGroups() {
		
		if($this->piVars['change'] && $this->piVars['pull']) {
			foreach($this->piVars['pull'] as $k=>$v) {
				$in[]=$k;
			}
			$s=implode(',',$in);
            $res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_groups','',array('pull' => 0)); 
			$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_groups','uid IN('.$s.')',array('pull' => 1)); 
			$this->msg[]='Groups were updated.';
		}
		
		$content='<h4>Configure Groups</h4><form action="" method="post"><input type="hidden" value="1" name="'.$this->prefixId.'[configuregroups]" /><table>';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_nntpreader_groups',
            'deleted = 0 AND
             hidden = 0',
            '',
            'nntp_name'   
        );
        while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $content.='<tr><td><input type="checkbox" name="'.$this->prefixId.'[pull]['.$row['uid'].']" '.($row['pull']==1?'checked="checked"':'').' /></td>';
            $content.='<td>'.$row['nntp_name'].'  ['.$row['messages'].']</td></tr>';
        }	
        $content.='</table><input type="submit" name="'.$this->prefixId.'[change]" value="save groups" /></form>';
        return $content;
            
	}
	
	function readGroups($g) {
		$readed=$this->db->saveGroups();
		#t3lib_div::debug($readed,'debug'); 
		return 'Gruppen eingelesen: '.intval($readed['new']).' neue Gruppen und '.intval($readed['updated']).' Gruppen upgedated.';
	}
	function updateGroupMsg($g) {
		
		$readed=$this->db->saveGroups();
		return 'Gruppen aktualisieren: '.intval($readed['updated']).' Gruppen upgedated.';
	}
	function showGroups($g) {
		if($g==0) {
			//alle Gruppen
			$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) c,newsgroup','tx_nntpreader_messages','hidden=0 and deleted=0','newsgroup');
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$msgCountInDB[$row['newsgroup']]=intval($row['c']);
			}
			
			
			$content='<h4>All Groups</h4><ul>';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_nntpreader_groups',
                'deleted = 0 AND
                 hidden = 0 AND
                 pull=1',
                '',
                'nntp_name'   
            );
            while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            	$in=intval($msgCountInDB[$row['uid']]);
            	$out=$row['messages'];
            	$groupLink=$this->pi_linkTP($row['name'],array($this->prefixId.'[group]'=>$row['uid']));
            	$content.='<li>'.$groupLink.'  <span'.($in<$out?' style="color:red;"':'').'>['.$in.' from '.$out.']</span></li>';
            }
            $content.='</ul>';
		} else {
			$group=$this->pi_getRecord('tx_nntpreader_groups',$g);
			
			if($this->piVars['test']) {
                if($this->nntp->connect($group['nntp_name'])) {
                    $start = intval($this->piVars['test']);
                	$msg = $this->nntp->getMessage($group,$start);
                    t3lib_div::debug($msg,'debug'); 
              
                    
                    $content.='fetched test-messages (ID: '.$msg->uid.')';
                    $group=$this->pi_getRecord('tx_nntpreader_groups',$g);
                    
                    
                }
            }
            if(intval($this->piVars['number'])>0) {
            	$uid=intval($this->piVars['number']);
				
				$m=$this->pi_getRecord('tx_nntpreader_messages',$uid);
				#t3lib_div::debug($m,'debug'); 
                $n=$m['msguid']; #$m['msgnumber']-1;
				$n=$m['msgnumber']-1;
				$textuid = $m['textuid'];
				 if($this->nntp->connect($group['nntp_name'])) {
				 	$msg = $this->nntp->getMessage(intval($this->piVars['newsgroup']),$n,false);
				 	 
				 	if (is_array($msg->structure->parameters) && strtoupper($msg->structure->parameters[0]->attribute)=='CHARSET') {
						$charset=strtolower($msg->structure->parameters[0]->value);
						
						if($charset=='us-ascii') {
							$msg->body['text']=utf8_encode($msg->body['text']); #imap_utf8($msg->body['text']);
						} else {
							if($charset!='utf-8' ) $msg->body['text']=$this->db->cs->conv($msg->body['text'],$charset,'utf-8'); #   imap_utf8($msg->body['text']);
						}
					} else {
						$msg->body['text']=imap_utf8($msg->body['text']);
					}
					
					#$this->db->updateMessage($m,$msg);
		
				 	$resInsert=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_nntpreader_text','uid='.$textuid,array(
			            'pid' => $pid,
			            'crdate' => time(),
						'tstamp' => strtotime($msg->header->date),
						'body' => $msg->body['text'],
						'header' => $msg->headerString,
						'subject' => imap_utf8($this->cs->conv($msg->header->subject, 'iso-8859-1', 'utf-8')),
						'newsgroup' => $group['uid'],
					));
					
					t3lib_div::debug($resInsert,'UPDATE'); 
              		
              		$resInsert=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_text','uid='.$textuid,array(
			            'pid' => $pid,
			            'crdate' => time(),
						'tstamp' => strtotime($msg->header->date),
						'body' => $msg->body['text'],
						'header' => $msg->headerString,
						'subject' => imap_utf8($this->cs->conv($msg->header->subject, 'iso-8859-1', 'utf-8')),
						'newsgroup' => $group['uid'],
					));
					
                    
                    $content.='updated test-messages (ID: '.$msg->uid.')';
				 }
			}
			
			
            if($this->piVars['fetch']) {
				if($this->nntp->connect($group['nntp_name'])) {
					$references=array();
					$start = $group['last_number'];
					$lastNumber=$lastUID=0;
					$max = $this->nntp->messageCount($group['nntp_name']);
					$fetched = 0;
					for($i=$start;$i<$start+intval($this->piVars['fetch']);$i++) {
						if($i>$max) break;
						$msg = $this->nntp->getMessage($group,$i);
						if(!$msg) break;
						$this->db->saveMessage($msg,$group,$this->pid);
						$fetched++;
                        $lastNumber=$msg->number>0?$msg->number:$lastNumber;
						$lastUID=$msg->uid>0?$msg->uid:$lastUID;
						if($msg->header->references) $references[]="'".$msg->header->references."'";
                    }
					
					if($fetched > 0) {
						//update group
						$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_groups','uid='.$g,array(
							'tstamp' => time(),
	                        'last_uid' => $lastUID,
							'last_number' => $lastNumber,
						));
						//update references
						if(count($references)>0) {
							$r=implode(',',$references);
							$res=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_messages','msgid IN('.$r.')',array('hasChild'=>1));
						}
					}
					
					$content.='fetched '.$fetched.' messages';
					$group=$this->pi_getRecord('tx_nntpreader_groups',$g);
					
					
				}
			}
			
			
			
			
			$content.='<form action="" method="post">
			<input type="hidden" name="tx_nntpreader_pi2[newsgroup]" value="'.$group['uid'].'" />
			<input type="text" size="8" name="tx_nntpreader_pi2[number]" />
			<input type="submit" name="submit" value="Message updaten" />
			</form>
			';
			
			
			$content.='<h4>Gruppe "'.$group['nntp_name'].'" [ '.$msgCountInDB.' from '.$group['messages'].' Posts ]</h4><h5>Status:</h5>';
			$content.=t3lib_div::view_array($group);
			
			
        
			$this->actionlinks[]=$this->pi_linkTP('1 Test-Messages f&uuml;r Gruppe '.$group['nntp_name'].' holen',array(
				$this->prefixId.'[group]'=> $g,
				$this->prefixId.'[test]'=> 1
			));
			$this->actionlinks[]=$this->pi_linkTP('250 Messages f&uuml;r Gruppe '.$group['nntp_name'].' holen',array(
				$this->prefixId.'[group]'=> $g,
				$this->prefixId.'[fetch]'=> 250
			));
			
			
		}
		return $content;
	}
	
	private function repairReferences() {
		
		$r=array();
		$i=0;$c=0;$good=0;$bad=0;
		
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'msgreference',
			'tx_nntpreader_messages',
			'hidden=0 and deleted=0 and msgreference!="" and parentid=0',
			'','','50');
		
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
		
		$s.= 'Updated '.$good.' references, '.$bad.' references lost.<br />';
		
		return $s;
	}
	
	private function missingReferences() {
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'count(*)',
			'tx_nntpreader_messages',
			'hidden=0 and deleted=0 and msgreference!="" and parentid=0');
			
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return intval($row[0]);
	}
	
	private function fetchAll() {
		
		//alle Gruppen
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_nntpreader_groups',
            'deleted = 0 AND
             hidden = 0 AND
             pull=1',
            '',
            ''   
        );
        while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        	$groups[]=$row;
        }
        
        foreach($groups as $g) {
			if($this->nntp->connect($g['nntp_name'])) {
				
				$start = $g['last_number'];
				$lastNumber=$lastUID=0;
				$max = $this->nntp->messageCount($g['nntp_name']);
				$fetched = 0;
				for($i=$start;$i<$start+5;$i++) {
					if($i>$max) break;
					$msg = $this->nntp->getMessage($g,$i);
					if(!$msg) break;
					$this->db->saveMessage($msg,$g,$this->pid);
					$fetched++;
	                $lastNumber=$msg->number>0?$msg->number:$lastNumber;
					$lastUID=$msg->uid>0?$msg->uid:$lastUID;
	            }
				
				if($fetched > 0) {
					//update group
					$resU=$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nntpreader_groups','uid='.$g['uid'],array(
						'tstamp' => time(),
		                'last_uid' => $lastUID,
						'last_number' => $lastNumber,
					));
					
				}
				
				$content.='fetched '.$fetched.' messages in group '.$g['nntp_name'].' ['.$lastNumber.' von '.$max.']<br />';
			}
		}
		
		$content.=$this->repairReferences();
		
		return $content;
	}
	
	
	
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/pi2/class.tx_nntpreader_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/pi2/class.tx_nntpreader_pi2.php']);
}

?>
