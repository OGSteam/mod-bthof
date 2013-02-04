<?php
	/* ************************************************************************* *
	 *	Filename	:	bt_hof.php Version 0.5
	 *	Author		:	erikosan / Savinien Cyrano (Univers 14)
	 *	Contributor	:	lithie / Shad0w
	 *	Mod OGSpy	:	Building & Techno HOF
	 * ************************************************************************* */
	
	/* ************************************************************************* *
	 *	Le Classement fourni par ce mod concerne tous les membres actifs du serveur OGSpy
	 *	Il se base sur les données fournies dans l'onglet Empire de OGspy et fournit les HOF Bâtiments/Recherche/Défense
	 *	Ce mod gère les permissions d'acces grace aux groupes d'OGSpy
	 *	Il suffit pour cela de créer un groupe "bt_hof" et d'y ajouter les utilisateurs authorisés pour l'utilisation de ce mod
	 *	Si AUCUN GROUPE N'EST CREE, TOUS LES MEMBRES ONT ACCES
	 * ************************************************************************* */
	
	if (!defined ('IN_SPYOGAME'))		die("Hacking attempt");
	// charge le fichier functions.php
require_once("mod/bthof/functions.php");
	
	// error_reporting(E_ALL);
	
	require_once("views/page_header.php");

	if (!isset ($table_prefix))		global $table_prefix;
	if (!isset ($icon_display))		global $icon_display;
	
	define('TABLE_BTHOF_CONF', $table_prefix . 'bthof_conf');
	define('TABLE_BTHOF_FLOTTES', $table_prefix . 'bthof_flottes');
	define('TABLE_FLOTTES', $table_prefix . 'mod_flottes');
	
	$query = "SELECT `active` FROM `".TABLE_MOD."` WHERE `action`='bt_hof' AND `active`='1' LIMIT 1";
	if (!$db->sql_numrows($db->sql_query($query)))
		die("Hacking attempt");
	
	if ($user_data["user_admin"] != 1 AND $user_data["user_coadmin"] != 1)
	{
		$request = "SELECT group_id FROM ".TABLE_GROUP." WHERE group_name='bt_hof'";
		$result = $db->sql_query($request);
		
		if (list($group_id) = $db->sql_fetch_row($result))
		{
			$request = "SELECT COUNT(*) FROM ".TABLE_USER_GROUP." WHERE group_id=".$group_id." AND user_id=".$user_data['user_id'];
			$result = $db->sql_query($request);
			
			list($row) = $db->sql_fetch_row($result);
			if ($row == 0)
				redirection("index.php?action=message&id_message=forbidden&info");
		}
	}
	
	// Récuppération du choix d'affichage
	
	if (!isset($affichage))
	{
		global $affichage;
		Get_Adm();
		
		if ($icon_display==1)
			$affichage = true;
		else
			$affichage = false;
	}
	
	if (isset ($HTTP_SERVER_VARS))		$_SERVEUR = $HTTP_SERVER_VARS;
	
	if (empty ($_SERVEUR))
	  {
		$javascript = true;
		}
	  else
	  {
		$javascript = false;
	  }
	global $bbcode;
	global $bbbat;
	
	$bbcode = "[color=orange][b][u]HoF Bâtiments - Flottes - Technologies - Défense - Production Miniere[/u][/b][/color]\n\n\n"; 	

	$Building_Name  = array("M","C","D","CES","CEF","UdR","UdN","CSp","HM","HC","HD","CM","CC","CD","Lab","Ter","Silo","BaLu","Pha","PoSa","DdR");
	$Building_Label = array("Mine de métal","Mine de Cristal","Synthétiseur de deutérium","Centrale électrique solaire","Centrale électrique de fusion","Usine de robots","Usine de nanites ","Chantier spatial","Hangar de métal","Hangar de cristal","Réservoir de deutérium","Cachette de métal","Cachette de cristal","Cachette de deutérium","Laboratoire de recherche","Terraformeur","Silo de missiles ","Base Lunaire","Phalange de capteur","Porte de saut spatial","Dépôt de Ravitaillement");
	$Building_icon  = array("1.gif","2.gif","3.gif","4.gif","12.gif","14.gif","15.gif","21.gif","22.gif","23.gif","24.gif","25.gif","26.gif","27.gif","31.gif","33.gif","44.gif","41.gif","42.gif","43.gif","34.gif");

	$Flottes_Name  = array("PT","GT","CLE","CLO","CR","VB","VC","REC","SE","BMD","DST","EDLM","TRA","SAT");
	$Flottes_Label = array("Petit Transporteur","Grand Transporteur","Chasseur Léger","Chasseur Lourd","Croiseur","Vaisseau de Bataille","Vaisseau de Colonisation","Recycleur","Sonde d'Espionnage","Bombardier","Destructeur","Etoile de la Mort","Traqueur","Satellite Solaire");
	$Flottes_icon  = array("202.gif","203.gif","204.gif","205.gif","206.gif","207.gif","208.gif","209.gif","210.gif","211.gif","213.gif","214.gif","215.gif","212.gif");

	$Tech_label     = array("Technologie Espionnage","Technologie Ordinateur","Technologie Armes","Technologie Bouclier","Protect. Vaisseaux","Technologie Energie","Technologie Hyperespace","Réacteur à Combustion","Réacteur à Impulsion","Propulsion Hyperespace","Technologie Laser","Technologie Ions","Technologie Plasma","Réseau de Recherche","Technologie Astrophysique","Technologie Graviton");
	$Tech_name      = array("Esp","Ordi","Armes","Bouclier","Protection","NRJ","Hyp","RC","RI","PH","Laser","Ions","Plasma","RRI","Astrophysique","Graviton");
	$Tech_icon      = array("106.gif","108.gif","109.gif","110.gif","111.gif","113.gif","114.gif","115.gif","117.gif","118.gif","120.gif","121.gif","122.gif","123.gif","124.gif","199.gif");

	$Def_label      = array( "Lance Missile","Laser Léger","Laser Lourd","Canon Gauss","Artillerie Ion","Lance Plasma","Missile Interception","Missile InterPlanétaire");
	$Def_name       = array( "LM","LLE","LLO","CG","AI","LP","MIC","MIP");
	$Def_icon       = array("401.gif","402.gif","403.gif","404.gif","405.gif","406.gif","502.gif","503.gif");

	if (!isset($nplayer))	global $nplayer;
	if (!isset($production_metal))	global $production_metal;
	if (!isset($production_cristal))	global $production_cristal;
	if (!isset($production_deuterium))	global $production_deuterium;
	if (!isset($production_total))	global $production_total;
	if (!isset($production_joueur))	global $production_joueur;
	if (!isset($pub_subaction)) $pub_subaction = 'Batiments';
	$nplayer=0;

	// Prendre le skin serveur par défaut s'il n'y en a pas dans le profil utilisateur
	if ($user_data["user_skin"]."a" == "a") {
		$lien = $server_config["default_skin"]."gebaeude/";}
	else 
		$lien = $user_data["user_skin"]."gebaeude/";

	/* ** Menu Principal ** */
	
	$prod = true;

	if ($user_data['user_admin'] == 1 OR $user_data['user_coadmin'] == 1) // $rightToAdmin vaut true si l'utilisateur a le droit d'administre OGSpy
		$rightToAdmin = true;
	else
		$rightToAdmin = false;

	if ($rightToAdmin) // Determine la taille, en %, des colones du menu
		$rowWidth = 12;
	else
		$rowWidth = 14;
	?>

	<table style='width : 100%; text-align : center; margin-bottom : 20px;'>
		<tr>
		<?php
		//menu Bâtiments
    if ($pub_subaction != 'Batiments') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Batiments" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>B&acirc;timents</a></td>',"\n";
    //menu Flottes
    if ($pub_subaction != 'Flottes') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Flottes" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>Flottes</a></td>',"\n";
    //menu techno
    if ($pub_subaction != 'Techno') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Techno" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>Technologies</a></td>',"\n";
    //menu defense
    if ($pub_subaction != 'Defense') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Defense" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>D&eacute;fense</a></td>',"\n";
    //menu prod minière
    if ($pub_subaction != 'Production') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Production" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>Prod Mini&egrave;re</a></td>',"\n";
    //menu espace bbcode
    if ($pub_subaction != 'BBCode') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=BBCode" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>BBCode</a></td>',"\n";
    if ($user_data['user_admin'] == 1 || $user_data['user_coadmin'] == 1 ) 
      {
      //menu admin
      if ($pub_subaction != 'Admin') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Admin" style="color: lime;"';
      else echo '<th width="'.$rowWidth.'%"><a';
      echo '>Administration</a></td>',"\n";
      }
    //menu changelog
    if ($pub_subaction != 'Changelog') echo '<td class="c" width="'.$rowWidth.'%"><a href="index.php?action=bt_hof&subaction=Changelog" style="color: lime;"';
    else echo '<th width="'.$rowWidth.'%"><a';
    echo '>Changelog</a></td>',"\n";
