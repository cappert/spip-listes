<?php

// inc/spiplistes_api.php

/******************************************************************************************/
/* SPIP-Listes est un systeme de gestion de listes d'abonnes et d'envoi d'information     */
/* par email pour SPIP. http://bloog.net/spip-listes                                      */
/* Copyright (C) 2004 Vincent CARON  v.caron<at>laposte.net                               */
/*                                                                                        */
/* Ce programme est libre, vous pouvez le redistribuer et/ou le modifier selon les termes */
/* de la Licence Publique Generale GNU publiee par la Free Software Foundation            */
/* (version 2).                                                                           */
/*                                                                                        */
/* Ce programme est distribue car potentiellement utile, mais SANS AUCUNE GARANTIE,       */
/* ni explicite ni implicite, y compris les garanties de commercialisation ou             */
/* d'adaptation dans un but specifique. Reportez-vous a la Licence Publique Generale GNU  */
/* pour plus de details.                                                                  */
/*                                                                                        */
/* Vous devez avoir recu une copie de la Licence Publique Generale GNU                    */
/* en meme temps que ce programme ; si ce n'est pas le cas, ecrivez a la                  */
/* Free Software Foundation,                                                              */
/* Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, Etats-Unis.                   */
/******************************************************************************************/

// $LastChangedRevision$
// $LastChangedBy$
// $LastChangedDate$

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip ("inc/utils");
include_spip ("inc/filtres");    /* email_valide() */
include_spip ("inc/acces");      /* creer_uniqid() */
include_spip('inc/charsets');

include_spip('base/abstract_sql');

include_spip('inc/spiplistes_api_abstract_sql');
include_spip('inc/spiplistes_api_globales');

/* function privee
 * multi_queries mysql n'est pas en mesure de le faire en natif :-(
 * A tranformer le jour ou mysql gerera correctement le multi_query
 * Et a transformer en transanction quand spip utilisera innodb ou autre table transactionnelle
 * @param $queries : requetes separees par des ';'
 */
function __exec_multi_queries($queries) {
	$queries = trim($queries);
	if (substr($queries, -1, 1) == ';') {
		$queries = substr($queries, 0, strlen($queries)-1);
	}
	$_queries = split(';', $queries);
	while( list(,$val) = each($_queries)) {
		$res = spip_query($val);
	}
	return $res;
}

//CP-20080508: renvoie OK ou ERR entre crochet
// sert principalement pour les log
function spiplistes_str_ok_error ($statut) {
	return("[".(($statut != false) ? "OK" : "ERR")."]");
}

//
function spiplistes_singulier_pluriel_str_get ($var, $str_sing, $str_plur, $returnvar = true) {
	$result = "";
	if($var) {
		$result = (($returnvar) ? $var : "") . " " . (($var > 1) ? $str_plur : $str_sing);
	}
	return($result);
}

//CP-20080508
function spiplistes_sql_compter ($table, $sql_whereq) {
	$sql_result = intval(sql_countsel($table, $sql_whereq));
	return($sql_result);
}

// CP-20080511
function spiplistes_courriers_statut_compter ($statut='tous') {
	$sql_where = spiplistes_listes_sql_where_or(
		($statut == 'tous')
		? _SPIPLISTES_COURRIERS_STATUTS
		: $statut
		);
	return(spiplistes_sql_compter('spip_courriers', $sql_where));
}

// CP-20080510
function spiplistes_courriers_casier_premier ($sql_select, $sql_whereq) {
	return(sql_select(
			$sql_select, "spip_courriers", $sql_whereq." LIMIT 1"
		)
	);
}

//CP-20080520
// Les fonctions spiplistes_abonnements_*() concernent les abonnements
// Table cible : spip_auteurs_listes

// CP-20080324 : abonner un id_auteur a une id_liste
// CP-20080508 : ou une liste de listes ($id_liste est un tableau de (id)listes)
// CP-20090111: ajouter la date d'inscription
function spiplistes_abonnements_ajouter ($id_auteur, $id_liste) {
	$result = false;
	if(($id_auteur = intval($id_auteur)) > 0) {
		$sql_table = "spip_auteurs_listes";
		$sql_noms = "(id_auteur,id_liste,date_inscription)";
		if(is_array($id_liste)) {
			$sql_valeurs = "";
			$msg = array();
			foreach($id_liste as $id) {
				if(($id = intval($id)) > 0) {
					$sql_valeurs .= " ($id_auteur,$id,NOW()),";
					$msg[] = $id;
				}
			}
			if(!empty($sql_valeurs)) {
				$sql_valeurs = rtrim($sql_valeurs, ",");
			}
		} else if(($id_liste = intval($id_liste)) > 0) {
			$sql_valeurs = " ($id_auteur,$id_liste,NOW())";
			$msg = array($id_liste);
		}
		if($sql_valeurs) {
			$msg = "#" . implode(",#", $msg);
			if(($result = sql_insert($sql_table, $sql_noms, $sql_valeurs)) === false) {
				spiplistes_sqlerror_log ("spiplistes_abonnements_ajouter()");
			}
			else {
				spiplistes_log_api("subscribe id_auteur #$id_auteur to id_liste $msg");
			}
		}
	}
	return($result);
}

