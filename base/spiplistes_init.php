<?php 

// base/spiplistes_init.php

// Original From SPIP-Listes-V :: Id: spiplistes_init.php paladin@quesaco.org

// $LastChangedRevision$
// $LastChangedBy$
// $LastChangedDate$

///////////////////////////////////////
// A chaque appel de exec/admin_plugin, si le plugin est activ�, 
// spip d�tecte spiplistes_install() et l'appelle 3 fois :
// 1/ $action = 'test'
// 2/ $action = 'install'
// 3/ $action = 'test'
// 

include_spip('inc/spiplistes_api_globales');
include_spip('inc/spiplistes_api');

function spiplistes_install ($action) {

//spiplistes_log("spiplistes_install() <<", _SPIPLISTES_LOG_DEBUG);

	switch($action) {
		case 'test':
			// si renvoie true, c'est que la base est � jour, inutile de re-installer
			// la valise plugin "effacer tout" appara�t.
			// si renvoie false, SPIP revient avec $action = 'install' (une seule fois)
			$spiplistes_version = $GLOBALS['meta']['spiplistes_version'];
			$result = (
				$spiplistes_version
				&& ($spiplistes_version >= __plugin_real_version_get(_SPIPLISTES_PREFIX))
				&& sql_showtable("spip_listes")
				);
			//spiplistes_log("TEST: ".($result ? "OK" : "NO"), _SPIPLISTES_LOG_DEBUG);
			return($result);
			break;
		case 'install':
			if(!$GLOBALS['meta']['spiplistes_version']) {
				$result = spiplistes_base_creer();
				$str_log = "create";
			}
			else {
				// logiquement, ne devrait pas passer par l� (upgrade assur� par mes_options)
				include_spip('base/spiplistes_upgrade');
				$result = spiplistes_upgrade();
				$str_log = "upgrade";
			}
			$result = (
				$result
				&& spiplistes_initialise_spip_metas_spiplistes()
				&& spiplistes_activer_inscription_visiteurs()
				);
			$str_log = "INSTALL: $str_log " . spiplistes_str_ok_error($result);
			if(!$result) {
				// nota: SPIP ne filtre pas le r�sultat. Si retour en erreur,
				// la case � cocher du plugin sera quand m�me coch�e
				$str_log .= ": PLEASE REINSTALL PLUGIN";
			}
			else {
				echo(_T('spiplistes:_aide_install'
					, array('url_config' => generer_url_ecrire(_SPIPLISTES_EXEC_CONFIGURE))
					));
			}
			spiplistes_log($str_log);
			return($result);
			break;
		case 'uninstall':
			// est appell� lorsque "Effacer tout" dans exec=admin_plugin
			$result = spiplistes_vider_tables();
			spiplistes_log("UNINSTALL: " . spiplistes_str_ok_error($result));
			return($result);
			break;
		default:
			break;
	}
}


function spiplistes_base_creer () {

//spiplistes_log("spiplistes_base_creer() <<", _SPIPLISTES_LOG_DEBUG);

	// demande � SPIP de cr�er les tables (base/create.php)
	include_spip('base/create');
	include_spip('base/abstract_sql');
	include_spip('base/db_mysql');
	include_spip('base/spiplistes_tables');
	creer_base();
	spiplistes_log("INSTALL: database creation");

	ecrire_meta('spiplistes_version', __plugin_real_version_get(_SPIPLISTES_PREFIX));

	$spiplistes_base_version = __plugin_real_version_base_get(_SPIPLISTES_PREFIX);
	ecrire_meta('spiplistes_base_version', $spiplistes_base_version);
	spiplistes_ecrire_metas();
	
	$spiplistes_base_version = $GLOBALS['meta']['spiplistes_base_version'];

	return($spiplistes_base_version);
}


function spiplistes_initialise_spip_metas_spiplistes ($reinstall = false) {

	$spiplistes_current_version =  __plugin_current_version_get(_SPIPLISTES_PREFIX);
	$spiplistes_real_version = __plugin_real_version_get(_SPIPLISTES_PREFIX);
	$opt_simuler_envoi = spiplistes_pref_lire('opt_simuler_envoi');
	$opt_simuler_envoi = ($opt_simuler_envoi == 'oui') ? 'oui' : 'non';
	
	if(
		// si premi�re install...
		!$spiplistes_current_version
		||
		(
		// ou mise � jour...
			(
				$spiplistes_current_version 
				|| ($opt_simuler_envoi == 'non')
			)
			&& ($spiplistes_current_version < $spiplistes_real_version)
		)
	) {
	// ...passe en simulation d'envoi
		$opt_simuler_envoi = "oui";
	}

	if(!isset($GLOBALS['meta'][_SPIPLISTES_META_PREFERENCES])) {
		$GLOBALS['meta'][_SPIPLISTES_META_PREFERENCES] = "";
	}
	//spiplistes_log("### _SPIPLISTES_META_PREFERENCES : $opt_simuler_envoi");
	__plugin_ecrire_key_in_serialized_meta ('opt_simuler_envoi', $opt_simuler_envoi, _SPIPLISTES_META_PREFERENCES);
	// les autres preferences serialis�es ('_SPIPLISTES_META_PREFERENCES') sont install�es par exec/spiplistes_config

	// autres valeurs par d�faut � l'installation
	$spiplistes_spip_metas = array(
		'spiplistes_lots' => _SPIPLISTES_LOT_TAILLE
		, 'spiplistes_charset_envoi' => _SPIPLISTES_CHARSET_ENVOI
		, 'mailer_smtp' => 'non'
		, 'abonnement_config' => 'simple'
	);
	foreach($spiplistes_spip_metas as $key => $value) {
		if($reinstall || !isset($GLOBALS['meta'][$key])) {
			ecrire_meta($key, $value);
		}
	}
	
	spiplistes_ecrire_metas();
	return(true);
}

function spiplistes_activer_inscription_visiteurs () {
	$accepter_visiteurs = $GLOBALS['meta']['accepter_visiteurs'];
	if($accepter_visiteurs != 'oui') {
		$accepter_visiteurs = 'oui';
		ecrire_meta("accepter_visiteurs", $accepter_visiteurs);
		spiplistes_ecrire_metas();
		echo "<br />"._T('spiplistes:autorisation_inscription');
		spiplistes_log("ACTIVER accepter visiteur");
	}
	return(true);
}

function spiplistes_vider_tables () {

	include_spip('base/abstract_sql');
	
	// ne supprime pas la table spip_auteurs_elargis (utilis�e par inscription2, echoppe, ... ? )
	$sql_tables = "spip_listes, spip_courriers, spip_auteurs_courriers, spip_auteurs_listes, spip_auteurs_mod_listes";
	
	spiplistes_log("DROPT TABLES ".$sql_tables);
	sql_drop_table($sql_tables, true);
	
	// effacer les metas (prefs, etc.)
	sql_delete('spip_meta', 
		"nom=".sql_quote('spiplistes_version')
		. " OR nom=".sql_quote('spiplistes_base_version')
		. " OR nom=".sql_quote('spiplistes_charset_envoi')
		. " OR nom=".sql_quote('spiplistes_lots')
		. " OR nom=".sql_quote('abonnement_config')
		. " OR nom=".sql_quote(_SPIPLISTES_META_PREFERENCES)
	);
	// recharge les metas en cache 
	spiplistes_ecrire_metas();
	
	return(true);
} //

?>