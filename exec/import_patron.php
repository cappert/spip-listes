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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/distant');
include_spip('inc/affichage');
include_spip('inc/meta');
include_spip('inc/filtres');
include_spip('inc/lang');

function exec_import_patron(){

	$id_message = _request('id_message');
	$patron = _request('patron');
	$date = _request('date');
	$changer_lang = _request('changer_lang');

	$lang = (isset($changer_lang)) ? $changer_lang : $GLOBALS['spip_lang'] ; 

	$nomsite=lire_meta("nom_site"); 
	$urlsite=lire_meta("adresse_site");
	$message_texte ='';

	include_spip('public/assembler');
	$contexte_patron = array('date' => $date,'patron'=>$patron,'lang'=>$lang);
	
	if (find_in_path('patrons/'.$patron.'_texte.html')){
		$patron_version_texte = true ;
		$message_texte =  recuperer_fond('patrons/'.$patron.'_texte', $contexte_patron);
	}
	$texte_patron = recuperer_fond('patron_switch', $contexte_patron);
	//$texte_patron = recuperer_page(generer_url_public('patron_switch',"patron=$patron&date=$date",true)) ;		
			
	$titre_patron = _T('spiplistes:lettre_info')." ".$nomsite;
	
	$titre = $titre_patron;
	$texte = $texte_patron;
	$message_texte = $message_texte;

	if((strlen($texte) > 10))
		spip_query("UPDATE spip_courriers SET titre="._q($titre).", texte="._q($texte).", message_texte="._q($message_texte)." WHERE id_courrier="._q($id_message));
	else
		$message_erreur = _T('spiplistes:patron_erreur');
	
	$nomsite=lire_meta("nom_site");
	$urlsite=lire_meta("adresse_site");
	$charset = lire_meta('charset');
	
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
	echo "<html lang='$lang' dir='ltr'>";
	echo "<head><meta http_equiv='Content-Type' content='text/html; charset=".$charset."'></head><body>\n";
	
  echo "<div style='float:right;width:200px;text-align:left;border:1px solid #000;background: yellow;color: #000;margin-bottom: 10px;padding:10px;'>";  
  echo "<p><strong>$patron</strong><p>\n";
  if($patron_version_texte) echo "<p><strong>Patron d&eacute;tect&eacute; pour la version texte</strong><p>\n";
  echo _T('spiplistes:date_ref').": $date\n";
  
  echo menu_langues('changer_lang', $lang , '<strong>Langue :</strong>&nbsp;','', generer_url_ecrire('import_patron','id_message='.$id_message.'&patron='.$patron.'&date='.$date ) );

	echo "<br /><form action='?exec=courrier_edit&id_message=$id_message' method='post'>\n";	
	echo "<input type='submit' name='Valider' value='"._T('spiplistes:confirmer')."' class='fondo'>\n";	
	echo "<a href='?exec=courrier_edit&id_message=$id_message'>"._T('spiplistes:retour_link')."</a><br />\n";
	echo "</form>";
	echo "</div>";
	echo "<div style='text-align:left;margin-right:250px;border-right:2px outset #000000'>";
	echo liens_absolus($texte_patron).$message_erreur;
	
	$contexte_pied = array('lang'=>$lang);
	$texte_pied = recuperer_fond('modeles/piedmail', $contexte_pied);

	echo $texte_pied;
	echo "</div>";
	
	echo "</body></html>";
		 
}	

?>