// desabonner des listes (CP-20071016)
// $listes_statuts : array (statuts des listes,..)
function spiplistes_abonnements_desabonner_statut ($id_auteur, $listes_statuts) {
	if(($id_auteur = intval($id_auteur)) && count($listes_statuts)) {
		$sql_where = "statut=".implode(" OR statut=", array_map("sql_quote", $listes_statuts));
		$sql_result = sql_select("id_liste", "spip_listes", $sql_where);
		$listes = array();
		while($row = sql_fetch($sql_result)) {
			$listes[] = intval($row['id_liste']);
		}
		if(count($listes)) {
			$sql_where = " id_auteur=".sql_quote($id_auteur)." AND id_liste IN (" . implode(",", $listes) . ")";
			sql_delete("spip_auteurs_listes", $sql_where);
		}
		return(spiplistes_format_abo_modifier($id_auteur));
	}
	return(false);
}


//CP-20080512 : supprimer des abonnes de la table des abonnements
function spiplistes_abonnements_auteurs_supprimer ($auteur_statut) {
	$auteur_statut = "statut=".sql_quote($auteur_statut);
	if(spiplistes_spip_est_inferieur_193()) { 
		$result = sql_delete("spip_auteurs_listes", 
					"WHERE id_auteur IN (SELECT id_auteur FROM spip_auteurs WHERE $auteur_statut)");
	} else {
		// Sur les precieux conseils de MM :
		// passer la requete en 2 etapes pour assurer portabilite sql
		$selection =
			sql_select("id_auteur", "spip_auteurs", $auteur_statut,'','','','','',false);
		$sql_result = sql_delete("spip_auteurs_listes", "id_auteur IN ($selection)");
		if ($sql_result === false) {
			spiplistes_sqlerror_log("abonnements_auteurs_supprimer");
		}
	}
	return($result);
}

/*
 * CP-20080330 : renvoie la liste des abonnements pour id_auteur
 * @return tableau d'id_listes. La valeur est titre si $avec_titre true, sinon id_liste
 * @param $id_auteur int
 * @param $avec_titre bool
 */
function spiplistes_abonnements_listes_auteur ($id_auteur, $avec_titre = false) {
	$result = array();
	$sql_select = array("abo.id_liste");
	$sql_from = array("spip_auteurs_listes AS abo");
	$sql_where = array();
	if($avec_titre) {
		$sql_select[] = "list.titre";
		$sql_from[] = "spip_listes AS list";
		$sql_where[] = "abo.id_liste=list.id_liste";
	}
	$sql_where[] = "abo.id_auteur=".sql_quote($id_auteur);
	$sql_result = sql_select (
		$sql_select
		, $sql_from
		, $sql_where
		);
	if ($sql_result === false) {
		spiplistes_sqlerror_log("spiplistes_abonnements_listes_auteur");
	}
	else {
		while ($row = sql_fetch($sql_result)) {
			$result[$row['id_liste']] = ($avec_titre ? $row['titre'] : $row['id_liste']);
		}
	}
	return($result);
}

// CP-20080324 : desabonner un id_auteur d'une id_liste
// CP-20080508 : ou de toutes les listes si $id_liste = 'toutes'
// CP-20090111: ou tous les abonnes si id_auteur == 'tous'
function spiplistes_abonnements_auteur_desabonner ($id_auteur, $id_liste) {
	$result = false;
	if(($id_auteur == tous) > 0) {
		$sql_where = array("id_auteur>0");
		$msg1 = "ALL";
	}
	else if (($id_auteur = intval($id_auteur)) > 0) {
		$sql_where = array("id_auteur=$id_auteur");
		$msg1 = "id_auteur #$id_auteur";
	}
	if($sql_where) {
		$sql_table = "spip_auteurs_listes";
		if($id_liste == "toutes") {
			$msg2 = "ALL";
		} else if(($id_liste = intval($id_liste)) > 0) {
			$sql_where[] = "id_liste=$id_liste";
			$msg2 = "id_liste #$id_liste";
		}
		if(($result = sql_delete($sql_table, $sql_where)) === false) {
			spiplistes_sqlerror_log("abonnements_auteur_desabonner()");
		}
		else {
			spiplistes_log_api("unsubscribe id_auteur #$id_auteur to $msg2");
		}
	}
	return($result);
}

