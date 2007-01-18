<?php

/******************************************************************************************/
/* SPIP-listes est un syst�me de gestion de listes d'information par email pour SPIP      */
/* Copyright (C) 2004 Vincent CARON  v.caron<at>laposte.net , http://bloog.net            */
/*                                                                                        */
/* Ce programme est libre, vous pouvez le redistribuer et/ou le modifier selon les termes */
/* de la Licence Publique G�n�rale GNU publi�e par la Free Software Foundation            */
/* (version 2).                                                                           */
/*                                                                                        */
/* Ce programme est distribu� car potentiellement utile, mais SANS AUCUNE GARANTIE,       */
/* ni explicite ni implicite, y compris les garanties de commercialisation ou             */
/* d'adaptation dans un but sp�cifique. Reportez-vous � la Licence Publique G�n�rale GNU  */
/* pour plus de d�tails.                                                                  */
/*                                                                                        */
/* Vous devez avoir re�u une copie de la Licence Publique G�n�rale GNU                    */
/* en m�me temps que ce programme ; si ce n'est pas le cas, �crivez � la                  */
/* Free Software Foundation,                                                              */
/* Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, �tats-Unis.                   */
/******************************************************************************************/

function spip_listes_onglets($rubrique, $onglet){
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur, $options;
	
	echo debut_onglet();
	if ($rubrique == "messagerie"){
		echo onglet(_T('spiplistes:Historique_des_envois'), generer_url_ecrire("spip_listes"), "messagerie", $onglet, _DIR_PLUGIN_SPIPLISTES."/img_pack/stock_hyperlink-mail-and-news-24.gif");
		echo onglet(_T('spiplistes:Listes_de_diffusion'), generer_url_ecrire("listes_toutes"), "messagerie", $onglet, _DIR_PLUGIN_SPIPLISTES."/img_pack/reply-to-all-24.gif");
		echo onglet(_T('spiplistes:Suivi_des_abonnements'), generer_url_ecrire("abonnes_tous"), "messagerie", $onglet, _DIR_PLUGIN_SPIPLISTES."/img_pack/addressbook-24.gif");
	}
	echo fin_onglet();
}

function spip_listes_raccourcis(){
	global  $connect_statut;
	
	// debut des racourcis
	echo debut_raccourcis(_DIR_PLUGIN_SPIPLISTES."/img_pack/mailer_config.gif");
	
	if ($connect_statut == "0minirezo") {
		icone_horizontale(_T('spiplistes:Nouveau_courrier'), generer_url_ecrire("courrier_edit","new=oui&type=nl"), _DIR_PLUGIN_SPIPLISTES."/img_pack/stock_mail_send.gif");
		echo "</a>"; // bug icone_horizontale()
		echo "<br />" ;
		echo "<br />" ;
		
		icone_horizontale(_T('spiplistes:Nouvelle_liste_de_diffusion'), generer_url_ecrire("liste_edit","new=oui"), _DIR_PLUGIN_SPIPLISTES."/img_pack/reply-to-all-24.gif");
		echo "</a>"; // bug icone_horizontale()
		icone_horizontale(_T('spiplistes:import_export'), generer_url_ecrire("import_export"), _DIR_PLUGIN_SPIPLISTES."/img_pack/listes_inout.png");
		echo "</a>"; // bug icone_horizontale()
		
		icone_horizontale(_T('spiplistes:Configuration'), generer_url_ecrire("config"),_DIR_PLUGIN_SPIPLISTES."/img_pack/mailer_config.gif");
		echo "</a>"; // bug icone_horizontale()
	}
	echo fin_raccourcis();

	//Afficher la console d'envoi ?
	global $table_prefix;
	$qery_message = "SELECT * FROM spip_courriers AS messages WHERE statut='encour' LIMIT 0,1";
	$rsult_pile = spip_query($qery_message);
	$mssage_pile = spip_num_rows($rsult_pile);
	$mess=spip_fetch_array($rsult_pile);	
	$id_mess = $mess['id_courrier'];
	if($mssage_pile > 0 ){
		echo "<br />";
		echo debut_boite_info();
		echo "<script type='text/javascript' src='".find_in_path('javascript/autocron.js')."'></script>";
		
		echo "<div style='font-weight:bold;text-align:center'>"._T('spiplistes:envoi_en_cours')."</div>";
		echo "<div style='padding : 10px;text-align:center'><img src='../"._DIR_PLUGIN_SPIPLISTES."/img_pack/48_import.gif'></div>";
		echo "<div id='meleuse'></div>" ;
		echo "<p>"._T('spiplistes:texte_boite_en_cours')."</p>" ;
		echo "<p align='center'><a href='".generer_url_ecrire('gerer_courrier','change_statut=publie&id_message='.$id_mess)."'>["._T('annuler')."]</a></p>";
		
		
		echo fin_boite_info();
	} 

	// colonne gauche boite info
	echo "<br />" ;
	echo debut_boite_info();
	echo _T('spiplistes:_aide');
	echo fin_boite_info();
}

