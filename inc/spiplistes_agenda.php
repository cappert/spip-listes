<?php 
	// inc/spiplistes_agenda.php
	// CP-20080621
	
// $LastChangedRevision$
// $LastChangedBy$
// $LastChangedDate$

define("_SPIPLISTES_AGENDA_PERIODE_HEBDO", 10);
define("_SPIPLISTES_AGENDA_PERIODE_MOIS", 45);
define("_SPIPLISTES_AGENDA_PERIODE_DEFAUT", _SPIPLISTES_AGENDA_PERIODE_MOIS);

define("_SPIPLISTES_AGENDA_CADRE_WIDTH", 200);
define("_SPIPLISTES_AGENDA_CADRE_PADDING", 8);
define("_SPIPLISTES_AGENDA_LOUPE_HEIGHT", 24);
define("_SPIPLISTES_AGENDA_CAPTION_HEIGHT", 16);
define("_SPIPLISTES_AGENDA_TABLE_WIDTH", (_SPIPLISTES_AGENDA_CADRE_WIDTH - (_SPIPLISTES_AGENDA_CADRE_PADDING * 2)));
define("_SPIPLISTES_AGENDA_TABLE_HEIGHT", 200);

define("_SPIPLISTES_ACTION_AGENDA", _SPIPLISTES_ACTION_PREFIX."agenda");

define("_SPIPLISTES_MIN_HEIGHT_BAR", 5);
define("_SPIPLISTES_MAX_HEIGHT_BAR", _SPIPLISTES_AGENDA_TABLE_HEIGHT);

function spiplistes_boite_agenda ($periode = false) {
	$result = ""
		. "<!-- boite agenda spiplistes -->\n"
		. debut_cadre_relief("statistiques-24.gif", true)
		. "<span class='verdana2 titre-petite-boite'>"
		. _T('spiplistes:boite_agenda_titre_').":"
		. "</span><br />"
		. "<div style='width:"._SPIPLISTES_AGENDA_CADRE_WIDTH."px;height:"
			. (_SPIPLISTES_AGENDA_LOUPE_HEIGHT 
				+ _SPIPLISTES_AGENDA_CAPTION_HEIGHT
				+ _SPIPLISTES_AGENDA_TABLE_HEIGHT
				+ (_SPIPLISTES_AGENDA_CADRE_PADDING * 2)
				)
			. "px'>\n"
		. "<div id='spiplistes_boite_agenda' style='padding:"._SPIPLISTES_AGENDA_CADRE_PADDING."px 0'>\n"
		. spiplistes_boite_agenda_contenu($periode, self(), _DIR_IMG_PACK)
		. "</div>\n"
		. "</div>\n"
		. fin_cadre_relief(true)
		. "<!-- fin boite agenda spiplistes -->\n"
		;
	return($result);
}