//CP-20080512 : supprimer des abonnements
function spiplistes_abonnements_supprimer ($sql_whereq) {
	return(sql_delete('spip_auteurs_listes', $sql_whereq));
}


//CP-20080508 : dans la table des abonnements
function spiplistes_abonnements_compter ($sql_whereq = "") {
	return(spiplistes_sql_compter("spip_auteurs_listes", $sql_whereq));
}

//CP-20080520
// Les fonctions spiplistes_listes_*() concernent les listes
// Table cible : spip_listes

//CP-20080508 : dans la table des listes
function spiplistes_listes_compter ($sql_whereq = "") {
	return(spiplistes_sql_compter("spip_listes", $sql_whereq));
}

//CP-20081228
//Donner le nombre d'abonnés (pour une liste) 
//qui ont un vrai format de réception (html ou texte)
// et une adresse mail valide
function spiplistes_listes_vrais_abos_compter ($id_liste) {
	if($id_liste = intval($id_liste)) {
		// SELECT COUNT(l.id_auteur) AS nb
		// FROM spip_auteurs_listes AS l, spip_auteurs_elargis AS f, spip_auteurs AS a
		// WHERE l.id_liste=$id_liste
		//	AND l.id_auteur=f.id_auteur 
		//	AND (l.id_auteur=a.id_auteur AND LENGTH(a.email) > 3)
		//	AND (f.`spip_listes_format`='html' OR f.`spip_listes_format`='texte')
		$sql_select = array('COUNT(l.id_auteur) AS nb');
		$sql_from = array('spip_auteurs_listes AS l', 'spip_auteurs_elargis AS f', 'spip_auteurs AS a');
		$sql_where = array(
			"l.id_liste=$id_liste"
			, "l.id_auteur=f.id_auteur"
			, "(l.id_auteur=a.id_auteur AND LENGTH(a.email) > 3)"
			, "(f.`spip_listes_format`='html' OR f.`spip_listes_format`='texte')"
			);
		$sql_result = sql_select($sql_select, $sql_from, $sql_where);
		if($sql_result === false) {
			spiplistes_sqlerror_log("spiplistes_listes_vrais_abos_compter()");
		}
		if($row = sql_fetch($sql_result)) {
			$result = $row['nb'];
		}
	}
	return($result);
}

// CP-20080501
function spiplistes_listes_liste_modifier ($id_liste, $array_set) {
	return(
		sql_update(
			'spip_listes'
			, $array_set
			, "id_liste=".sql_quote($id_liste)." LIMIT 1"
		)
	);
}

// CP-20080501
function spiplistes_listes_liste_supprimer ($id_liste) {
	$sql_where = "id_liste=".sql_quote(intval($id_liste));
	return(
		sql_delete('spip_listes', $sql_where." LIMIT 1")
		&& spiplistes_mod_listes_supprimer("tous", $id_liste)
		&& sql_delete('spip_auteurs_listes', $sql_where)
	);
}

//CP-20080512
function spiplistes_listes_liste_creer ($statut, $lang, $titre, $texte, $pied_page) {
	global $connect_id_auteur;

	if($id_liste = sql_insertq('spip_listes', array(
			  'statut' => $statut
			, 'lang' => $lang
			, 'titre' => $titre
			, 'texte' => $texte
			, 'pied_page' => $pied_page
			)
		)
	) { 
		$id_liste = intval($id_liste);
		$id_auteur = intval($connect_id_auteur);
		spiplistes_mod_listes_supprimer("tous", $id_liste);
		spiplistes_mod_listes_ajouter($id_auteur, $id_liste);
		spiplistes_abonnements_auteur_desabonner("tous", $id_liste);
		spiplistes_abonnements_ajouter($id_auteur, $id_liste);
		return($id_liste);
	}
	return(false);
}

//CP-20080602
// renvoie tableau de id_auteurs abonnes a une liste
function spiplistes_listes_liste_abo_ids ($id_liste) {
	$sql_result = sql_select('id_auteur', 'spip_auteurs_listes', "id_liste=".sql_quote($id_liste), '', array('id_auteur'));
	$ids_abos = array();
	while($row = sql_fetch($sql_result)) {
		$ids_abos[] = intval($row['id_auteur']);
	}
	return($ids_abos);
}