/**
* spiplistes_afficher_en_liste
*
* affiche des listes d'�l�ments
*
* @param string titre
* @param string image
* @param string statut
* @param string recherche
* @param string nom_position
* @return string la liste des lettres pour le statut demand�
* @author BoOz / Pierre Basson
**/
function spiplistes_afficher_en_liste($titre, $image, $element='listes', $statut, $recherche='', $nom_position='position') {
	
	global $pas, $id_auteur;
	$position = intval($_GET[$nom_position]);
	
	$clause_where = '';
	if (!empty($recherche)) {
		$recherche = addslashes($recherche);
		$clause_where.= ' AND ( titre LIKE "%'.$recherche.'%"  OR  descriptif LIKE "%'.$recherche.'%"  OR  texte LIKE "%'.$recherche.'%" )';
	}
	
	$lettres = '';
	
	if(!$pas) $pas=10 ;
	if(!$position) $position=0 ;
	
	if($element == 'listes'){
		$requete_listes = 'SELECT id_liste,
		titre,
		date
		FROM spip_listes
		WHERE statut='._q($statut).' '.$clause_where.'
		ORDER BY date DESC
		LIMIT '.intval($position).','.intval($pas).'';
	}
	
	if($element == 'messages'){
		$type='nl' ;
		if($statut=='redac')
			$statut='redac" OR statut="ready';
		if($statut=='auto'){
			$type='auto';
			$statut='publie';
		}
		if($statut=='encour')
			$type2='OR type="auto"';
	
		$requete_listes = 'SELECT id_courrier,
			titre,
			date, nb_emails_envoyes
			FROM spip_courriers
			WHERE (type='._q($type).' '.$type2.') AND statut='._q($statut).' '.$clause_where.'
			ORDER BY date DESC
			LIMIT '.intval($position).','.intval($pas).'';
	}
	
	if($element == 'abonnements'){
		if($statut=='')
			$requete_listes = 'SELECT listes.id_liste, listes.titre, listes.statut, listes.date, 							lien.id_auteur,lien.id_liste FROM  spip_abonnes_listes AS lien LEFT JOIN spip_listes AS listes  ON 				lien.id_liste=listes.id_liste WHERE lien.id_auteur="'.$id_auteur.'" AND (listes.statut ="liste" OR 				listes.statut ="inact") ORDER BY listes.date DESC LIMIT '.$position.','.$pas.'';
		else{
			$requete_listes = 'SELECT id_courrier,
			titre,
			date, nb_emails_envoyes
			FROM spip_courriers
			WHERE type='._q($type).' AND statut='._q($statut).' '.$clause_where.'
			ORDER BY date DESC
			LIMIT '.intval($position).','.intval($pas).'';
		}
	}
	
	//echo "$requete_listes";
	$resultat_aff = spip_query($requete_listes);

	
	if (@spip_num_rows($resultat_aff) > 0) {
	
	$en_liste.= "<div class='liste'>\n";
	$en_liste.= "<div style='position: relative;'>\n";
	$en_liste.= "<div style='position: absolute; top: -12px; left: 3px;'>\n";
	$en_liste.= "<img src='".$image."'  />\n";
	$en_liste.= "</div>\n";
	$en_liste.= "<div style='background-color: white; color: black; padding: 3px; padding-left: 30px; border-bottom: 1px solid #444444;' class='verdana2'>\n";
	$en_liste.= "<b>\n";
	$en_liste.= $titre;
	$en_liste.= "</b>\n";
	$en_liste.= "</div>\n";
	$en_liste.= "</div>\n";
	$en_liste.= "<table width='100%' cellpadding='2' cellspacing='0' border='0'>\n";
	
	while ($row = spip_fetch_array($resultat_aff)) {
		$titre		= $row['titre'];
		$date		= affdate($row['date']);				
		
		switch ($element){
			case "abonnements":
				$id_row = $row['id_liste'];
				$url_row	= generer_url_ecrire('gerer_liste', 'id_liste='.$id_row);
				$url_desabo	= generer_url_ecrire('abonne_edit', 'id_liste='.$id_row.'&id_auteur='.$id_auteur.'&suppr_auteur='.$id_auteur);
				break;
			case "listes":
				$id_row = $row['id_liste'];
				$url_row	= generer_url_ecrire('gerer_liste', 'id_liste='.$id_row);
				break;
			default:
				$id_row	= $row['id_courrier'];			
				$nb_emails_envoyes	= $row['nb_emails_envoyes'];
				$url_row	= generer_url_ecrire('gerer_courrier', 'id_message='.$id_row);
		}
		
		$en_liste.= "<tr class='tr_liste'>\n";
		$en_liste.= "<td width='11'>";
		switch ($statut) {
			case 'brouillon':
				$en_liste.= "<img src='img_pack/puce-blanche.gif' alt='puce-blanche' border='0' style='margin: 1px;' />";
				break;
			case 'publie':
				$en_liste.= "<img src='img_pack/puce-verte.gif' alt='puce-verte' border='0' style='margin: 1px;' />";
				break;
			case 'envoi_en_cours':
				$en_liste.= "<img src='img_pack/puce-orange.gif' alt='puce-orange' border='0' style='margin: 1px;' />";
				break;
		}
		$en_liste.= "</td>";
		$en_liste.= "<td class='arial2'>\n";
		$en_liste.= "<div>\n";
		$en_liste.= "<a href=\"".$url_row."\" dir='ltr' style='display:block;'>\n";
		$en_liste.= $titre;
		
		if ($element == 'listes') {
			$nb_abo= spip_num_rows(spip_query("SELECT id_auteur FROM spip_abonnes_listes WHERE id_liste='$id_row'"));
			$nb_abo = ($nb_abo>1)? $nb_abo." abonn&eacute;s" : $nb_abo." abonn&eacute;";
			
			$en_liste.= " <font size='1' color='#666666' dir='ltr'>\n";
			$en_liste.= "(".$nb_abo.")\n";
			$en_liste.= "</font>\n";
		}
		
		if($nb_emails_envoyes>0){
			$en_liste.= " <font size='1' color='#666666' dir='ltr'>\n";
			$en_liste.= "(".$nb_emails_envoyes.")\n";
			$en_liste.= "</font>\n";
		}
		
		$en_liste.= "</a>\n";
		$en_liste.= "</div>\n";
		$en_liste.= "</td>\n";
		
		switch ($element){
			case "abonnements":
				$en_liste.= "<td width='120' class='arial1'><a href=\"".$url_desabo."\" dir='ltr' style='display:block;'>D&eacute;sabonnement</a></td>\n";
				break;
			default:
				$en_liste.= "<td width='120' class='arial1'>".$date."</td>\n";
		}
		
		$en_liste.= "<td width='50' class='arial1'><b>N&nbsp;".$id_row."</b></td>\n";
		$en_liste.= "</tr>\n";
	
	}
	$en_liste.= "</table>\n";
	
	switch ($element){
		case "listes":
			$requete_total = 'SELECT id_liste
			FROM spip_listes
			WHERE statut="'.$statut.'" '.$clause_where.'
			ORDER BY date DESC';
			$retour = 'listes_toutes';
			break;
		case "messages":
			$requete_total = 'SELECT id_courrier
			FROM spip_courriers
			WHERE type="'.$type.'" AND statut="'.$statut.'"';
			$retour = 'spip_listes';
			break;
		case "abonnements":
			$requete_total = 'SELECT listes.id_liste, listes.titre, listes.statut, listes.date, lien.id_auteur,lien.id_liste FROM  spip_abonnes_listes AS lien LEFT JOIN spip_listes AS listes  ON 	lien.id_liste=listes.id_liste WHERE lien.id_auteur="'.$id_auteur.'" AND (listes.statut ="liste" OR listes.statut ="inact") ORDER BY listes.date DESC';
			$retour = 'abonne_edit';
			$param = '&id_auteur='.$id_auteur;
			break;
	}
	
	$resultat_total = spip_query($requete_total);
	$total = spip_num_rows($resultat_total);
	
	$en_liste.= spiplistes_afficher_pagination($retour, $param, $total, $position, $nom_position);
	$en_liste.= "</div>\n";
	$en_liste.= "<br />\n";
	}

	return $en_liste;

}



