<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/spiplistes_api');
include_spip('inc/spiplistes_api_globales');

function formulaires_modif_abonnement2_charger_dist(){
	$confirm = _request('confirm');
	$d = _request('d');
	$list = _request('list');
	$email_desabo = _request('email_desabo');
	$valeurs = array();
	$valeurs['list'] = $list;
	$valeurs['d'] = $d;
	
	if(!empty($d)) {
		// cookie recu
		
		// cherche l'abonné
		$sql_select = "id_auteur,statut,nom,email";
		$sql_result = sql_select(
			$sql_select
			, 'spip_auteurs'
			, array(
				"cookie_oubli=".sql_quote($d)
				, "statut<>".sql_quote('5poubelle')
				, "pass<>".sql_quote('')
			)
			, '', '', 1
		);
		$row = sql_fetch($sql_result);
		
		if($row) {
			// abonné trouvé
			foreach(explode(",",$sql_select) as $key) {
				$$key = $row[$key];
			}
			$valeurs['id_auteur'] = intval($id_auteur);
			$valeurs['format'] = spiplistes_format_abo_demande($id_auteur);

			// premier passage sur le formulaire...
			// recuperer le cookie de relance desabonnement, et afficher le formulaire de modif
			$valeurs['formulaire_affiche'] = '1';
		}
	} // end if($d)
	spip_log('charger','listes');
	return $valeurs;
}

function formulaires_modif_abonnement2_verifier(){
	$erreurs = array();
	spip_log('yeenenejddddddddddddddddddddddddddddddddddddd dddddddd verifier','listes');
	return $erreurs;
}

function formulaires_modif_abonnement2_traiter_dist(){
	spip_log('traiter','listes');
	$d = _request('d');
	$list = _request('list');
	$email_desabo = _request('email_desabo');
	
	// cherche l'abonné
	$sql_select = "id_auteur,statut,nom,email";
	$sql_result = sql_select(
		$sql_select
		, 'spip_auteurs'
		, array(
			"cookie_oubli=".sql_quote($d)
			, "statut<>".sql_quote('5poubelle')
			, "pass<>".sql_quote('')
		)
		, '', '', 1
	);
	$row = sql_fetch($sql_result);
	print_r($row);
	foreach(explode(",",$sql_select) as $key) {
		$$key = $row[$key];
	}
	$id_auteur = intval($id_auteur);
	$format = spiplistes_format_abo_demande($id_auteur);
	spip_log("id_auteur = $id_auteur");
	// confirme les modifications ?
		// désabonne l'auteur
		spiplistes_abonnements_desabonner_statut($id_auteur, explode(";", _SPIPLISTES_LISTES_STATUTS_TOUS));

		if(is_array($list) && count($list)) {	
			// on abonne l'auteur aux listes choisies
			if(spiplistes_abonnements_ajouter($id_auteur, $list) !== false) {
				$message_formulaire = _T('spiplistes:abonnement_modifie');
			}
		} 
		
		// maj du format de reception
		$type_abo = _request('suppl_abo'); 
		if($format != $type_abo) {
			$format = $type_abo;
			spiplistes_format_abo_modifier($id_auteur, $format);
			// affichage des modifs
			if($format == 'non') {
				$message_formulaire = _T('spiplistes:desabonnement_valid').":&nbsp;".$email;  
			}
			else {
				$message_formulaire = _T('spiplistes:abonnement_modifie');
				$message_formulaire .= "<p>"._T('spiplistes:abonnement_nouveau_format').$format."<br />";
			}
		}
		
		// detruire le cookie perso
		//spip_query("UPDATE spip_auteurs SET cookie_oubli='' WHERE cookie_oubli =".sql_quote($d));
		spiplistes_auteurs_cookie_oubli_updateq('', $d, $true);
		
		return array('editable'=>true,'message' => $message_formulaire);
}

?>