// retourne nombre d'abonnes a une liste
// si $preciser, renvoie tableau total et formats
function spiplistes_listes_nb_abonnes_compter ($id_liste = 0, $preciser = false) {
	$id_liste = intval($id_liste);
	$sql_whereq = (($id_liste > 0) ? "id_liste=".sql_quote($id_liste) : "");
	$total = spiplistes_sql_compter ("spip_auteurs_listes", $sql_whereq);
	if($preciser) {
		$selection = 
			(spiplistes_spip_est_inferieur_193())
			? "SELECT id_auteur FROM spip_auteurs_listes " . (!empty($sql_whereq) ? "WHERE  $sql_whereq" : "")
			: sql_select("id_auteur", "spip_auteurs_listes", $sql_whereq,'','','','','',false)
			;
		$sql_result = sql_select(
			"`spip_listes_format` AS f, COUNT(*) AS n"
			, "spip_auteurs_elargis"
			, "id_auteur IN (".$selection.")"
			, "`spip_listes_format`");
		if( $sql_result === false) {
			spiplistes_sqlerror_log("listes_nb_abonnes_compter");
		}
		$formats = array('html' => 0, 'texte' => 0);
		$keys = array_keys($formats);
		while($row = sql_fetch($sql_result)) {
			if(in_array($row['f'], $keys)) {
				$formats[$row['f']] += $row['n'];
			}
		}
		return(array($total, $formats['html'], $formats['texte']));
	}
	return($total);
}

function spiplistes_desabonner_auteur ($id_auteur) {
	
}

//CP-20080509: renvoie email emetteur d'une liste
function spiplistes_listes_email_emetteur ($id_liste = 0) {
	$id_liste = intval($id_liste);
	$result = false;
	if($id_liste > 0) {
		$result = 
			sql_getfetsel(
				"email_envoi"
				, "spip_listes"
				, "id_liste=".sql_quote($id_liste)." LIMIT 1"
			);
		if($result === false) {
			spiplistes_sqlerror_log("listes_email_emetteur");
		}
	}
	if(!$result) {
		$result = spiplistes_email_from_default();
	}
	return($result);
}

//CP-20080511
function spiplistes_listes_liste_fetsel ($id_liste, $keys = "*") {
	$id_liste = intval($id_liste);
	return(sql_fetsel($keys, "spip_listes", "id_liste=".sql_quote($id_liste)." LIMIT 1"));
}

//CP-20081116
function spiplistes_listes_liste_statut ($id_liste) {
	return(spiplistes_listes_liste_fetsel($id_liste, 'statut'));
}

// CP-20080505 : renvoie array sql_where des listes publiees
function spiplistes_listes_sql_where_or ($listes) {
	return("statut=".implode(" OR statut=", array_map("sql_quote", explode(";", $listes))));
}

//taille d'une chaine sans saut de lignes ni espaces ni punct
function spiplistes_strlen($out){
	$out = preg_replace("/([[:space:]]|[[:punct:]])+/", "", $out);
	return (strlen($out));
}

//CP-20080508 : dans la queue d'envoi des courriers
function spiplistes_courriers_en_queue_compter ($sql_whereq = "") {
	// demande le nombre de courriers dans la queue
	// avec etat vide (si etat non vide, 
	// c'est que la meleuse est en train de l'envoyer)
	return(spiplistes_sql_compter("spip_auteurs_courriers", $sql_whereq));
}

/*
 * @return le nom du patron de pied
 * @param $id_liste int
 */
function spiplistes_listes_pied_patron ($id_liste) {
	$result = sql_getfetsel('pied_page', 'spip_listes', "id_liste=".sql_quote($id_liste), '','',1);
	if ($result === false) {
		spiplistes_sqlerror_log("listes_pied_patron");
	}
	return($result);
}

// CP-20080510
function spiplistes_courriers_en_queue_modifier ($array_set, $sql_whereq) {
	return(
		sql_update(
			'spip_auteurs_courriers'
			, $array_set
			, $sql_whereq
		)
	);
}

// CP-20080510
function spiplistes_courriers_en_queue_supprimer ($sql_whereq) {
	if(($result = sql_delete('spip_auteurs_courriers', $sql_whereq)) === false) {
		spiplistes_sqlerror_log("courriers_en_queue_supprimer");
	}
	return($result);
}

// CP-20080621
// la premiere etiquette sur le tas
function spiplistes_courriers_en_queue_premier ($select, $where) {
	return(
		sql_getfetsel(
				  $select
				, 'spip_auteurs_courriers'
				, $where
				, '', '', 1
			)
	);
}

