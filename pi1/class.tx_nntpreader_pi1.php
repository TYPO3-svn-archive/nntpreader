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
require_once(PATH_typo3.'contrib/RemoveXSS/RemoveXSS.php');

/**
 * Plugin 'NNTP Reader' for the 'nntpreader' extension.
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package	TYPO3
 * @subpackage	tx_sktnntpreader
 */
class tx_nntpreader_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_nntpreader_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_nntpreader_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'nntpreader';	// The extension key.
	var $pi_checkCHash = true;
	var $threadsPerPage;
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pid=intval($this->cObj->data['pages']);
		
		#t3lib_div::debug($GLOBALS['TSFE']->type,'debug'); 
		 
		
		$this->server=$this->pi_getRecord('tx_nntpreader_server',1);
		$this->threadsPerPage = $this->conf['threadsPerPage'] ? intval($this->conf['templateFile']) : 25;
		
		
		$tmpl=$this->conf['templateFile'] ? $this->conf['templateFile'] : 'typo3conf/ext/nntpreader/res/template.html';
		$this->uploadDir='uploads/tx_nntpreader/';    
		$this->template=$this->cObj->fileResource($tmpl);
		
		#t3lib_div::debug($this->template,'debug'); 
		
		if($GLOBALS['TSFE']->type==111) {
			return $this->rssfeed();
		} 
		
		
		
		$GLOBALS['TSFE']->additionalHeaderData[] = '<link rel="stylesheet" type="text/css" href="'.($this->conf['cssFile'] ? $this->conf['cssFile'] : 'typo3conf/ext/nntpreader/res/nntpreader.css').'" />';
		
		
		
		if($this->conf['showCronStat']) {
            $content = $this->cronstat();
        } elseif($this->conf['showLastPosts']) {
            $content = $this->LastPosts();
        } elseif($this->conf['showLastThreads']) {
			$content = $this->LastPosts(1);
		} else {
		
			if($this->piVars['search']) {
				$content=$this->search();
			} elseif($this->piVars['nid'] && $this->piVars['mid']) {
				$content=$this->showThread();
			} elseif($this->piVars['nid'] && !$this->piVars['tid']) {
				$content = $this->showSingleNewsgroup();
			} else {
				$content = $this->showNewsgroups();
			}
			
			
		}
		return $this->pi_wrapInBaseClass($content);
	}
	
	private function showNewsgroups() {
		
		$subpart=$this->cObj->getSubpart($this->template,'###FORUM###'); 
		$rowF=$this->cObj->getSubpart($subpart,'###ROW###');
		$addWhere = isset($this->conf['category']) ? ' AND category='.intval($this->conf['category']) : '';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_nntpreader_groups',
                'deleted = 0 AND
                 hidden = 0 AND
                 pull=1'.$addWhere,
                '',
                'nntp_name'   
	    );
	    
	  
	    
	    while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $markerArray['###NG###'] = $this->pi_linkTP($row['nntp_name'].($row['new'] ? '   <img src="typo3conf/ext/nntpreader/res/new.gif" width = "40" height="24" alt="new" />' : ''),array(
            	$this->prefixId.'[nid]' => $row['uid']
            ),1,$this->conf['ngPages.'][$row['uid']]);
            $markerArray['###THREADS###'] = $row['threads']; #$this->threadCount($row['uid']);
            $markerArray['###POSTS###'] = $row['messages'];
         	$markerArray['###RSS###'] =$this->cObj->typolink('<img src="typo3conf/ext/nntpreader/res/rss-feed.gif" title="RSS 2.0 feed" />',array(
        	'parameter' => $this->conf['rssPage'].',111',
        	'additionalParams' => '&n='.$row['uid'],
        	'target' => '_blank'
        ));  
         	
         	$rows.=$this->cObj->substituteMarkerArrayCached($rowF,$markerArray,$subpartArray,$linkArray);    
	    }
        $subpartArray['###ROW###']=$rows;   
        $markerArray['###BREADCRUMB###']=$this->pi_linkTP('Search in all Newsgroups',array(
        	$this->prefixId.'[search]' => 1,
        ),0);
        
        $markerArray['###GRSS###'] = $this->cObj->typolink('<img src="typo3conf/ext/nntpreader/res/rss-feed.gif" title="RSS 2.0 feed" />',array(
        	'parameter' => $this->conf['rssPage'].',111',
        	'target' => '_blank'
        ));  
        return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	
	}
	
	private function showSingleNewsgroup() {
		
		$subpart=$this->cObj->getSubpart($this->template,'###NEWSGROUP###'); 
		$rowN=$this->cObj->getSubpart($subpart,'###ROW###');
		
		
		
		$group=$this->pi_getRecord('tx_nntpreader_groups',intval($this->piVars['nid']));
		
		if($this->piVars['newmsg']) {
			#return $this->showPostForm('',$group);
		}
		
		
		
		$count=$group['threads'];
		$pages=ceil($count/$this->threadsPerPage);
		
		$act=$this->piVars['page']?intval($this->piVars['page']):$pages;
		if($act==0) $act=$pages;
		
		
		//PageBrowser
		$markerArray['###PAGEBROWSER###'] = $this->Pagebrowser($count,$this->threadsPerPage,array(
                    $this->prefixId.'[nid]' => $group['uid'],
    			));
		
		$limit = (($pages-$act)* $this->threadsPerPage).','.$this->threadsPerPage;
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'tx_nntpreader_messages',
                'deleted = 0 AND hidden = 0 AND newsgroup='.intval($this->piVars['nid']).' AND (msgreference="" or parentid=-1)',
                '',
                'maildate desc',
                $limit   
	    );
	    
	    
	    
	    while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $markerArray['###SUBJECT###'] = $this->pi_linkTP(htmlspecialchars($row['subject']),array(
                $this->prefixId.'[nid]' => intval($this->piVars['nid']),
            	$this->prefixId.'[mid]' => $row['uid'],
            	$this->prefixId.'[page]' => $act,
            ),1);
            $count=0;
            if($row['hasChild']==1) $this->getMessageTree($row['uid'],$count,$empty,true);
            
            $markerArray['###DATE###'] = date('d.m.Y H:i',$row['maildate']);
            $markerArray['###NAME###'] = $row['namefrom'] ? $row['namefrom'] : '&lt;'.substr($row['emailfrom'],0,strpos($row['emailfrom'],'@')).'@xxxxxx'.'&gt;';
            $markerArray['###THREADS###'] = $count;
         	
         	$rows.=$this->cObj->substituteMarkerArrayCached($rowN,$markerArray,$subpartArray,$linkArray);    
	    }
        $subpartArray['###ROW###']=$rows;   
        $markerArray['###BREADCRUMB###']=$this->pi_linkTP('Netfielders Newsgroups',array(),1,$this->conf['ngPages.'][0]);
        $markerArray['###GROUP###']=$group['nntp_name'];# . ' ['.$this->threadCount($group['uid']).' Threads]';
            
        return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
		
	}
	
	private function showThread() {
		$subpart=$this->cObj->getSubpart($this->template,'###THREAD###'); 
		
		$group=$this->pi_getRecord('tx_nntpreader_groups',intval($this->piVars['nid']));
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('m.*,t.body,t.header','tx_nntpreader_messages m,tx_nntpreader_text t','t.uid=m.textuid and m.uid='.intval($this->piVars['mid']));
		$thread=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		
		
		$root=$thread['msgreference']=='' ? $thread : $this->getRootMessage($thread['parentid']);
		
		$tmp=$GLOBALS['TSFE']->ATagParams;
        if(intval($this->piVars['mid'])==$root['uid'])$GLOBALS['TSFE']->ATagParams='class="active"';
        $posterText = htmlspecialchars($root['subject']) . '<span class="thread-poster">'.$root['namefrom'].'  '.date('d.m.Y H:i',$root['maildate']).'</span>';
		$output='<ul class="msgtree"><li'.(intval($this->piVars['mid'])==$root['uid']?' class="active"':'').'>'.$this->pi_linkTP($posterText,array(
            			 $this->prefixId.'[nid]' => intval($this->piVars['nid']),
            			 $this->prefixId.'[mid]' => $root['uid'],
            			 $this->prefixId.'[page]' => $this->piVars['page']
               		),1);
        $GLOBALS['TSFE']->ATagParams=$tmp;
        
        $count=0;
		if($root['hasChild']==1) $this->getMessageTree($root['uid'],$count,$output);
		$output.='</ul>';
		
        $markerArray['###BREADCRUMB###']=$this->pi_linkTP('Netfielders Newsgroups',array(),1,$this->conf['ngPages.'][0]);
		$markerArray['###BREADCRUMB###'].=' - '.$this->pi_linkTP($group['nntp_name'],array($this->prefixId.'[nid]' => $group['uid'],$this->prefixId.'[page]' => $this->piVars['page']),1);
		
        $markerArray['###TREE###']=$output;
        $markerArray['###DATE###']=date('d.m.Y H:i',$thread['maildate']);
        $markerArray['###FROM###']=$thread['namefrom'];
        $markerArray['###FROMEMAIL###']=substr($thread['emailfrom'],0,strpos($thread['emailfrom'],'@')).'@xxxxxx'; #  $this->cObj->typolink($email,array('parameter'=>$thread['emailfrom']));
        $markerArray['###HEADER###']=''; #'htmlspecialchars($thread['header']);
        $markerArray['###MSGID###']='ID '.intval($this->piVars['mid']); #'htmlspecialchars($thread['header']);
        
        #$markerArray['###SUBJECT###']=htmlspecialchars(RemoveXSS::RemoveXSS($thread['subject']));
		$markerArray['###SUBJECT###']=htmlspecialchars($thread['subject']);

		$msgBody = $thread['body']; #RemoveXSS::RemoveXSS($thread['body']);
		$msgBody = htmlspecialchars($msgBody);
		$msgBody = preg_replace("/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i","$1http://$2",    $msgBody);
		$msgBody = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@\[\]\%\+]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</a>", $msgBody);
		$msgBody = preg_replace("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i","<a href=\"mailto:$1\">$1</a>",$msgBody);
		$markerArray['###BODY###']=nl2br($msgBody);
		
		
		if($thread['files']!='') {
			$files = explode(',',$thread['files']);
			$fileconf = $this->conf['filePreview.'];
			foreach ($files as $f) {
                $fileconf['value']=$f;
				$markerArray['###ATTACHMENTS###'] .= $this->cObj->cObjGetSingle($this->conf['filePreview'],$fileconf);
			}
		} else {
			$subpartArray['###FILES###'] = '';
		}
		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	}
	
	
	private function getMessageTree($id,&$count,&$output,$countOnly=false) {
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,subject,msgid,parentid,hasChild,namefrom,maildate',
			'tx_nntpreader_messages',
			'parentid="'.$id.'"',
			$groupBy='',
			$orderBy='maildate asc'
		);
		
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res)>0) {
			if(!$countOnly) $output.='<ul>';
			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
               $count++;
               if(!$countOnly) {
               	   $tmp=$GLOBALS['TSFE']->ATagParams;
               	   if(intval($this->piVars['mid'])==$row['uid'])$GLOBALS['TSFE']->ATagParams='class="active"';
               		$reText = '[RE] '.$row['namefrom'].'  '.date('d.m.Y H:i',$row['maildate']);
               		$output.='<li>'.$this->pi_linkTP($reText,array(
            			 $this->prefixId.'[nid]' => intval($this->piVars['nid']),
            			 $this->prefixId.'[mid]' => $row['uid'],
            			 $this->prefixId.'[page]' => $this->piVars['page']
               		),1);
               		$GLOBALS['TSFE']->ATagParams=$tmp;
               }
               if($row['hasChild']==1) {
               	   $this->getMessageTree($row['uid'],$count,$output,$countOnly);
			   }
			   if(!$countOnly) $output.='</li>';
			}
			if(!$countOnly) $output.='</ul></li>';
		}
		
	}
	
	private function getRootMessage($id) {
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_nntpreader_messages','uid="'.$id.'"');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if($row['msgreference']=='') 
			return $row;
		else
			return $this->getRootMessage($row['parentid']);
	}
	
	private function threadCount($groupUID) {
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)','tx_nntpreader_messages','hidden=0 and deleted=0 and newsgroup='.$groupUID.' and hasChild=0');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}
	
	private function search() {
		$content = $this->getSearchBox();
		
		
	    $sword=addslashes(str_replace("'",'',$this->piVars['sword']));
	    #t3lib_div::debug($sword,'debug'); 
        $subpart=$this->cObj->getSubpart($this->template,'###SEARCHRESULTS###'); 
		$rowN=$this->cObj->getSubpart($subpart,'###ROW###'); 
		
		if(trim($sword)!='') {
			$ng = array_combine((array)explode(',',$this->piVars['ngid']) , (array)explode(',',$this->piVars['ngname']));
			if(intval($this->piVars['newsgroup'])>0) $addWhere = ' AND newsgroup="'.intval($this->piVars['newsgroup']).'"';
			
			
			if($this->piVars['boolean']==1) {
				$sql1 = "SELECT count(*)
                FROM tx_nntpreader_text 
                WHERE MATCH (subject,body) AGAINST ('".$sword."' IN BOOLEAN MODE)$addWhere";
                
                $sql2 = "SELECT uid, tstamp,  body, parent, subject, newsgroup, MATCH (subject,body) AGAINST
	            ('".$sword."' IN BOOLEAN MODE) AS score
    			FROM tx_nntpreader_text 
    			WHERE MATCH (subject,body) AGAINST ('".$sword."' IN BOOLEAN MODE)$addWhere";
    			
			} else {
				$sql1 = "SELECT count(*)
                FROM tx_nntpreader_text 
                WHERE MATCH (subject,body) AGAINST ('".$sword."')$addWhere";
                
                $sql2 = "SELECT uid, tstamp, body, parent, subject,newsgroup, MATCH (subject,body) AGAINST
	            ('".$sword."') AS score
    			FROM tx_nntpreader_text 
    			WHERE MATCH (subject,body) AGAINST ('".$sword."')$addWhere";
    			
    		}
    		#t3lib_div::debug($sql1,'debug'); 
    		$res=$GLOBALS['TYPO3_DB']->sql_query($sql1);
    		$row=$GLOBALS['TYPO3_DB']->sql_fetch_row($res);
    		$count=$row[0];
    		
    		
    		
    		if ($count == 0) {
                $content.= 'no results.';
    			$markerArray['###COUNT###'] = 'no results';
    		} else {
    			$markerArray['###COUNT###'] = $count;
    			
    			$act=$this->piVars['page']?intval($this->piVars['page']):1;
    			if($act==0) $act=1;
    			$limit = (($act-1)* 10).',10';
    			
				//Pagebrowser
    			$markerArray['###PAGEBROWSER###'] = $this->PagebrowserAsc($count,10,array(
                    $this->prefixId.'[search]' => 1,
                    $this->prefixId.'[boolean]' => $this->piVars['boolean'],
                    $this->prefixId.'[newsgroup]' => intval($this->piVars['newsgroup']),
    				$this->prefixId.'[sword]' => $this->piVars['sword'],
    			));
		
    			$res=$GLOBALS['TYPO3_DB']->sql_query($sql2." LIMIT $limit");
    			
    			
    			
    			$i=0;
    			while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
    				$i++;
    				
    				$markerArray['###SUBJECT###'] = $this->pi_linkTP($row['subject'],array(
		                $this->prefixId.'[nid]' => $row['newsgroup'],
            			$this->prefixId.'[mid]' => $row['parent'],
			        ),1,$this->conf['ngPages.'][$row['newsgroup']]);
			        $count=0;
			        if($row['hasChild']==1) $this->getMessageTree($row['uid'],$count,$empty,true);
			        
			        $markerArray['###DATE###'] = date('d.m.Y H:i',$row['tstamp']);
			        $markerArray['###NAME###'] = $row['namefrom'] ? $row['namefrom'] : '&lt;'.$row['emailfrom'].'&gt;';
                    $markerArray['###RELEVANCE###'] = number_format($row['score'],2);
                    $markerArray['###NEWSGROUP###'] = $ng[$row['newsgroup']];
			        $markerArray['###TEXT###'] = $this->markWords(strip_tags($row['body']));
         			
         			$rows.=$this->cObj->substituteMarkerArrayCached($rowN,$markerArray,$subpartArray,$linkArray);    
				}
        		$subpartArray['###ROW###']=$rows;  
        		$content .= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
    		}
		}
		
		return $content;
	}
	
	private function markWords($text) {
		$sword=trim(strtr($this->piVars['sword'],array(
            '"' => '',
			"'" => '',
			'+' => '',
			' -' => ' ',
            '.' => ' ',
			'/' => '\/',
			'\\' => '\\\\',
		)));
		$tmp=explode(' ',$sword);
		foreach($tmp as $v) {
			$text = preg_replace('/'.$v.'/i','<span style="background:yellow;">'.$v.'</span>',$text);
		}
		return $text;	
	}
	
	private function getSearchBox() {
		$subpart=$this->cObj->getSubpart($this->template,'###SEARCH###'); 
		$category=array('General','Teams','Projects','German Usergroups','Int. Usergroups');
		$cat=-1;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,nntp_name,category','tx_nntpreader_groups', 'deleted = 0 AND hidden = 0 AND pull=1','','category,nntp_name');
	    while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            if($row['category']!=$cat) {
            	if($row['category']>0) $ngo.='</optgroup>';
            	$ngo.='<optgroup label="'.$category[$row['category']].'">';
            	$cat=$row['category'];
            }
            $ng['id'][]=$row['uid'];  
            $ng['name'][]=$row['nntp_name'];  
            $ngo.='<option value="'.$row['uid'].'"'.($this->piVars[newsgroup]==$row['uid'] ? ' selected="selected"':'').'>'.$row['nntp_name'].'</option>';
	    }
	    $ngo.='</optgroup>';
	    
        $markerArray['###HIDDEN###'].='<input type="hidden" name="tx_nntpreader_pi1[ngid]" value="'.implode(',',$ng['id']).'" />';
        $markerArray['###HIDDEN###'].='<input type="hidden" name="tx_nntpreader_pi1[ngname]" value="'.implode(',',$ng['name']).'" />';
        $markerArray['###VALUE###'] = htmlspecialchars($this->piVars['sword']);
        $markerArray['###NGS###'] = $ngo;
	    $markerArray['###B_SEL###'] = $this->piVars['boolean']==1?'selected="selected"':'';
	    
	    
		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	}
	
	private function rssfeed() {
		$subpart=$this->cObj->getSubpart($this->template,'###RSSFEED###'); 	
		$rowC=$this->cObj->getSubpart($subpart,'###CONTENT###'); 	
		
		#t3lib_div::debug($this->piVars,'debug'); 
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,nntp_name','tx_nntpreader_groups', 'deleted = 0 AND hidden = 0 AND pull=1','','');
	    while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $ng[$row['uid']]=$row;  
	    }
	    $nsg=intval(t3lib_div::_GP('n'));
		if($nsg>0) $addWhere='and m.newsgroup='.$nsg; 
	    
		#t3lib_div::debug($subpart,'rss'); 
        $markerArray['###SITE_TITLE###']= $nsg==0 ? 'Netfielders TYPO3-Newsgroups' : 'Netfielders TYPO3-Newsgroup "'.$ng[$nsg]['nntp_name'].'"';
        $markerArray['###SITE_LINK###']='http://www.steffenkamper.info/index.php?id=38';
        $markerArray['###SITE_DESCRIPTION###']='TYPO3 newslists' . ($nsg>0 ? ' - List: ' . $ng[$nsg]['nntp_name'] : '');
        $markerArray['###NEWS_COPYRIGHT###']='TYPO3';
        $markerArray['###NEWS_WEBMASTER###']='Steffen Kamper';
		$markerArray['###NEWS_LASTBUILD###']=date('d.m.Y H:i');
		
		
		
		
		
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('m.*,t.body','tx_nntpreader_messages m,tx_nntpreader_text t','t.uid=m.textuid and m.hidden=0 and m.deleted=0 '.$addWhere,$groupBy='',$orderBy='m.maildate desc',$limit='10');
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $markerArray['###NEWS_TITLE###']=$row['subject'];
			$rssUrl = $this->conf['siteUrl'] . $this->pi_linkTP_keepPIvars_url(array(
                'nid' => $row['newsgroup'],
            	'mid' => $row['uid'],
            	'page' => 1,
            ),1,'',$this->conf['ngPages.'][$row['newsgroup']]);
			
            $markerArray['###NEWS_LINK###'] = preg_replace(array('/\[/', '/\]/', '/&/'), array('%5B', '%5D', '&#38;') , $rssUrl);
			$markerArray['###NEWS_CONTENT###'] = htmlspecialchars($this->cObj->stdWrap(strip_tags($row['body']),$this->conf['body_stdWrap.']));
			
            $markerArray['###NEWS_AUTHOR###'] = htmlspecialchars($row['namefrom']);
            $markerArray['###NEWS_CATEGORY###'] = $ng[$row['newsgroup']]['nntp_name'];
			$markerArray['###NEWS_DATE###'] = date('d.m.Y H:i',$row['maildate']);
			
			$rows.=$this->cObj->substituteMarkerArrayCached($rowC,$markerArray,$subpartArray,$linkArray); 
		}
		$subpartArray['###CONTENT###']=$rows;
		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	}
	
	private function showPostForm($initText,$group,$answer=false) {
		
		$markerArray['###SPECIAL###'] = '';
		$message = htmlspecialchars(trim($this->piVars['posting']));
		
		$subpart=$this->cObj->getSubpart($this->template,'###POSTFORM###'); 
		$markerArray['###HIDDEN###']='<input type="hidden" name="no_cache" value="1" /><input type="hidden" name="tx_nntpreader_pi1[newmsg]" value="1" />';
		if(!$answer) {
			$markerArray['###LEGEND###'] = "New Message in Newsgroup ".$group['nntp_name'];
			$markerArray['###POSTING###'] = $message;
			$subject=htmlspecialchars(trim($this->piVars['subject']));
		} else {
			$markerArray['###LEGEND###'] = "Answer in Newsgroup ".$group['nntp_name'];
			$markerArray['###POSTING###'] = '';
		}
        $markerArray['###SUBMIT###'] = 'send message';
        $markerArray['###ACTION###'] = '';
		$markerArray['###SUBJECT###'] = $subject;
		
		if($this->piVars['submit']) {
			if(trim($this->piVars['posting'])=='') {
			
			} else {
				//ok, do it
				require_once(t3lib_extMgm::extPath('nntpreader').'nntp/nntp.php');
				$this->server=$this->pi_getRecord('tx_nntpreader_server',1);
				$this->nntp = new nntpreader_nntp($this->server,'');
				
				$mail=$this->composeMail($subject,$message,$group);
				
				$stream = $this->nntp->connect($group['nntp_name']);
				#$ret = imap_mail()  #imap_append($stream,$group['nntp_name'],$mail);
				if(!ret) $markerArray['###SPECIAL###'].='<p class="error">ERROR:<br />'.imap_errors().'</p>';
				$markerArray['###SPECIAL###'] .= 'Mail:<pre>'.$mail.'</pre>';
			}
		}
		
		
		
		if(intval($this->piVars['page'])>1) $markerArray['###HIDDEN###'].='<input type="hidden" name="tx_nntpreader_pi1[page]" value="'.intval($this->piVars['page']).'" />';
		
		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	}
	
	
	private function composeMail() {
		$envelope["from"]= "info@sk-typo3.de";
        $envelope["to"]  = "typo3-test@lists.netfielders.de";
		$envelope["x-mailer"]  = "TYPO3 nntpreader from support.typo3.org";
		$envelope['subject'] = $subject;
		
		/*
		$part1["type"] = TYPEMULTIPART;
		$part1["subtype"] = "mixed";

		$filename = "/tmp/imap.c.gz";
		$fp = fopen($filename, "r");
		$contents = fread($fp, filesize($filename));
		fclose($fp);

		$part2["type"] = TYPEAPPLICATION;
		$part2["encoding"] = ENCBINARY;
		$part2["subtype"] = "octet-stream";
		$part2["description"] = basename($filename);
		$part2["contents.data"] = $contents;
		*/	
		
		$part3["type"] = TYPETEXT;
		$part3["subtype"] = "plain";
		$part3["description"] = "description";
		$part3["contents.data"] = $msg."\n\n\n\t";

		#$body[1] = $part1;
		#$body[2] = $part2;
		$body[1] = $part3;
		
		$mail=imap_mail_compose($envelope, $body);
		
		//send direct
		$from = $envelope["from"];
		$newsgroup = 'typo3.test';
		$additional = '';
		
		$stream = @fsockopen('news.netfielders.de',119, $errno, $errstr,15);
		stream_set_blocking($stream,true);
        fputs($stream, "From: $from\r\n");
		fputs($stream, "Newsgroups: $newsgroup\r\n");
		fputs($stream, "Subject: $subject\r\n");
		fputs($stream, "$additional\r\n");
		fputs($stream, "\r\n$msg\r\n.\r\n");
		
		fclose($stream); 
		//imap_mail($envelope["to"],$envelope['subject'],$mail);
		
		return $errstr;
		
	}
	
	private function Pagebrowser($recordCount,$recordsPerPage,$linkParams=array()) {
		$pages = ceil($recordCount/$recordsPerPage);
		$middle = ceil(($pages-10)/2);
		$act=$this->piVars['page']?intval($this->piVars['page']):$pages;
		if($act==0) $act=1;
		$out='<span>PAGE</span>';
		
		if($middle<6) {
			//show all Pages
			
			for($i=$pages;$i>0;$i--) {
				$out.=	$i==$act ? '<span><b>'.$i.'</b></span> ' : '<span>'.$this->pi_linkTP($i,array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => $i,
				)),1).'</span> ';
				
			}
		} else {
			//show 20 Pages
			$start= $act+10>$pages ? $pages : $act+10;
			
			$out .= $start==$pages ? '' : '<span>'.$this->pi_linkTP($pages,array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => $pages,
				)),1).'</span><span>... </span>';
				
			for($i=$start;$i>$start-20;$i--) {
				if($i>0) {
					$out.=	$i==$act ? '<span><b>'.$i.'</b></span> ' : '<span>'.$this->pi_linkTP($i,array_merge(
						$linkParams,array(
						$this->prefixId.'[page]' => $i,
					)),1).'</span> ';
				}
			}
			if($i>1) $out .= '<span>... </span>'.$this->pi_linkTP('1',array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => 1,
				)),1).'</span> ';
		}
		
		return $out;
	}	
	
	private function PagebrowserAsc($recordCount,$recordsPerPage,$linkParams=array()) {
		$pages = ceil($recordCount/$recordsPerPage);
		$middle = ceil(($pages-10)/2);
		$act=$this->piVars['page']?intval($this->piVars['page']):1;
		if($act==0) $act=1;
		$out='<span>PAGE</span>';

		if($middle<6) {
			//show all Pages

			for($i=1;$i<=$pages;$i++) {
				$out.=	$i==$act ? '<span><b>'.$i.'</b></span> ' : '<span>'.$this->pi_linkTP($i,array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => $i,
				)),1).'</span> ';

			}
		} else {
			//show 20 Pages
			$start= $act-10<=0 ? 1 : $act-10;

			$out .= $start==1 ? '' : '<span>'.$this->pi_linkTP('1',array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => 1,
				)),1).'</span><span>... </span>';

			for($i=$start;$i<=$start+20;$i++) {
				if($i<=$pages) {
					$out.=	$i==$act ? '<span><b>'.$i.'</b></span> ' : '<span>'.$this->pi_linkTP($i,array_merge(
						$linkParams,array(
						$this->prefixId.'[page]' => $i,
					)),1).'</span> ';
				}
			}
			if($i<$pages) $out .= '<span>... </span>'.$this->pi_linkTP($pages,array_merge(
					$linkParams,array(
					$this->prefixId.'[page]' => $pages,
				)),1).'</span> ';
		}

		return $out;
	}
	
	private function LastPosts($threadsOnly=false) {
		
		$subpart=$this->cObj->getSubpart($this->template,'###LASTPOSTS###'); 
		$rowN=$this->cObj->getSubpart($subpart,'###ROW###');
		$addWhere = $threadsOnly ? ' and m.parentid=0' : '';
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('m.*,g.nntp_name','tx_nntpreader_messages m,tx_nntpreader_groups g','g.uid=m.newsgroup and m.hidden=0 and m.deleted=0'.$addWhere,$groupBy='',$orderBy='m.maildate desc',$limit='25');
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$markerArray['###SUBJECT###'] = $this->pi_linkTP(htmlspecialchars($row['subject']),array(
                $this->prefixId.'[nid]' => $row['newsgroup'],
            	$this->prefixId.'[mid]' => $row['uid'],
            	//$this->prefixId.'[page]' => $act,
            ),1,$this->conf['ngPages.'][$row['newsgroup']]);
            
            $markerArray['###DATE###'] = date('d.m.Y H:i',$row['maildate']);
            $markerArray['###NAME###'] = $row['namefrom'] ? $row['namefrom'] : '&lt;'.substr($row['emailfrom'],0,strpos($row['emailfrom'],'@')).'@xxxxxx'.'&gt;';
            $markerArray['###NG###'] = $this->pi_linkTP($row['nntp_name'],array(
            	$this->prefixId.'[nid]' => $row['newsgroup']
            ),1,$this->conf['ngPages.'][$row['newsgroup']]);
         	
         	$rows.=$this->cObj->substituteMarkerArrayCached($rowN,$markerArray,$subpartArray,$linkArray);    
	    }
        $subpartArray['###ROW###']=$rows;   
		$markerArray['###BREADCRUMB###'] = '';  #$this->pi_linkTP('refresh');
		$markerArray['###LATEST###'] = $threadsOnly ? 'Threads' : 'Posts';
		return $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$linkArray); 
	}
	
	private function cronstat() {
		$stat=file('fileadmin/cron.log');
		$stat = array_slice(array_reverse($stat),0,100);
		return '<p>'.$this->pi_linkTP('refresh').'</p><h3>Cronjob Log</h3><pre>'.implode("\n",$stat).'</pre>';
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/pi1/class.tx_nntpreader_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/pi1/class.tx_nntpreader_pi1.php']);
}

?>