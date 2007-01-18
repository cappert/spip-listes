<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/extra_plus');
function action_spiplistes_changer_statut_abonne_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_auteur = $securiser_action();
	$redirect = urldecode(_request('redirect'));

	//changer de statut
	//if (autoriser())
	$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur="._q($id_auteur));
	if ($row = spip_fetch_array($result)) {
		$id_auteur=$row['id_auteur'];
		$statut=$row['statut'];

		if($statut=='6forum'){
			spip_query("DELETE FROM spip_abonnes_listes WHERE id_auteur="._q($id_auteur));
			spip_query("DELETE FROM spip_auteurs WHERE id_auteur="._q($id_auteur));
		}
	}
	
	if ($redirect){
		redirige_par_entete(str_replace("&amp;","&",$redirect);
	}
}

?>