//CP-20080512
// Les fonctions spiplistes_format_abo_*() concernent les formats de reception des abos
// Table cible : spip_auteurs_elargis


// suspend les abonnements d'un compte
function spiplistes_format_abo_suspendre ($id_auteur) {
	return(spiplistes_format_abo_modifier($id_auteur));
}

//CP2008111 supprimer le format d'un id_auteur
// CP-20090111: si $id_auteur == 'tous', supprimer tous les formats
function spiplistes_format_abo_supprimer ($id_auteur) {
	$sql_table = "spip_auteurs_elargis";
	if(($id_auteur = intval($id_auteur)) > 0) {
		$sql_where = "id_auteur=$id_auteur";
		$msg = "id_auteur #$id_auteur";
	}
	else if ($id_auteur == 'tous') {
		$sql_where = "id_auteur>0";
		$msg = "ALL";
	}
	if($sql_where) {
		if(($result = sql_delete("spip_auteurs_elargis", $sql_where)) === false) {
			spiplistes_sqlerror_log("format_abo_supprimer()");
		}
		else {
			spiplistes_log_api("delete format for $msg");
		}
	}
	return($result);
}


// modifier le format abonne
// si id_auteur, celui-ci uniquement
// sinon, 'tous' pour modifier globalement (uniquement ceux ayant deja un format)
function spiplistes_format_abo_modifier ($id_auteur, $format = 'non') {
	if($format = spiplistes_format_valide($format)) {
		$sql_table = "spip_auteurs_elargis";
		$sql_champs = array('`spip_listes_format`' => sql_quote($format));
		if($id_auteur=='tous') {
			// appliquer le meme format a tous les abos
			$sql_result = sql_update($sql_table, $sql_champs);
			$action = "update";
		}
		else if(($id_auteur = intval($id_auteur)) > 0) {
			if(($cur_format = spiplistes_format_abo_demande($id_auteur)) !== false) {
				// si pas d'erreur sql
				if(!$cur_format) {
					// si inexistant faire un insert 
					$sql_champs = array(
						'id_auteur' => $id_auteur
						, '`spip_listes_format`' => $format
					);
					$sql_result = sql_insertq($sql_table, $sql_champs);
					$action = "insert";
				} else {
					// sinon update
					$sql_where = "id_auteur=".sql_quote($id_auteur)." LIMIT 1"; 
					$sql_result = sql_update($sql_table, $sql_champs, $sql_where);
					$action = "update";
				}
			}
		}
		if($sql_result === false) {
			spiplistes_sqlerror_log("spiplistes_format_abo_modifier() $action $id_auteur");
		}
		else {
			$id_auteur = ($id_auteur == 'tous') ? "ALL" :  "id_auteur #$id_auteur";
			spiplistes_log_api("$action format #$format to $id_auteur");
		}
	}
	return($sql_result);
}

// renvoie le format d'abonnement d'un auteur
function spiplistes_format_abo_demande ($id_auteur) {
	$id_auteur = intval($id_auteur);
	$result = false;
	$sql_where = "id_auteur=".sql_quote($id_auteur);
	if($id_auteur > 0) {
		if(spiplistes_spip_est_inferieur_193()) {
			$result = sql_getfetsel("`spip_listes_format`", "spip_auteurs_elargis", $sql_where, '', '', 1);
		} else {
			/*
			$result = sql_fetsel("`spip_listes_format` AS format", "spip_auteurs_elargis", $sql_where);
			$result = $result['format'];
			*/
			if(($sql_result = sql_select("`spip_listes_format` AS format", "spip_auteurs_elargis", $sql_where, '', '', 1)) !== false) {
				$row = sql_fetch($sql_result);
				$result = $row['format'];
				spiplistes_log_api("current format for id_auteur #$id_auteur = $result");
			}
			else {
				spiplistes_sqlerror_log("spiplistes_format_abo_demande()");
			}
		}
		/* Code a valider. Si ok, supprimer ci-dessus.
		$GLOBALS['mysql_rappel_nom_base'] = false;
		$result = sql_getfetsel("spip_listes_format", "spip_auteurs_elargis", "id_auteur=".sql_quote($id_auteur));
		$result = spiplistes_format_valide($result);
		/**/
	}
	return($result);
}

/*
 * CP-20090111
 * liste des formats autorises
 * @return 
 * 	($idx == 'array') array (index et sa valeur identique) 
 * 	($idx == 'quoted') la valeur est sql_quote'
 * 	($idx == 'sql_where') string ligne sql_where formatee avec OR
 * @param $idx string[optional]
 */