/**
* adapte de lettres_afficher_pagination
*
* @param string fond
* @param string arguments
* @param int total
* @param int position
* @author Pierre Basson
**/
function spiplistes_afficher_pagination($fond, $arguments, $total, $position, $nom) {
	global $pas;
	$pagination = '';
	$i = 0;

	$nombre_pages = floor(($total-1)/$pas)+1;

	if($nombre_pages>1) {
	
		$pagination.= "<div style='background-color: white; color: black; padding: 3px; padding-left: 30px;  padding-right: 40px; text-align: right;' class='verdana2'>\n";
		while($i<$nombre_pages) {
			$url = generer_url_ecrire($fond, $nom.'='.strval($i*$pas).$arguments, '&');
			$item = strval($i+1);
			if(($i*$pas) != $position) {
				$pagination.= '&nbsp;&nbsp;&nbsp;<a href="'.$url.'">'.$item.'</a>'."\n";
			} else {
				$pagination.= '&nbsp;&nbsp;&nbsp;<i>'.$item.'</i>'."\n";
			}
			$i++;
		}
		
		$pagination.= "</ul>\n";
		$pagination.= "</div>\n";
	}

	return $pagination;
}

//function spiplistes_propre($texte)
// passe propre() sur un texte puis nettoye les trucs rajoutes par spip sur du html
// ca s'utilise pour afficher un courrier dans l espace prive
// on l'applique au courrier avant de confirmer l'envoi
function spiplistes_propre($texte){
	$temp_style = ereg("<style[^>]*>[^<]*</style>", $texte, $style_reg);
	if (isset($style_reg[0])) 
		$style_str = $style_reg[0]; 
	else 
		$style_str = "";
	$texte = ereg_replace("<style[^>]*>[^<]*</style>", "__STYLE__", $texte);
	//passer propre si y'a pas de html (balises fermantes)
	if( !preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $texte) ) 
	$texte = propre($texte); // pb: enleve aussi <style>...  
	$texte = propre_bloog($texte); //nettoyer les spip class truc en trop
	$texte = ereg_replace("__STYLE__", $style_str, $texte);
	$texte = liens_absolus($texte);
	
	return $texte;
}

