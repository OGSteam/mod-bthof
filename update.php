<?php
if (!defined('IN_SPYOGAME')) {
    die("Hacking attempt");
}

global $db;
if (!isset($table_prefix)) global $table_prefix;

define('TABLE_BTHOF_CONF',$table_prefix.'bthof_conf');
define('TABLE_BTHOF_FLOTTES',$table_prefix.'bthof_flottes');

// Test de présence de la table de configuration
$query = "SELECT * FROM ".TABLE_BTHOF_CONF." WHERE 1";
$result = $db->sql_query($query);
$conf_existe = $db->sql_numrows($result);
$conf_03 = mysql_num_fields($result);
echo $conf_03."\n";
echo $conf_existe."\n";
//echo $result."\n";
if(!$result) 
{
	echo "nul\n";
	// création de la table d'enregistrement des préférences (pour les joueurs)
	$query = "CREATE TABLE `".$table_prefix.'bthof_conf'."` ("
		."  `user_id` int(11) NOT NULL COMMENT 'ID du compte OGSpy',"
		."  `icon_display_active` tinyint(1) NOT NULL default '1' COMMENT 'active ou non les icônes graphiques',"
		."  `bbcode_t` varchar(8) NOT NULL default '' COMMENT 'couleur bbcode pour les titres (ex : Bâtiments)',"
		."  `bbcode_o` varchar(8) NOT NULL default '' COMMENT 'couleur bbcode pour les objets (ex : Mine de Métal)',"
		."  `bbcode_r` varchar(8) NOT NULL default '' COMMENT 'couleur bbcode pour les valeurs de records (ex : 28)',"
		."  `bbcode_l` varchar(8) NOT NULL default '' COMMENT 'couleur bbcode pour les recordmens (ex : toto, titi)',"
		."  PRIMARY KEY  (`user_id`)"
		."  ) COMMENT='sauvegarde des paramètres bt_hof'";
	$db->sql_query($query);

	//Insertion des valeurs par défaut
	$query = "INSERT INTO ".TABLE_BTHOF_CONF." (user_id, icon_display_active, bbcode_t, bbcode_o, bbcode_r, bbcode_l) VALUES ('','1','orange','','red','yellow')";
	$db->sql_query($query);
}
elseif($conf_existe == 0)
{
	echo "vide\n";
	if($conf_03 == 2)
	{	// Ajout des champs pour le bbcode
			echo "ajout\n";

		$query = "ALTER TABLE `".$table_prefix.'bthof_conf'."` ADD `bbcode_t` varchar(8) NOT NULL, ADD `bbcode_o` varchar(8) NOT NULL, ADD `bbcode_r` varchar(8) NOT NULL, ADD `bbcode_l` varchar(8) NOT NULL";
		$db->sql_query($query);
	}
	//Insertion des valeurs par défaut
	$query = "INSERT INTO ".TABLE_BTHOF_CONF." (user_id, icon_display_active, bbcode_t, bbcode_o, bbcode_r, bbcode_l) VALUES ('','1','orange','','red','yellow')";
	$db->sql_query($query);
}

$query  = $db->sql_query("SELECT `version` FROM `".TABLE_MOD."` WHERE action='bt_hof'");
$result = $db->sql_fetch_assoc($query);
$version = $result['version'];

// création de la table d'enregistrment des flottes
if ($version == "0.4") {
	$query2 = "CREATE TABLE `".TABLE_BTHOF_FLOTTES."` ("
	."	user_id int(11) NOT NULL default '0',"
	."	PT int(11) NOT NULL default '0',"
	."	GT int(11) NOT NULL default '0',"
	."	CLE int(11) NOT NULL default '0',"
	."	CLO int(11) NOT NULL default '0',"
	."	CR int(11) NOT NULL default '0',"
	."	VB int(11) NOT NULL default '0',"
	."	VC int(11) NOT NULL default '0',"
	."	REC int(11) NOT NULL default '0',"
	."	SE int(11) NOT NULL default '0',"
	."	BMD int(11) NOT NULL default '0',"
	."	DST int(11) NOT NULL default '0',"
	."	EDLM int(11) NOT NULL default '0',"
	."	TRA int(11) NOT NULL default '0',"
	."	SAT int(11) NOT NULL default '0',"
	."	PRIMARY KEY (`user_id`)"
	."	) COMMENT='flotte de la partie bt_hof'";
	$db->sql_query($query2);
}

$mod_folder = "bthof";
$mod_name = "bt_hof";
update_mod($mod_folder,$mod_name);
?>