function spiplistes_formats_autorises ($idx = 'array') {
	static $formats;
	if(!$formats) {
		$ii = explode(";", _SPIPLISTES_FORMATS_ALLOWED);
		$formats = array('array' => array_combine($ii, $ii));
		$formats['quoted'] = array_map("sql_quote", $formats['array']);
		$formats['sql_where'] = "(`spip_listes_format`=" . implode(" OR `spip_listes_format`=", $formats['quoted']).")";
	}
	return($formats[$idx]);
}

//CP-20080512
// Les fonctions spiplistes_mod_listes_*() concernent les abonnements
// Table cible : spip_auteurs_mod_listes

// renvoie ID du moderateur de la liste
// CP-20080608 : ou de toutes les listes si $id_liste = 'toutes'
// -> result du style: array[id_liste] => array(id_auteur, ...)
function spiplistes_mod_listes_get_id_auteur ($id_liste) {
	$sql_from = 'spip_auteurs_mod_listes';
	$sql_where = 
		($id_liste == "toutes")
		? ""
		: "id_liste=".sql_quote(intval($id_liste))
		;
	if($sql_result = sql_select("*", $sql_from, $sql_where)) {
		$result = array();
		while($row = sql_fetch($sql_result)) {
			$ii = $row['id_liste'];
			if(!isset($result[$ii])) {
				$result[$ii] = array();
			}
			$result[$ii][] = $row['id_auteur'];
		}
		return($result);
	}
	return(false);
}

// CP-20080503: supprime un ou + moderateurs d'une liste
// CP-20090111: ou tous les moderateurs si $id_auteur == 'tous'
function spiplistes_mod_listes_supprimer ($id_auteur, $id_liste) {
	if(($id_auteur = intval($id_auteur)) > 0) {
		$sql_where = array("id_auteur=$id_auteur");
		$msg = "id_auteur #$id_auteur";
	} else if($id_auteur == "tous") {
		$sql_where = array("id_auteur>0");
		$msg = "ALL";
	}
	if($sql_where && (($id_liste = intval($id_liste) > 0))) {
		$sql_where[] = "id_liste=$id_liste";
		if(($result = sql_delete('spip_auteurs_mod_listes', $sql_where)) !== false) {
			spiplistes_log_api("delete moderator #$id_auteur from id_liste #$id_liste");
		}
		else {
			spiplistes_sqlerror_log("mod_listes_supprimer()");
		}
	}
	return($result);
}

//CP-20080512
function spiplistes_mod_listes_ajouter ($id_auteur, $id_liste) {
	if(($id_liste = intval($id_liste) > 0)) {
		$result =
			sql_insertq('spip_auteurs_mod_listes'
				, array(
					  'id_auteur' => $id_auteur
					, 'id_liste' => $id_liste
					)
			);
		if($result !== false) {
			spiplistes_log_api("insert moderator id_auteur #$id_auteur to id_liste #$id_liste");
		}
		else {
			spiplistes_sqlerror_log("mod_listes_ajouter");
		}
	}
	return($result);
}

//CP-2080610
function spiplistes_mod_listes_compter ($id_liste) {
	$n = sql_fetch(sql_select("COUNT(*) AS n", "spip_auteurs_mod_listes", "id_liste=".sql_quote($id_liste)));
	return(($n && $n['n']) ? $n['n'] : false);
}

//CP-20080620
// renvoie tableau id_liste des listes moderees par l'auteur
function spiplistes_mod_listes_id_auteur ($id_auteur) {
	$result = false;
	if($sql_result = sql_select('id_liste', 'spip_auteurs_mod_listes', 'id_auteur='.sql_quote($id_auteur))) {
		$result = array();
		while($row = sql_fetch($sql_result)) {
			$result[] = $row['id_liste'];
		}
	}
	else if($sql_result === false) {
		spiplistes_sqlerror_log("mod_listes_id_auteur");
	}
	return($result);
}