//taille d'une chaine sans saut de lignes ni espaces
function spip_listes_strlen($out){
	$out = preg_replace("/(\r\n|\n|\r| )+/", "", $out);
	return $out ;
}


// API a enrichir

// ajouter les abonnes d'une liste a un envoi
function remplir_liste_envois($id_courrier,$id_liste){
	if($id_liste==0){
		$query_m = "SELECT id_auteur FROM spip_auteurs ORDER BY id_auteur ASC";
	}else{
		$query_m = "SELECT id_auteur FROM spip_abonnes_listes WHERE id_liste='".$id_liste."'";
	}
	//echo $query_m ."<br>";
	$result_m = spip_query($query_m);
	$i = 0 ;
	while($row_ = spip_fetch_array($result_m)) {
		$id_abo = $row_['id_auteur'];
		//echo $id_abo.",".$id_message."<br>";
		spip_query("INSERT INTO spip_abonnes_courriers (id_auteur,id_courrier,statut,maj) VALUES ("._q($id_abo).","._q($id_courrier).",'a_envoyer', NOW()) ");
		$i++ ;
	}
	spip_query("UPDATE spip_courriers SET total_abonnes='$i' WHERE id_courrier="._q($id_courrier)); 

}

// compatibilite spip 1.9
if(!function_exists(fin_gauche)) { function fin_gauche(){return false;} }