?>
		</tr>
	</table>
	
	<?php
		if (!isset ($pub_mine))
			$pub_mine = 'total';
		
		switch ($pub_subaction)
		{
			case "Batiments" : // Page Bâtiments
				Create_HOF($Building_Name,$Building_Label,$Building_icon,"Bâtiments","user_building",$number,$affichage);
				break;
		
			case "Flottes" : // Page Flottes
				Create_HOF($Flottes_Name,$Flottes_Label,$Flottes_icon,"Flottes","bthof_flottes",13,$affichage);
				break;
		
			case "Techno" : //Page Technologies
				Create_HOF($Tech_name,$Tech_label,$Tech_icon,"Technologies","user_technology",15,$affichage);
				break;
		
			case "Defense" : // Page Défense
				Create_HOF($Def_name,$Def_label,$Def_icon,"Défense","user_defence",7,$affichage);
				break;
		
			case "Production" : //Page production minière
				$type_production = Array( Array ( 'titre'=>'jour' , 'x'=>1 ) , Array ( 'titre'=>'semaine' , 'x'=>7 ) );
				foreach($type_production as $tbl)
				{
					?>
			
					<table style='width : 60%; text-align : center; margin-bottom : 20px;'>
						<tr>
							<td class='c' style='color : #FF00FF; width : 28%;' colspan='2'>Production par <?php echo $tbl['titre']; ?></td>
							<td class='c' style='width : 18%;'><a href='index.php?action=bt_hof&subaction=Production&mine=metal'>Métal</a></td>
							<td class='c' style='width : 18%;'><a href='index.php?action=bt_hof&subaction=Production&mine=cristal'>Cristal</a></td>
							<td class='c' style='width : 18%;'><a href='index.php?action=bt_hof&subaction=Production&mine=deuterium'>Deutérium</a></td>
							<td class='c' style='width : 18%;'><a href='index.php?action=bt_hof&subaction=Production&mine=total'>Total</a></td>
						</tr>
				
					<?php 
					Create_Mine_HOF();
					$valid_pub_mine = Array ('metal','cristal','deuterium','total');
					if(!in_array($pub_mine,$valid_pub_mine)) $pub_mine = 'total';
					
					// Page de production de {$pub_mine}
					arsort(${'production_'.$pub_mine});
					$nb = 1;
			
					foreach (${'production_'.$pub_mine} as $key => $val)
					{
						?>
						
						<tr>
							<td style='background-color : #273234;'><?php echo '<span style=\'color : white; font-weight : bold;\'>' . $nb . '</span>'; ?></td>
							<td style='background-color : #273234;'><a><?php echo '<span style=\'color : white; font-weight : bold;\'>' . $production_joueur[$key] . '</span>'; ?></a></td>
							<td style='background-color : #273234;'><font color='red'><b><?php echo number_format($production_metal[$key]*$tbl['x'], 0, ',', ' '); ?></b></font></td>
							<td style='background-color : #273234;'><font color='lightblue'><b><?php echo number_format($production_cristal[$key]*$tbl['x'], 0, ',', ' '); ?></b></font></td>
							<td style='background-color : #273234;'><font color='green'><b><?php echo number_format($production_deuterium[$key]*$tbl['x'], 0, ',', ' '); ?></b></font></td>
							<td style='background-color : #273234;'><font color='grey'><?php echo number_format($production_total[$key]*$tbl['x'], 0, ',', ' '); ?></font></td>
						</tr>
							
						<?php 
						$nb++;
					}
							
					echo '</table>';
				}
				break;
			case "BBCode" : // Création  de la page BBCode
				Get_BBCode();
			
				$bbcode .= "[b][color=".$bbcode_t."]Bâtiments[/color][/b]\n\n";
				$bbcode .= HOF_bbcode($Building_Name,$Building_Label,"Bâtiments","user_building",$number,$bbcode_o,$bbcode_r,$bbcode_l);
			
				$bbcode .= "\n\n[b][color=".$bbcode_t."]Flottes[/color][/b]\n\n";
				$bbcode .= HOF_bbcode($Flottes_Name,$Flottes_Label,"Flottes","bthof_flottes",13,$bbcode_o,$bbcode_r,$bbcode_l);
			
				$bbcode .= "\n\n[b][color=".$bbcode_t."]Technologies[/color][/b]\n\n";
				$bbcode .= HOF_bbcode($Tech_name,$Tech_label,"Technologies","user_technology",15,$bbcode_o,$bbcode_r,$bbcode_l);
			
				$bbcode .= "\n\n[b][color=".$bbcode_t."]Défense[/color][/b]\n\n";
				$bbcode .= HOF_bbcode($Def_name,$Def_label,"Défense","user_defence",7,$bbcode_o,$bbcode_r,$bbcode_l);
			
				$bbcode .= "\n[b][color=".$bbcode_t."]Production[/color][/b]\n\n";
			
				Create_Mine_HOF();
			
				arsort($production_metal);
			
				list($key,$val) = each($production_metal);
			
				if($bbcode_o=='')
					$bbcode .= "- Métal : ";
				else
					$bbcode .= "- [color=".$bbcode_o."]Métal : [/color]";
			
				if($bbcode_r=='')
					$bbcode .= $val." : ";
				else
					$bbcode .= "[color=".$bbcode_r."]".$val."[/color] : ";
			
				if($bbcode_l=='')
					$bbcode .= $production_joueur[$key]."[/color]\n";
				else
					$bbcode .= "[color=".$bbcode_l."]".$production_joueur[$key]."[/color]\n";
			
				arsort($production_cristal);
	
				list($key,$val) = each($production_cristal);
			
				if($bbcode_o=='')
					$bbcode .= "- Cristal : ";
				else
					$bbcode .= "- [color=".$bbcode_o."]Cristal : [/color]";
			
				if($bbcode_r=='')
					$bbcode .= $val." : ";
				else
					$bbcode .= "[color=".$bbcode_r."]".$val."[/color] : ";
			
				if($bbcode_l=='')
					$bbcode .= $production_joueur[$key]."[/color]\n";
				else
					$bbcode .= "[color=".$bbcode_l."]".$production_joueur[$key]."[/color]\n";
			
				arsort($production_deuterium);
			
				list($key,$val) = each($production_deuterium);
			
				if($bbcode_o=='')
					$bbcode .= "- Deutérium : ";
				else
					$bbcode .= "- [color=".$bbcode_o."]Deutérium : [/color]";
			
				if($bbcode_r=='')
					$bbcode .= $val." : ";
				else  $bbcode .= "[color=".$bbcode_r."]".$val."[/color] : ";
			
				if($bbcode_l=='')
					$bbcode .= $production_joueur[$key]."[/color]\n";
				else
					$bbcode .= "[color=".$bbcode_l."]".$production_joueur[$key]."[/color]\n";
				?>
					<textarea rows='25' cols='15' style='border : 3px ridge silver; padding : 10px; font-size : 12px;' id='bbcode3'><?php echo $bbcode; ?></textarea>
				<?php
				break;
			case "Admin" :	//affichage du tableau admin
				if (isset ($pub_add_admin))
					Save_Adm ($pub_icon_display);
				else Get_Adm();
			
				?>
					<form method='post'>
						<table style='width : 50%; text-align : center'>
							<tr>
								<td class='c' style='color : #FF00FF;' colspan='2'>Apparence</td>
							</tr>
							<tr>
								<td style='background-color : #273234;'>Afficher les icônes graphiques</td>
								<td style='width : 20%; background-color : #273234;'><input name='icon_display' type='checkbox' value='1' <?php if ($icon_display == 1) echo 'checked=\'checked\''; ?></td>
							</tr>
							<tr>
								<td style='background-color : #273234;' colspan='3'><input type='submit' name='add_admin' value='Enregistrer' /></td>
							</tr>
						</table>
					</form>
			
				<?php
					if (isset ($pub_add_bbcode))
						Sauve_BBCode ($pub_bbcode_t, $pub_bbcode_o, $pub_bbcode_r, $pub_bbcode_l);
					else Get_BBCode();
				
					$color		= array ('','aqua','black','blue','cyan','fuchsia','gray','green','lime','maroon','navy','olive','orange','pink','purple','red','silver','teal','white','yellow');
					$nb_color	= 20;
				?>
			
				<form method='post'>
					<table style='width : 50%; text-align : center'>
						<tr>
							<td class='c' style='color : #FF00FF;' colspan='2'>Couleurs du BBCode</td>
						</tr>
						<tr>
							<td style='background-color : #273234; color : <?php echo $bbcode_t; ?>'>Couleur des titres</td>
							<td style='background-color : #273234;'>
								<select name='bbcode_t'>
									<?php
										for ($i = 0 ; $i < $nb_color ; $i++)
										{
											echo '<option value=\'' . $color[$i] . '\' ' . ($color[$i] == $bbcode_t ? "selected" : "") . '>' . $color[$i] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style='background-color : #273234; color : <?php echo $bbcode_o; ?>'>Couleur des labels records</td>
							<td style='background-color : #273234;'>
								<select name='bbcode_o'>
									<?php
										for ($i = 0 ; $i < $nb_color ; $i++)
										{
											echo '<option value=\'' . $color[$i] . '\' ' . ($color[$i] == $bbcode_o ? "selected" : "") . '>' . $color[$i] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style='background-color : #273234; color : <?php echo $bbcode_r; ?>'>Couleur des valeurs records</td>
							<td style='background-color : #273234;'>
								<select name='bbcode_r'>
									<?php
										for ($i = 0 ; $i < $nb_color ; $i++)
										{
											echo '<option value=\'' . $color[$i] . '\' ' . ($color[$i] == $bbcode_r ? "selected" : "") . '>' . $color[$i] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style='background-color : #273234; color : <?php echo $bbcode_l; ?>'>Couleur des recordmens</td>
							<td style='background-color : #273234;'>
								<select name='bbcode_l'>
									<?php
										for ($i = 0 ; $i < $nb_color ; $i++)
										{
											echo '<option value=\'' . $color[$i] . '\' ' . ($color[$i] == $bbcode_l ? "selected" : "") . '>' . $color[$i] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style='background-color : #273234;' colspan='3'><input type='submit' name='add_bbcode' value='Enregistrer' /></td>
						</tr>
					</table>
				</form>
			
				<?php
				break;
			case "Changelog" :	// Affichage du Change Log
				?>
			
				<table style='width:60%'>
					<tr style='line-height : 20px; vertical-align : center;'>
						<td class='c' style='text-align : center; width : 20%; color : #FF00FF;'>Version</td>
						<td class='c' style='text-align : center; color : #FF00FF;'>Modification</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.1</td>
						<td style='background-color : #273234;'>
							<ul>
								<li>Version initiale avec affichage sur une seule page.</li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.2</td>
						<td style='background-color : #273234;'>
							<ul>
								<li>Correction du parsing des résultats.</li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.3</td>
						<td style='background-color : #273234;'>
							<ul>
								<li>Mise en place de l'affichage par onglets avec idée d' Oliwan.</li>
								<li>Ajout des Gifs Ogame des bâtiments, technologies, défense en se basant sur le skin indiqué dans le profil utilisateur (par défaut http://zebulondunet.free.fr/skin/gebaeude/).</li>
								<li>Ajout de la fonction de création de BBCode.</li>
								<li>Réorganisation du source (fonctions, tableaux).</li>
								<li>Première intégration d'informations totaux de production minière par jour.</li>
							</ul>
						</td>
					</tr>
	
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.4</td>
						<td style='background-color : #273234;'>
							<i>Version personnalisée par Lithie</i>
							<ul>
								<li>Modification du design en utilisant des liens avec du subaction en get.</li>
								<li>Regroupement des classements de production sous un seul onglet (Prod Minière).</li>
								<li>Espace BBCode : ajout des hof de production et coloration (sur le principe de _LaNceLoT_).</li>
								<li>Mise en place du choix d'affichage des images (méthode proposée par Oliwan).</li>
								<li>Ajout du choix des couleurs (en partie admin) pour l'espace BBCode.</li>
								<li>Changement du lien par défaut pour les images (celui du serveur).</li>
								<li>Espace BBCode : retrait de la balise [list] remplacée par \" - \" à chaque entrée.</li>
								<li>Activation du choix d'affichage des images via la partie admin.</li>
								<li>Numérotage du classement dans Prod. Minière.</li>
							</ul>
						</td>
					</tr>
	
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.5a</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Shad</i>
							<ul>
								<li>Ajout d'une section flotte.</li>
							</ul>
						</td>
					</tr>
	
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.5b</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Tehty</i>
							<ul>
								<li>Suppression de bug.</li>
							</ul>
						</td>
					</tr>
	
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.5c</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Naruto kun</i>
							<ul>
								<li>Suppression de bug.</li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.6</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par scaler</i>
							<ul>
								<li>Nouvelle formule de production d'énergie de la Centrale Électrique de Fusion avec la version 0.78a d'OGame.</li>
								<li>Calcul du facteur de production erroné.</li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.6a</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Ninety</i>
							<ul>
								<li>Les tableaux ont ete re-codes (plus propre au niveau du code + apparence).</li>
								<li>Changement mineur a niveau du BBCode.</li>
								<li>Valeur pour les production formattees.</li>
								<li>Changement de l'apparence de la partie Admin et Change Log.</li>
								<li>Modification du menu. (ericc)</li>
								<li>Ajout d'une colonne "Cumul Total". (ericc)</li>
								<li>Détection de la présence du Mod_flottes. (ericc)</li>
								<li>Les fonctions sont maintenant dans un fichier séparé. (ericc)</li>
								<li>La mise à jour de la table flotte ne se fait que pour la page flotte (gain de temps) (ericc)</li>
								<li>Les icones sont maintenant celle du skin séléctionnée.Si elles ne sont pas présentes dans le skin, on va chercher celles de Ogame (ralentissement de l'affichage). (Sylar)</li>
								<li>La techonologie expédition a la même place que dans OGame. (Sylar) </li>
                                <li>Une colonne 'Total' dans les productions minière. (Sylar) </li>
                                <li>Simplification du code d'affichage des productions. </li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>0.6b</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par ericc</i>
							<ul>
								<li>Simplification du code d'affichage des icones.</li>
								<li>Correction du bug d'affichage si les icones n'était pas validés.</li>
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>1.0.0</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Shad</i>
							<ul>
								<li>Changement du lien qui affiche les images.</li>
								<li>Mise a jour des fonctions d'install, update et uninstall.</li>
								<li>Remise a jour des id planètes et prise en compte du nombre de planètes et lunes.</li>
								<li>Remplacement de la techonologie expédition par astrophysique.</li>
								<li>Prise en compte de la vitesse de l'uni.</li>
								
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>1.0.1</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Shad</i>
							<ul>
								<li>Prise en compte du dépôt de ravitaillement si activé sur le serveur.</li>								
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>1.0.2</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Shad</i>
							<ul>
								<li>Correction de bug.</li>								
							</ul>
						</td>
					</tr>
					<tr>
						<td style='background-color : #273234; text-align : center;'>1.0.3</td>
						<td style='background-color : #273234;'>
							<i>Mise à jour par Shad</i>
							<ul>
								<li>Ajout des cachettes métal, cristal et deutérium.</li>								
							</ul>
						</td>
					</tr>
				</table>
			
				<?php		
				break;
			}
			require_once("views/page_tail.php");
?>