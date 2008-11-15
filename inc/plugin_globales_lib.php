<?php
// Original From SPIP-Listes-V :: $Id: plugin_globales_lib.php paladin@quesaco.org $
// Christian PAULUS : http://www.quesaco.org/ (c) 2007
// $LastChangedRevision$
// $LastChangedBy$
// $LastChangedDate$

// librairie de fonctions et defines globales 
// ind�pendantes de tout plugin

// toutes les fonctions ici sont pr�fix�es '__' (2 tirets)
// sauf re-d�claration inc/vieilles_defs.php
// sauf d�claration des fonctions php5

if (!defined("_ECRIRE_INC_VERSION")) return;

if(defined("_PGL_PLUGIN_GLOBALES_LIB") && _PGL_PLUGIN_GLOBALES_LIB) return;
define("_PGL_PLUGIN_GLOBALES_LIB", 20080224.2026); //date.heure

// HISTORY:
// CP-20081115: integration de __plugin_html_signature dans *_presentation
// idem pour __plugin_current_svnrevision_get, __plugin_svn_revision_read
// CP-20080503: revision __boite_alerte()
// CP-20080224: ajout de __lire_meta()
// CP-20071231: __plugin_current_version_base_get compl�te par lecture plugin.xml si vb manquant dans meta
// CP-20071224: ajout de __plugin_current_svnrevision_get() et modif __plugin_html_signature()
// CP-20071222: optimisation __plugin_boite_meta_info() pour plugin en mode stable et mode dev

include_spip("inc/plugin");

if(!defined("_PGL_SYSLOG_LAN_IP_ADDRESS")) define("_PGL_SYSLOG_LAN_IP_ADDRESS", "/^192\.168\./");
if(!defined("_PGL_USE_SYSLOG_UNIX"))  define("_PGL_USE_SYSLOG_UNIX", true);



/*//////////////////////////
*/





/*
 * ne sert plus ?
 */*/