// Nombre d'abonnes a une liste : a faire

function spiplistes_cherche_auteur(){
	if (!$cherche_auteur = _request('cherche_auteur')) return;
	
	echo "<p align='left'>";
	$result = spip_query("SELECT id_auteur, nom, email FROM spip_auteurs");
	
	while ($row = spip_fetch_array($result)) {
		if( email_valide($cherche_auteur) )
			$table_auteurs[] = $row["email"] ;
		else
			$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	echo debut_boite_info();
	if (!$resultat)
		echo "<b>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</b><br />";
	elseif (count($resultat) == 1) {
		list(, $nouv_auteur) = each($resultat);
		echo "<b>"._T('spiplistes:une_inscription')."</b><br />";
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur="._q($nouv_auteur));
		echo "<ul>";
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];
			
			echo "<li><font face='Verdana,Arial,Sans,sans-serif' size=2><b><font size=3><a href=\"?exec=abonne_edit&id_auteur=$id_auteur\">".typo($nom_auteur)."</a></font></b>";
			echo " | $email_auteur";
			echo "</font>\n";
		}
		echo "</ul>";
	}
	elseif (count($resultat) < 16) {
		reset($resultat);
		unset($les_auteurs);
		while (list(, $id_auteur) = each($resultat))
			$les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<b>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</b><br />";
			$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom");
			echo "<ul>";
			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];
				
				echo "<li><font face='Verdana,Arial,Sans,sans-serif' size=2><b><font size=3>".typo($nom_auteur)."</font></b>";
				if ($email_auteur)
					echo " ($email_auteur)";
				echo " | <a href=\"".generer_url_ecrire("abonne_edit","id_auteur=$id_auteur")."\">"._T('spiplistes:choisir')."</a>";
				if (trim($bio_auteur))
					echo "<br /><font size=1>".couper(propre($bio_auteur), 100)."</font>\n";
				echo "</font><p>\n";
			}
			echo "</ul>";
		}
	}
	else
		echo "<b>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</b><br />";

	echo fin_boite_info();
	echo "<p>";
}