function spiplistes_boite_agenda_contenu ($periode, $retour, $img_pack) {
	$result = "";
	$maintenant = time();
	switch($periode) {
		case _SPIPLISTES_AGENDA_PERIODE_HEBDO:
			$loupe_img = 'loupe-plus.gif';
			$autre_periode = _SPIPLISTES_AGENDA_PERIODE_MOIS;
			break;
		case _SPIPLISTES_AGENDA_PERIODE_MOIS:
		default:
			$loupe_img = 'loupe-moins.gif';
			$autre_periode = _SPIPLISTES_AGENDA_PERIODE_HEBDO;
			$periode = _SPIPLISTES_AGENDA_PERIODE_DEFAUT;
	}

	$inventaire = spiplistes_listes_inventaire($periode);

	if($inventaire) {
		$exec_url = parametre_url($retour, 'periode_agenda', $autre_periode);
		$action_url = generer_action_auteur(_SPIPLISTES_ACTION_AGENDA, $autre_periode);
		$action_url = parametre_url($action_url, 'redirect', rawurlencode($retour));
		$result .= ""
			. "<div style='height:"._SPIPLISTES_AGENDA_LOUPE_HEIGHT."px'>\n"
			. "<a href='".$exec_url."'"
//				. " onclick=\"return(AjaxSqueeze('".$action_url."','spiplistes_boite_agenda','".$exec_url."',event))\""
				. " onclick=\"return(AjaxSqueeze('".$action_url."','spiplistes_boite_agenda','',event))\""
				. " title='"._T('spiplistes:boite_agenda_voir_jours', array('nb_jours' => $autre_periode))."'"
				. " class='agenda-loupe'"
				. " style='background-image: url(".$img_pack.$loupe_img.")'"
				. " >"
			. "</a>\n"
			. "</div>\n"
			;
	} //

	$result .= ""
		. "<div style='position:relative'>\n"
		;

	// tableau datas
	$result_datas = ""
		. "<!-- tableau agenda des datas -->\n"
		. "<table cellpadding='0' cellspacing='0' border='0' class='table-agenda'"
			. " style='width:"
				. _SPIPLISTES_AGENDA_TABLE_WIDTH . "px;"
				. "height:"
				. _SPIPLISTES_AGENDA_TABLE_HEIGHT . "px'"
			. " >\n"
		. "<caption class='verdana2'>\n"
		.	(
			$inventaire
			? _T('spiplistes:boite_agenda_legende', array('nb_jours' => $periode))
			: _T('spiplistes:pas_de_liste_prog')
			)
		. "</caption>\n"
		. "<tr>\n"
		;

	if($inventaire) {
		$max_jour = spiplistes_boitelistes_calculer_max($inventaire);
		$coef_graph = _SPIPLISTES_AGENDA_TABLE_HEIGHT / $max_jour;
		$larg_col = ceil(_SPIPLISTES_AGENDA_CADRE_WIDTH / $periode);
		function spiplistes_redate($matches) {
			$matches[1]++;
			return(_T('date_jour_'.$matches[1])." ".$matches[2]);
		}
		for($ii = 0; $ii < $periode; $ii++) {
			$date_sql = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")+$ii, date("Y")));
			$date_jour = date("w j, Y", mktime(0, 0, 0, date("m")  , date("d")+$ii, date("Y")));
			$dimanche = ($date_jour[0] == "0");
			$date_jour = preg_replace_callback("/^(\d{1,2}) (.*)$/", "spiplistes_redate", $date_jour);
			$style = ($dimanche ? "border-right:1px solid #ccc;" : "");
			$liste_graph =
				(isset($inventaire[$date_sql]) && count($inventaire[$date_sql]))
				? spiplistes_boitelistes_planning_jour($inventaire[$date_sql], $date_jour, $coef_graph)
				: ""
				;
			$result_datas .= ""
				. "<td class='bas' style='".$style."width:".$larg_col."px;height:"._SPIPLISTES_AGENDA_TABLE_HEIGHT."px'"
				. " title='$date_jour'"
				. " >\n"
				. $liste_graph
				. "</td>\n"
				;
		}
	} // 
	else {
		$result_datas .= "<td class='centrer' style='font-weight:bold;color:#ccc'>?</td>\n";
	}

	$result_datas .= ""
		. "</tr>\n"
		. "</table>\n"
		. "<!-- fin tableau agenda des datas -->\n"
		;
	
	$result_legend = "";
	if($inventaire) {
		$titre_tableau = _T('spiplistes:max_').": ".$max_jour;
		$hauteur_col = ceil(_SPIPLISTES_AGENDA_TABLE_HEIGHT / 2);
		$style_col = "border:0;height:".$hauteur_col."px;";
		$moyenne_jour = ceil($max_jour / 2);
		$result_legend = ""
			. "<!-- tableau agenda des legendes -->\n"
			. "<table cellpadding='0' cellspacing='0' border='0' class='table-agenda'"
				. " style='width:" . _SPIPLISTES_AGENDA_TABLE_WIDTH . "px;"
					. "height:"	. _SPIPLISTES_AGENDA_TABLE_HEIGHT . "px;"
					. "border:0'"
				. " >\n"
			. "<caption style='text-align:right;font-size:60%' title='".$titre_tableau."'>$titre_tableau"
				. "<caption>\n"
			. "<tr class='tr-legend'>\n"
			. "<td style='".$style_col."border:1px solid #000;border-left:0;border-right:0'>"
				. "<div>"
					. "<div class='left-legend'>$max_jour</div>"
					. "<div class='right-legend'>$max_jour</div>"
				. "</div>"
			. "</td>\n"
			. "</tr>\n"
			. "<tr class='tr-legend'>\n"
			. "<td style='$style_col'>"
				. "<div>"
					. "<div class='left-legend'>$moyenne_jour</div>"
					. "<div class='right-legend'>$moyenne_jour</div>"
				. "</div>"
			. "</td>\n"
			. "</tr>\n"
			. "</table>\n"
			. "<!-- fin tableau agenda des legendes -->\n"
			;
	}
	
	$result .= ""
		. $result_legend
		. $result_datas
		. "</div>\n"
		;
	return($result);
}