//function spiplistes_texte_propre($texte)
// passe propre() sur un texte puis nettoie les trucs rajoutes par spip sur du html
// 	Remplace spiplistes_courrier_propre() qui est a supprimer apres verif.
function spiplistes_texte_propre($texte){
	$temp_style = ereg("<style[^>]*>[^<]*</style>", $texte, $style_reg);
	if (isset($style_reg[0])) 
		$style_str = $style_reg[0]; 
	else 
		$style_str = "";
	$texte = ereg_replace("<style[^>]*>[^<]*</style>", "__STYLE__", $texte);
	//passer propre si y'a pas de html (balises fermantes)
	if( !preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $texte) ) 
	$texte = propre($texte); // pb: enleve aussi <style>...  
	
	// Corrections complementaires
	$patterns = array();
	$replacements = array();
	// html
	$patterns[] = "#<br>#i";
	$replacements[] = "<br />";
	$patterns[] = "#<b>([^<]*)</b>#i";
	$replacements[] = '<strong>\\1</strong>';
	$patterns[] = "#<i>([^<]*)</i>#i";
	$replacements[] = '<em>\\1</em>';
	// spip class
	$patterns[] = "# class=\"spip\"#";
	$replacements[] = "";	
	
	$texte = preg_replace($patterns, $replacements, $texte);

	$texte = ereg_replace("__STYLE__", $style_str, $texte);
	
	//les liens avec double debut #URL_SITE_SPIP/#URL_ARTICLE
	$texte = ereg_replace($GLOBALS['meta']['adresse_site']."/".$GLOBALS['meta']['adresse_site'], $GLOBALS['meta']['adresse_site'], $texte);
	$texte = liens_absolus($texte);
	
	return ($texte);
}

function spiplistes_titre_propre($titre){
	$titre = spiplistes_texte_propre($titre);
	$titre = substr($titre, 0, 128); // Au cas ou copie/colle
	return($titre);
}

// donne contenu tampon au format html (CP-20071013)
// tampon_patron: nom du tampon (fichier, sans extension)
function spiplistes_tampon_html_get ($tampon_patron) {
	$contexte_patron = array();
	foreach(explode(",", _SPIPLISTES_TAMPON_CLES) as $key) {
		$contexte_patron[$key] = spiplistes_pref_lire($key);
	}
	include_spip('public/assembler');
	return(recuperer_fond(_SPIPLISTES_PATRONS_TAMPON_DIR.$tampon_patron, $contexte_patron));
}

// donne contenu lien_courrier au format html (CP-20071014)
// lien_patron: nom du tampon (fichier, sans extension)
function spiplistes_lien_courrier_html_get ($lien_patron, $url_courrier) {
	$contexte_patron = array('url_courrier'=>$url_courrier);
	include_spip('public/assembler');
	return(recuperer_fond(_SPIPLISTES_PATRONS_TETE_DIR.$lien_patron, $contexte_patron));
}

// donne contenu lien_courrier au format texte (CP-20071014)
// lien_patron: nom du lien_courrier (fichier, sans extension)
// lien_html: contenu html converti en texte si pas de contenu
function spiplistes_lien_courrier_texte_get ($lien_patron, $lien_html, $url_courrier) {
	$contexte_patron = array('url_courrier'=>$url_courrier);
	$result = false;
	$f = _SPIPLISTES_PATRONS_TETE_DIR.$tampon_patron;
	if (find_in_path($f."_texte.html")){
		$result = recuperer_fond($f, $contexte_patron);
	}
	if(!$result) {
		$result = spiplistes_courrier_version_texte($lien_html);
	}
	return($result);
}

function spiplistes_pied_de_page_liste ($id_liste = 0, $lang = false) {
	$result = false;
	if(!$lang) {
		$lang = $GLOBALS['spip_lang'];
	}
	if(($id_liste = intval($id_liste)) > 0){
		$result = sql_getfetsel('pied_page', 'spip_listes', "id_liste=".sql_quote($id_liste), '','',1);
	}
	if(!$result) {
		include_spip('public/assembler');
		$contexte_pied = array('lang'=>$lang);
		$result = recuperer_fond(_SPIPLISTES_PATRONS_PIED_DEFAUT, $contexte_pied);
	}
	return ($result);
}

function spiplistes_format_valide ($format) {
	return(in_array($format, array("non", "texte", "html")) ? $format : false);
}

//CP-20080519
// Les fonctions spiplistes_auteurs_*() concernent les auteurs
// Table cible : spip_auteurs

// CP-20080503
// soit update cookie du cookie transmis
// soit update cookie de l'email transmis
function spiplistes_auteurs_cookie_oubli_updateq ($cookie_oubli, $where, $where_is_cookie = false) {
	if(is_string($where)) {
		$where = (($where_is_cookie) ? "cookie_oubli" : "email")
			. "=" . sql_quote($where) . " LIMIT 1";
	}
	return(sql_update('spip_auteurs', array('cookie_oubli' => sql_quote($cookie_oubli)), $where));
}

// CP-20080629
function spiplistes_date_heure_valide ($date_heure) {
	$date_array = recup_date($date_heure);
	if($date_array) {
		list($annee, $mois, $jour) = $date_array;
		list($heures, $minutes, $secondes) = recup_heure($date_heure);
		return(array($annee, $mois, $jour, $heures, $minutes, $secondes));
	}
	return(false);
}