if(!function_exists('__plugin_meta_version')) {
	// renvoie le num de version du plugin lors de la derniere installation
	// present dans les metas
	function __plugin_get_meta_version($prefix) {
		$result = false;
		$info = spiplistes_get_meta_infos($prefix);
		if(isset($info['version'])) {
			$result = $info['version'];
		}
		return($result);
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__mysql_date_time')) {
	function __mysql_date_time($time = 0) {
		return(date("Y-m-d H:i:s", $time));
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__is_mysql_date')) {
	function __is_mysql_date($date) {
		$t = strtotime($date);
		return($t && ($date == __mysql_date_time($t)));
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__mysql_max_allowed_packet')) {
	// renvoie la taille maxi d'un paquet PySQL (taille d'une requ�te)
	function __mysql_max_allowed_packet() {
		$sql_result = spip_query("SHOW VARIABLES LIKE 'max_allowed_packet'");
		$row = spip_fetch_array($sql_result, SPIP_NUM);
		return($row[1]);
	}
}

/*
 * ne sert plus
 */
// si inc/vieilles_defs.php disparait ?
// CP20080224: nota: ce passage/fonction probablement a supprimer (redondant)
if(!function_exists("lire_meta")) {
	function lire_meta($key) {
		$result = 0;
		if(_FILE_CONNECT && @file_exists(_FILE_CONNECT_INS .'.php')) {
			if(!isset($GLOBALS['meta'][$key])) {
				$sql_result = @spip_query("SELECT valeur FROM spip_meta WHERE nom=".sql_quote($key)." LIMIT 1");
				if($row = spip_fetch_array($sql_result)) {
					$result = $row[$key];
					$GLOBALS['meta'][$key] = $result;
				}
			}
			else {
				$result = $GLOBALS['meta'][$key];
			}
		}
		return($result);
	}
}

// !(PHP 4 >= 4.3.0, PHP 5)
if(!function_exists("html_entity_decode")) {
	function html_entity_decode ($string, $quote_style = "", $charset = "")
	{
		// Remplace les entites numeriques
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
		// Remplace les entit�s lit�rales
		$trans_tbl = get_html_translation_table (HTML_ENTITIES);
		$trans_tbl = array_flip ($trans_tbl);
		return strtr ($string, $trans_tbl);
	}
}

if((phpversion()<5) && !function_exists("__html_entity_decode_utf8")) {
// http://fr.php.net/html_entity_decode
	// thank to: laurynas dot butkus at gmail dot com
	function __html_entity_decode_utf8($string)
	{
		 static $trans_tbl;
		
		 // replace numeric entities
		 $string = preg_replace('~&#x([0-9a-f]+);~ei', 'spiplistes_code2utf(hexdec("\\1"))', $string);
		 $string = preg_replace('~&#([0-9]+);~e', 'spiplistes_code2utf(\\1)', $string);
	
		 // replace literal entities
		 if (!isset($trans_tbl))
		 {
			  $trans_tbl = array();
			 
			  foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
					$trans_tbl[$key] = utf8_encode($val);
		 }
		
		 return strtr($string, $trans_tbl);
	} // __html_entity_decode_utf8()
	
	// Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
	// thank to: akniep at rayo dot info
	function spiplistes_code2utf($number)  {
		spiplistes_log("####");
		static $windows_illegals_chars;
		if($windows_illegals_chars === null) {
			$windows_illegals_chars = array(
				128 => 8364
	            , 129 => 160 // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
	            , 130 => 8218
	            , 131 => 402
	            , 132 => 8222
	            , 133 => 8230
	            , 134 => 8224
	            , 135 => 8225
	            , 136 => 710
	            , 137 => 8240
	            , 138 => 352
	            , 139 => 8249
	            , 140 => 338
	            , 141 => 160 // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
	            , 142 => 381
	            , 143 => 160 // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
	            , 144 => 160 // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
	            , 145 => 8216
	            , 146 => 8217
	            , 147 => 8220
	            , 148 => 8221
	            , 149 => 8226
	            , 150 => 8211
	            , 151 => 8212
	            , 152 => 732
	            , 153 => 8482
	            , 154 => 353
	            , 155 => 8250
	            , 156 => 339
	            , 157 => 160 // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
	            , 158 => 382
	            , 159 => 376
			);
		}
		
        if ($number < 0)
            return FALSE;
        if ($number < 128)
            return chr($number);
        // Removing / Replacing Windows Illegals Characters
        if ($number < 160) {
        	$number = $windows_illegals_chars[$number];
        }
       
        if ($number < 2048)
            return chr(($number >> 6) + 192) . chr(($number & 63) + 128);
        if ($number < 65536)
            return chr(($number >> 12) + 224) . chr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
        if ($number < 2097152)
            return chr(($number >> 18) + 240) . chr((($number >> 12) & 63) + 128) . chr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
       
        return(false);
    } //spiplistes_code2utf()
}

if(!function_exists('__texte_html_2_iso')) {
	function __texte_html_2_iso($string, $charset, $unspace = false) {
		$charset = strtoupper($charset);
		// html_entity_decode a qq soucis avec UTF-8
		if($charset=="UTF-8" && (phpversion()<5)) {
			$string = __html_entity_decode_utf8($string);
		}
		else {
			$string = html_entity_decode($string, ENT_QUOTES, $charset);
		}
		if($unspace) {
			// renvoie sans \r ou \n pour les boites d'alerte javascript
			$string = preg_replace("/([[:space:]])+/", " ", $string);
		}
		return ($string);
	}
}




/*
 * ne sert plus
 */
if(!function_exists('__table_items_count')) {
	function __table_items_count ($table, $key, $where='') {
		return (
			(($row = spip_fetch_array(spip_query("SELECT COUNT($key) AS n FROM $table $where"))) && $row['n'])
			? intval($row['n'])
			: 0
		);
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__table_items_get')) {
	function __table_items_get ($table, $keys, $where='', $limit='') {
		$result = array();
		$sql_result = spip_query("SELECT $keys FROM $table $where $limit");
		while($row = spip_fetch_array($sql_result)) { $result[] = $row; }
		return ($result);
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__ecrire_metas')) {
	function __ecrire_metas () {
		if(version_compare($GLOBALS['spip_version_code'],'1.9300','<')) { 
			include_spip("inc/meta");
			ecrire_metas();
		}
		return(true);
	}
}

/*
 * ne sert plus
 */
if(!function_exists('__lire_meta')) {
	function __lire_meta ($key) {
		global $meta; return $meta[$key];
	}
}


?>