// dresser l'inventaire des listes � partir 
// d'aujourd'hui, sur $jours jours.
function spiplistes_listes_inventaire ($jours) {

	//$aujourdhui = date("Y-m-d 00:00:00");
	//$jusqua = date("Y-m-d 00:00:00", mktime(0, 0, 0, date("m")  , date("d") + ($jours - 1), date("Y")));

	$sql_result = sql_select(
		'l.id_liste, l.titre, COUNT( a.id_auteur ) AS nb_abos, date'
		, 'spip_listes AS l LEFT JOIN spip_auteurs_listes AS a ON a.id_liste = l.id_liste'
		, array(
			"date >= CURDATE()"
			, "date <= INTERVAL ".$jours." DAY + CURDATE()"
			)
		, 'l.id_liste'
	);
	if(sql_count($sql_result)) {
		$result = array();
		while($row = sql_fetch($sql_result)) {
			$date = substr($row['date'], 0, 10);
			if(!isset($result[$date])) {
				$result[$date] = array();
			}
			$result[$date][] = array(
				'id_liste' => $row['id_liste']
				, 'titre' => $row['titre']
				, 'nb_abos' => $row['nb_abos']
				, 'date' => $row['date']
			);
		}
		return($result);
	}
	return(false);
}

function spiplistes_boitelistes_planning_jour ($planning, $prefix_titre, $coef_graph) {
	$result = "";
	$ii = 0;
	foreach($planning as $liste) {
		$titre_nb_abos = 
			($liste['nb_abos'] > 0)
			? spiplistes_singulier_pluriel_str_get(
				$liste['nb_abos']
				, _T('spiplistes:nb_destinataire_sing')
				, _T('spiplistes:nb_destinataire_plur')
				)
			: _T('spiplistes:aucun_destinataire')
			;
		$titre = $prefix_titre.": ".couper($liste['titre'])." ($titre_nb_abos)";
		$height = max(_SPIPLISTES_MIN_HEIGHT_BAR, ceil($liste['nb_abos'] * $coef_graph));
		$href = generer_url_ecrire(_SPIPLISTES_EXEC_LISTE_GERER, "id_liste=".$liste['id_liste']);
		$result .= ""
			. "<a href='$href' class='a-fond-".intval($ii++ % 2)."' title='".$titre."' style='height:".$height."px'>\n"
			. "</a>\n"
			;
	}
	return($result);
}

function spiplistes_boitelistes_calculer_max ($inventaire) {
	$count = 0;
	foreach($inventaire as $jour) {
		$count = max(spiplistes_boitelistes_calculer_jour($jour), $count);
	}
//spiplistes_log("max: $count");
	return($count);
}

function spiplistes_boitelistes_calculer_jour ($jour) {
	$count = 0;
	foreach($jour as $liste) {
		$count += $liste['nb_abos'];
	}
	return($count);
}

//
?>