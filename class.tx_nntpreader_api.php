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

require_once (t3lib_extMgm::extPath('lang', 'lang.php'));
require_once (t3lib_extMgm::extPath('cms', 'tslib/class.tslib_content.php'));
require_once (PATH_t3lib . 'class.t3lib_page.php');

class tx_nntpreader_api {

	/**
	 * Instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $cObj;
	protected $prefixId;
	/**
	 * Creates an instance of this class
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->prefixId = 'tx_nntpreader_pi1';
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->start('', '');
	}

	/**
	 * returns link to a given posting id
	 * 0 to 100.
	 *
	 * @param	string		$postID	ID of a posting
	 * @param	array		$conf	Configuration array
	 * @return	string		Link to posting
	 */
	public function getPostingLink($postID, $conf = null) {
		if (is_null($conf)) {
			$conf = $this->getDefaultConfig();
		}
		if (intval($postID) == 0) {
			return '';
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_nntpreader_messages', 'uid=' . intval($postID));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$lconf['parameter'] = $conf['ngPages.'][$row['newsgroup']];
		$lconf['additionalParams'] = '&' . $this->prefixId . '[nid]=' . $row['newsgroup'] . '&' . $this->prefixId . '[mid]=' . $row['uid'];
		$lconf['useCacheHash'] = 1;
		$link = $this->cObj->typolink(htmlspecialchars($row['subject']), $lconf);
		return $link;

	}

	/**
	 * Retrieves default configuration of ratings.
	 * Uses plugin.tx_ratings_pi1 from page TypoScript template
	 *
	 * @return	array		TypoScript configuration for ratings
	 */
	public function getDefaultConfig() {
		return $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nntpreader_pi1.'];
	}

	/**
	 * Implements enableFields call that can be used from regular FE and eID
	 *
	 * @param	string		$tableName	Table name
	 * @return	string		SQL
	 */
	public function enableFields($tableName) {
		if ($GLOBALS['TSFE']) {
			return $this->cObj->enableFields($tableName);
		}
		$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		/* @var $sys_page t3lib_pageSelect */
		return $sys_page->enableFields($tableName);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/class.tx_nntpreader_api.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nntpreader/class.tx_nntpreader_api.php']);
}

?>