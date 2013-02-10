<?php
if (!defined('IN_SPYOGAME')) {
    die("Hacking attempt");
}

global $db;
if (!isset($table_prefix)) 
	global $table_prefix;

$is_ok = false;	
$mod_folder = "bthof";
$is_ok = install_mod($mod_folder);
if ($is_ok == true)
	{
		define('TABLE_BTHOF_CONF',$table_prefix.'bthof_conf');
		define('TABLE_BTHOF_FLOTTE',$table_prefix.'bthof_flotte');

// suppression de la table TABLE_BTHOF_CONF si elle existe
		$query = "DROP TABLE IF EXISTS `".$table_prefix.'bthof_conf'."`";
		$db->sql_query($query);

// suppression de la table TABLE_BTHOF_FLOTTE si elle existe
		$query = "DROP TABLE IF EXISTS `".$table_prefix.'bthof_flotte'."`";
		$db->sql_query($query);

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

		$query = "INSERT INTO ".TABLE_BTHOF_CONF." (user_id, icon_display_active, bbcode_t, bbcode_o, bbcode_r, bbcode_l) VALUES ('0','1','orange','','red','yellow')";
			$db->sql_query($query);

		$query = "CREATE TABLE `".$table_prefix.'bthof_flottes'."` ("
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
			$db->sql_query($query);
	}
else
	{
		echo  "<script>alert('Désolé, un problème a eu lieu pendant l'installation, corrigez les problèmes survenue et réessayez.');</script>";
	}

?>