//CP-20080511
function spiplistes_auteurs_auteur_select ($sql_select, $sql_where) {
	return(sql_select($sql_select, 'spip_auteurs', $sql_where." LIMIT 1"));
}

//CP-20080511
function spiplistes_auteurs_auteur_delete ($sql_where) {
	// détruire ou mettre à la poubelle ?
	// SPIP ne détruit pas lui !
	// dans le doute...
	// if(($result = sql_delete('spip_auteurs', $sql_where." LIMIT 1")) === false) {
	if(($result = sql_update(
					"spip_auteurs"
					, array('statut' => sql_quote('5poubelle'))
					, $sql_where . " LIMIT 1"
				)) === false) {
		spiplistes_sqlerror_log("auteurs_auteur_delete");
	}
	return($result);
}

//CP-20080511
function spiplistes_auteurs_auteur_insertq ($champs_array) {
	return(sql_insertq('spip_auteurs', $champs_array));
}

//CP-20080511
// CP-20090111: utiliser l'api pour pouvoir envoyer par smtp si besoin
function spiplistes_envoyer_mail ($to, $subject, $message, $from = false, $headers = "") {
	static $opt_simuler_envoi;
	if(!$opt_simuler_envoi) {
		$opt_simuler_envoi = spiplistes_pref_lire('opt_simuler_envoi');
	}
	if(!$from) {
		$from = spiplistes_email_from_default();
	}
	if(strpos($from, "<") === false) {
		$fromname = extraire_multi($GLOBALS['meta']['nom_site']);
		if ($GLOBALS['meta']['spiplistes_charset_envoi']!=$GLOBALS['meta']['charset']){
			include_spip('inc/charsets');
			$fromname = unicode2charset(charset2unicode($fromname),$GLOBALS['meta']['spiplistes_charset_envoi']);
		}
	}
	$reply_to = "no-reply".preg_replace("|.*(@[a-z.]+)|i", "$1", email_valide($from));
	
	if($opt_simuler_envoi == 'oui') {
		spiplistes_log("!!! MAIL SIMULATION MODE !!!");
		$result = true;
	}
	else {
		include_once(_DIR_PLUGIN_SPIPLISTES.'inc/spiplistes_mail.inc.php');
		$email_a_envoyer['texte'] = new phpMail($to, $subject, ''
			, html_entity_decode($message)
			, $GLOBALS['meta']['spiplistes_charset_envoi']
			);
		$email_a_envoyer['texte']->From = $from ;
		$email_a_envoyer['texte']->FromName = $fromname ;
		$email_a_envoyer['texte']->AddCustomHeader("Errors-To: ".$from); 
		$email_a_envoyer['texte']->AddCustomHeader("Reply-To: ".$reply_to); 
		$email_a_envoyer['texte']->AddCustomHeader("Return-Path: ".$from); 
		$email_a_envoyer['texte']->SMTPKeepAlive = true;
		$result = $email_a_envoyer['texte']->send();
		$msg = "email from $from to $to";
		spiplistes_log(!$result ? "error: $msg not sent" : "$msg sent");
	}
	return($result);
}

function spiplistes_listes_statuts_periodiques () {
	static $s;
	if($s === null) {
		$s = explode(";", _SPIPLISTES_LISTES_STATUTS_PERIODIQUES);
	}
	return($s);
}

/******************************************************************************************/
/* SPIP-Listes est un systeme de gestion de listes d'abonnes et d'envoi d'information     */
/* par email pour SPIP. http://bloog.net/spip-listes                                      */
/* Copyright (C) 2004 Vincent CARON  v.caron<at>laposte.net                               */
/*                                                                                        */
/* Ce programme est libre, vous pouvez le redistribuer et/ou le modifier selon les termes */
/* de la Licence Publique Generale GNU publiee par la Free Software Foundation            */
/* (version 2).                                                                           */
/*                                                                                        */
/* Ce programme est distribue car potentiellement utile, mais SANS AUCUNE GARANTIE,       */
/* ni explicite ni implicite, y compris les garanties de commercialisation ou             */
/* d'adaptation dans un but specifique. Reportez-vous a la Licence Publique Generale GNU  */
/* pour plus de details.                                                                  */
/*                                                                                        */
/* Vous devez avoir recu une copie de la Licence Publique Generale GNU                    */
/* en meme temps que ce programme ; si ce n'est pas le cas, ecrivez a la                  */
/* Free Software Foundation,                                                              */
/* Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, Etats-Unis.                   */
/******************************************************************************************/
?>