function spiplistes_afficher_auteurs($query, $url){
	$debut = _request('debut');
	$tri = _request('tri');

	$t = spip_query($query);
	$nombre_auteurs = spip_num_rows($t);
	
	//
	// Lire les auteurs qui nous interessent
	// et memoriser la liste des lettres initiales
	//
	
	$max_par_page = 30;
	if ($debut > $nombre_auteurs - $max_par_page)
	$debut = max(0,$nombre_auteurs - $max_par_page);
	$debut = intval($debut);
	
	$i = 0;
	$auteurs=array();
	while ($auteur = spip_fetch_array($t)) {
		if ($i>=$debut AND $i<$debut+$max_par_page) {
			if ($auteur['statut'] == '0minirezo')
			$auteur['restreint'] = spip_num_rows(
			  spip_query("SELECT * FROM spip_auteurs_rubriques WHERE id_auteur="._q($auteur['id_auteur'])));
			$auteurs[] = $auteur;
		}
		$i++;
		
		if ($tri == 'nom') {
			$lettres_nombre_auteurs ++;
			$premiere_lettre = strtoupper(spip_substr(extraire_multi($auteur['nom']),0,1));
			if ($premiere_lettre != $lettre_prec) {
				#			echo " - $auteur[nom] -";
				$lettre[$premiere_lettre] = $lettres_nombre_auteurs-1;
			}
			$lettre_prec = $premiere_lettre;
		}
	}
	
	//
	// Affichage
	//
	
	// reglage du debut
	$max_par_page = 30;
	if ($debut > $nombre_auteurs - $max_par_page)
	$debut = max(0,$nombre_auteurs - $max_par_page);
	$fin = min($nombre_auteurs, $debut + $max_par_page);
	
	// ignorer les $debut premiers
	unset ($i);
	reset ($auteurs);
	while ($i++ < $debut AND each($auteurs));
	
	// ici commence la vraie boucle
	echo debut_cadre_relief('redacteurs-24.gif');
	echo "<table border='0' cellpadding=3 cellspacing=0 width='100%' class='arial2'>\n";
	echo "<tr bgcolor='#DBE1C5'>";
	echo "<td width='20'>";
	$img = "<img src='img_pack/admin-12.gif' alt='' border='0'>";
	if ($tri=='statut')
		echo $img;
	else
		echo "<a href='".parametre_url($url,'tri','statut')."' title='"._T('lien_trier_statut')."'>$img</a>";
	
	echo "</td><td>";
	if ($tri == '' OR $tri=='nom')
		echo '<b>'._T('info_nom').'</b>';
	else
		echo "<a href='".parametre_url($url,'tri','nom')."' title='"._T('lien_trier_nom')."'><b>"._T('info_nom')."</b></a>";
	
	if ($options == 'avancees') echo "</td><td colspan='2'><b>"._T('info_contact')."</b>";
		echo "</td><td>";
	if ($visiteurs != 'oui') {
		if ($tri=='nombre')
			echo "<b>"._T('spiplistes:format')."</b>";
		else
			echo "<b>"._T('spiplistes:format')."</b>"; 
	}
	echo "</td><td>";
	echo "<b>"._T('spiplistes:modifier')."</b>";
	
	echo "</td></tr>\n";
	
	if ($nombre_auteurs > $max_par_page) {
		echo "<tr bgcolor='white'><td colspan='".($options == 'avancees' ? 5 : 3)."'>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
		for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
			if ($j > 0) echo " | ";
			
			if ($j == $debut)
				echo "<b>$j</b>";
			elseif ($j > 0)
				echo "<a href=$retour&debut=$j>$j</a>";
			else
				echo " <a href=$retour>0</a>";
			
			if ($debut > $j  AND $debut < $j+$max_par_page)
				echo " | <b>$debut</b>";
		}
		echo "</font>";
		echo "</td></tr>\n";
		
		if (($tri == 'nom') AND $options == 'avancees') {
			// affichage des lettres
			echo "<tr bgcolor='white'><td colspan='5'>";
			echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
			foreach ($lettre as $key => $val) {
				if ($val == $debut)
					echo "<b>$key</b> ";
				else
					echo "<a href='".parametre_url($url,'debut',$val)."'>$key</a> ";
			}
			echo "</font>";
			echo "</td></tr>\n";
		}
		echo "<tr height='5'></tr>";
	}
	
	//translate extra field data
	list(,,,$trad,$val) = explode("|",_T("spiplistes:options")); 
	$trad = explode(",",$trad);
	$val = explode(",",$val);
	$trad_map = Array();
	for($index_map=0;$index_map<count($val);$index_map++) {
		$trad_map[$val[$index_map]] = $trad[$index_map];
	}
	$i=0;
	foreach ($auteurs as $row) {
		// couleur de ligne
		$couleur = ($i % 2) ? '#FFFFFF' : $couleur_claire;
		$i++;
		echo "<tr bgcolor='$couleur'>";
		
		// statut auteur
		echo "<td>";
		echo bonhomme_statut($row);
		
		// nom
		echo '</td><td>';
		echo "<a href='?exec=abonne_edit&id_auteur=".$row['id_auteur']."'>".typo($row['nom']).'</a>';
		
		if ($connect_statut == '0minirezo' AND $row['restreint'])
		echo " &nbsp;<small>"._T('statut_admin_restreint')."</small>";
		
		// contact
		if ($options == 'avancees') {
			echo '</td><td>';
			if ($row['messagerie'] == 'oui' AND $row['login']
			  AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non" AND $messagerie != "non")
				echo bouton_imessage($row['id_auteur'],"force")."&nbsp;";
			if ($connect_statut=="0minirezo"){
				if (strlen($row['email'])>3)
					echo "<a href='mailto:".$row['email']."'>"._T('lien_email')."</a>";
				else
					echo "&nbsp;";
			}
			
			if (strlen($row['url_site'])>3)
				echo "</td><td><a href='".$row['url_site']."'>"._T('lien_site')."</a>";
			else
				echo "</td><td>&nbsp;";
		}
		
		// Abonne ou pas ?
		echo '</td><td>';
		
		$extra = unserialize ($row["extra"]);
		
		if( !is_array($extra) ){
			$extra = array();
			$extra["abo"] = "non";
			set_extra($row["id_auteur"],$extra,'auteur');
			get_extra($row["id_auteur"],'auteur');
		}
		
		$abo = $extra["abo"];
		
		if($abo == "non")
			echo "-";
		else
			echo "&nbsp;".$trad_map[$abo];
		
		// Modifier l'abonnement
		echo '</td><td>';
		
		$retour = parametre_url($url,'debut',$debut);
		if ($row["statut"] != '0minirezo') {
			$u = parametre_url($retour,'id_auteur',$row['id_auteur']);
			$u = parametre_url($retour,'changer_statut','oui');
			if($extra["abo"] == 'html'){
				$option_abo = "<a href='".parametre_url($retour,'statut','non')."'>"._T('spiplistes:desabo')
				 . "</a> | <a href='".parametre_url($retour,'statut','texte')."'>"._T('spiplistes:texte')."</a>";
			}
			elseif ($extra["abo"] == 'texte') 
				$option_abo = "<a href='".parametre_url($retour,'statut','non')."'>"._T('spiplistes:desabo')
				 . "</a> | <a href='".parametre_url($retour,'statut','html')."'>html</a>";
			elseif(($extra["abo"] == 'non')OR (!$extra["abo"])) 
				$option_abo = "<a href='".parametre_url($retour,'statut','texte')."'>"._T('spiplistes:texte')
				 . "</a> | <a href='".parametre_url($retour,'statut','html')."'>html</a>";
			echo "&nbsp;".$option_abo;
		}
		echo "</td></tr>\n";
	}
	
	echo "</table>\n";
	
	echo "<a name='bas'>";
	echo "<table width='100%' border='0'>";
	
	$debut_suivant = $debut + $max_par_page;
	if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
		echo "<tr height='10'></tr>";
		echo "<tr bgcolor='white'><td align='left'>";
		if ($debut > 0) {
			$debut_prec = strval(max($debut - $max_par_page, 0));
			echo "<form method=\"get\" action=\"".parametre_url($url,'debut',$debut_prec)."\">";
			echo "<input type='submit' name='submit' value='&lt;&lt;&lt;' class='fondo'>";
			echo "</form>";
		}
		echo "</td><td align='right'>";
		if ($debut_suivant < $nombre_auteurs) {
			echo '<form method="post" action="'.parametre_url($url,'debut',$debut_suivant).'">';
			echo "<input type='submit' name='submit' value='&gt;&gt;&gt;' class='fondo'>";
			echo "</form>";
		}
		echo "</td></tr>\n";
	}
	
	echo "</table>\n";
	echo fin_cadre_relief();
	return join(',', $les_auteurs);
}

/******************************************************************************************/
/* SPIP-listes est un syst�me de gestion de listes d'information par email pour SPIP      */
/* Copyright (C) 2004 Vincent CARON  v.caron<at>laposte.net , http://bloog.net            */
/*                                                                                        */
/* Ce programme est libre, vous pouvez le redistribuer et/ou le modifier selon les termes */
/* de la Licence Publique G�n�rale GNU publi�e par la Free Software Foundation            */
/* (version 2).                                                                           */
/*                                                                                        */
/* Ce programme est distribu� car potentiellement utile, mais SANS AUCUNE GARANTIE,       */
/* ni explicite ni implicite, y compris les garanties de commercialisation ou             */
/* d'adaptation dans un but sp�cifique. Reportez-vous � la Licence Publique G�n�rale GNU  */
/* pour plus de d�tails.                                                                  */
/*                                                                                        */
/* Vous devez avoir re�u une copie de la Licence Publique G�n�rale GNU                    */
/* en m�me temps que ce programme ; si ce n'est pas le cas, �crivez � la                  */
/* Free Software Foundation,                                                              */
/* Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, �tats-Unis.                   */
/******************************************************************************************/
?>
