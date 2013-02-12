<?php
/** $Id: functions.php  2008-03-08  ericc $ **/
/**
* @file functions.php
*
* functions.php Défini les fonctions du mod
*
* @package [MOD] bt_hof
* @author  erikosan / Savinien Cyrano (Univers 14) 
* @version 0.6a
*	created  : ??/??/2007
*	modified : 11/02/2013
*/
    // On véréfie que le dépôt de ravitaillement fait activer sur l'univers.
    $ddr = $server_config['ddr'];
    if ($ddr == 1) {
        $number ="20";
    } else {
        $number ="19";
    }

    // ==============================================
    // = Calcul du classement de production miniere =
    // ==============================================//
    function Create_Mine_HOF()
    {
    //if (!isset($nplayer))	global $nplayer;
        $user_empire = user_get_empire();
        $nb_planet = find_nb_planete_user();
        if (!isset($production_metal))     { global $production_metal; }
        if (!isset($production_cristal))   { global $production_cristal; }
        if (!isset($production_deuterium)) { global $production_deuterium; }
        if (!isset($production_total))     { global $production_total; }
        if (!isset($production_joueur))    { global $production_joueur; }
        if (!isset($table_prefix))         { global $table_prefix; }
        if (!isset($db))                   { global $db; }
        $planet = array(false, 'user_id' => '', 'planet_name' => '', 'coordinates' => '',
                        'fields' => '', 'fields_used' => '', 'temperature_min' => '',
                        'temperature_max' => '', 'Sat' => '', 'M' => 0, 'C' => 0,
                        'D' => 0, 'CES' => 0, 'CEF' => 0 , 'M_percentage' => 0, 
                        'C_percentage' => 0, 'D_percentage' => 0, 'CES_percentage' => 100,
                        'CEF_percentage' => 100, 'Sat_percentage' => 100);
        $sql = "SELECT DISTINCT u.user_id,user_name,off_geologue FROM ".TABLE_USER." u,".$table_prefix."user_building b WHERE user_active AND u.user_id=b.user_id";
        $result = $db->sql_query($sql);

        $nplayer=0;
        //Début boucle sur joueur
        while ($player = mysql_fetch_array($result, MYSQL_NUM))
        {
            $user_id=$player[0];

            $query_officer = $db->sql_query("SELECT `off_ingenieur`,`off_geologue` FROM ".TABLE_USER." WHERE user_id = ".$user_id);
            list($off_ingenieur, $off_geologue) = $db->sql_fetch_row($query_officer);

         // Récupération des informations sur les mines du joueur
            $quet = mysql_query('SELECT planet_id, planet_name, coordinates, `fields`, temperature_min, temperature_max, Sat, M, C, D, CES, CEF, M_percentage, C_percentage, D_percentage, CES_percentage, CEF_percentage, Sat_percentage FROM '.TABLE_USER_BUILDING.' WHERE user_id = '.$user_id.' ORDER BY planet_id');
            $user_building = array_fill(1, $nb_planet, $planet);

        // Récupération des informations sur les technologies du joueur
            $user_technology = $user_empire["technology"];
            $NRJ = $user_technology['NRJ'];

        // Boucle sur les systèmes d'un joueur
            while ($row = mysql_fetch_assoc($quet))
            {
                $arr = $row;
                unset($arr['planet_id']);
                unset($arr['planet_name']);
                unset($arr['coordinates']);
                unset($arr['fields']);
                unset($arr['temperature_min']);
                unset($arr['temperature_max']);
                unset($arr['Sat']);
                $fields_used = array_sum(array_values($arr));

                $row['fields_used'] = $fields_used;
                $user_building[$row['planet_id']] = $row;
                $user_building[$row['planet_id']][0] = true;

            // calcul des productions
            global $db, $server_config;
            require_once("includes/ogame.php");

                $metal_heure   = 0;
                $cristal_heure = 0;
                $deut_heure    = 0;
                $metal_jour    = 0;
                $cristal_jour  = 0;
                $deut_jour     = 0;
                $start         = 101;
                $nb_planet     = find_nb_planete_user();
                for ($i=$start ; $i<=$start+$nb_planet-1 ; $i++)
                {	
                    $M = $user_building[$i]['M'];
                    $C = $user_building[$i]['C'];
                    $D = $user_building[$i]['D'];
                    $CES = $user_building[$i]['CES'];
                    $CEF = $user_building[$i]['CEF'];
                    $SAT = $user_building[$i]['Sat'];
                    $M_per = $user_building[$i]['M_percentage'];
                    $C_per = $user_building[$i]['C_percentage'];
                    $D_per = $user_building[$i]['D_percentage'];
                    $CES_per = $user_building[$i]['CES_percentage'];
                    $CEF_per = $user_building[$i]['CEF_percentage'];
                    $SAT_per = $user_building[$i]['Sat_percentage'];
                    $temperature_min = $user_building[$i]['temperature_min'];
                    $temperature_max = $user_building[$i]['temperature_max'];
                    $production_CES = ( $CES_per / 100 ) * ( production( "CES", $CES, $off_ingenieur ));
                    $production_CEF = ( $CEF_per / 100 ) * ( production("CEF", $CEF, $off_ingenieur, $temperature_max, $NRJ ));
                    $production_SAT = ( $SAT_per / 100 ) * ( production_sat( $temperature_min, $temperature_max, $off_ingenieur ) * $SAT );
                    $prod_energie   = $production_CES + $production_CEF + $production_SAT;

                    $consommation_M = ( $M_per / 100 ) * ( consumption( "M", $M ));
                    $consommation_C = ( $C_per / 100 ) * ( consumption( "C", $C ));
                    $consommation_D = ( $D_per / 100 ) * ( consumption( "D", $D ));
                    $cons_energie   = $consommation_M + $consommation_C + $consommation_D;

                    if ($cons_energie == 0) {
                        $cons_energie = 1;
                    }
                    $ratio = floor(($prod_energie / $cons_energie) * 100) / 100;
                    if ($ratio > 1) {
                        $ratio = 1;
                    }

                    $metal_heure   = $metal_heure   + (( production( "M", $M, $off_geologue )) * $ratio);
                    $cristal_heure = $cristal_heure + (( production( "C", $C, $off_geologue )) * $ratio);
                    $deut_heure    = $deut_heure    + ((( production( "D", $D, $off_geologue, $temperature_max )) * $ratio) -  ((consumption ("CEF", $CEF)) * ( $CEF_per / 100 )));
                }			

                $metal_jour = 24 * $metal_heure;
                $cristal_jour = 24 * $cristal_heure;
                $deut_jour = 24 * $deut_heure;
                $production_joueur[$nplayer]=$player[1];
                $production_metal[$nplayer]=$metal_jour;
                $production_cristal[$nplayer]=$cristal_jour;
                $production_deuterium[$nplayer]=$deut_jour;
                $production_total[$nplayer]=$metal_jour+$cristal_jour+$deut_jour;
            } // Fin Boucle sur les systèmes d'un joueur
            $nplayer ++;
        } //Fin boucle joueur
        mysql_free_result($result);
        return $nplayer;
    }

    // ===========================================
    // = Calcul du classement pour une catégorie =
    // ===========================================//
    function Create_HOF ($Table_name, $Table_label, $Table_icon, $Title,
                         $OGSpy_Table, $NbItems, $affich)
    {
        if (!isset ($table_prefix)) { global $table_prefix; }
        if (!isset ($db))           { global $db; }
        if (!isset ($lien))         { global $lien; }

        // Controle de l'exitance du mod flottes
        if ($OGSpy_Table == "bthof_flottes")
        {
        // Controle de l'existance du mod flottes et de son activation.
            $query = "SELECT active FROM `".TABLE_MOD."` WHERE `title`='flottes'";
            $result    = $db->sql_query($query);
            $modflotte = $db->sql_fetch_row($result);
            if ($modflotte[0] != "1") {
            // Le mod flotte n'est pas installé ou n'est pas actif 
                echo "Le mod Flottes doit être installé et actif pour permettre de faire des statistiques sur les flottes";
                return;
            } else {
            // Le mod flotte est installé, on met à jour la table TABLE_BTHOF_FLOTTES
                Update_Flotte();
            }
        }
        
        print("<table align='center'>");
        print("<tr><th width='150px'><font color=\'#FF00FF\'>".$Title."</font></th><th width='50px'><font color=\'#FF00FF\'>Max</font></th><th width='300px'><font color=\'#FF00FF\'>Joueur(s)</font></th>");
        if ($Title != "Flottes" and $Title != "Technologies") {
            print ("<th width='50px'><font color=\'#FF00FF\'>Cumul&nbsp;Total</font></th><th width='300px'><font color=\'#FF00FF\'>Joueur(s)</font></th>");
        }
        print ("</tr>");
        print ("<tr> <td width=\"30px\">&nbsp;</td> </tr>");

        for ($NoBld=0 ; $NoBld <= $NbItems ; $NoBld ++)
        {#########################################=> Requête SQL à améliorer ! (JOIN + 1 seule requête.#########################
          //Requète SQL pour récupérer la valeur Max de chaque type et le nom du joueur associé classé par ordre décroissant			
            $sql = "SELECT MAX($Table_name[$NoBld]) ,user_name FROM ".$table_prefix.$OGSpy_Table.", ".TABLE_USER.
                   " WHERE ".TABLE_USER.".user_active AND " .TABLE_USER.".user_id=".$table_prefix.$OGSpy_Table.".user_id" .
                   " GROUP BY user_name ORDER BY 1 DESC";
            $result = $db->sql_query($sql);
          //Requète SQL pour récupérer le total par joueur classé par ordre décroissant
            $sql2 ="SELECT SUM($Table_name[$NoBld]) ,user_name FROM ".$table_prefix.$OGSpy_Table.", ".TABLE_USER.
                   " WHERE ".TABLE_USER.".user_active AND ".TABLE_USER.".user_id=".$table_prefix.$OGSpy_Table.".user_id".
                   " GROUP BY user_name ORDER BY 1 DESC";
            $result2 = $db->sql_query($sql2);

            $val = -1;
            $premiere_fois = 0;
            while ($row = mysql_fetch_array($result, MYSQL_NUM))
            {
                $val = $row[0];
                if ($val == 0) {
                    $row[1] = '-';
                }
              // ce controle sert à afficher les ex aequo s'il y en a !!				
                if($premiere_fois == 1) {
                  //si la valeur est inférieur à la valeur max -> on sort
                    if ($val_max > $val) {
                        break;
                    }
                  //sinon on affiche une virgule et le nom					
                    if ($val != 0) {
                        printf(", %s",$row[1]);
                    }
                } else {
                  //on monte le flag comme quoi la boucle à tournée au moins une fois
                    $premiere_fois = 1;
                  //on enregistre la valeur max, les résultats étant classé par ordre décroissant, le premier est le plus élevé !!
                    $val_max = $row[0];
                    echo "\n\t\t\t <tr> \n\t\t\t\t" . "<td style='color : #FF00F0; background-color : #273234; text-align: center;'>";
                    if ($affich) {
                        aff_img($Table_icon[$NoBld]);
                    } else {
                        echo "&nbsp;";
                    }
                    echo $Table_label[$NoBld] . '</td>' . "\n\t\t\t\t";
                    echo '<td style=\'color : #FF80F0; background-color : #273234; text-align: center; \'>' . $row[0] . '</td>' . "\n\t\t\t\t";
                    echo '<td style=\'color : #FFFFF0; background-color : #273234; text-align: center; \'>' . $row[1];
                }
            }
            if ($Title != "Flottes" and $Title != "Technologies") 
            {
                print("</font></td>");
                $val = -1;
                $flag = 0;
                while ($row2 = mysql_fetch_array($result2)) {
                    $val = $row2[0];
                   // ce controle sert à afficher les ex aequo s'il y en a !!
                    if($flag == 1) {
                     //si la valeur est inférieur à la valeur max -> on sort 
                        if ($val_max > $val) {
                            break;
                        }
                     //sinon on affiche une virgule et le nom suivant
                        printf(", %s",$row2[1]);
                    } else {
                      //on monte le flag comme quoi la boucle à tournée au moins une fois
                        $flag = 1;
                      //on enregistre la valeur max, les résultats étant classé par ordre décroissant, le premier est le plus élevé !!
                        $val_max = $row2[0];
                        echo '<td  width=\'50px\' style=\'color : #FF80F0; background-color : #273234; text-align: center; \'>';
                        printf("%s</td><td width=\"400px\" style=\"color : #FFFFF0; background-color : #273234; text-align: center; \">%s", $row2[0],$row2[1]);
                    }
                }
            }
            echo '</td>' . "\n\t\t\t" . '</tr>';
            mysql_free_result($result);
        }
        echo "\n\t\t" . '</table>';
        return 1;
    }
			
	// ================================
	// = Creation de la chaine BBcode =
	// ================================//
	
	function HOF_bbcode ($Table_name,$Table_label,$Title,$OGSpy_Table,$NbItems,$b1,$b2,$b3)
	{
		if (!isset($table_prefix))global $table_prefix;
		if (!isset($db))	global $db;
		if (!isset($lien))	global $lien;
		if (!isset($bbcode))	global $bbcode;

		for ($NoBld=0;$NoBld<=$NbItems;$NoBld ++)
		{
			$sql ="select max($Table_name[$NoBld]) ,user_name 
			      from ".$table_prefix.$OGSpy_Table.", ".TABLE_USER."
			      where ".TABLE_USER.".user_active and ".TABLE_USER.".user_id=".$table_prefix.$OGSpy_Table.".user_id"
						." group by user_name order by 1 desc";
			
			$result = $db->sql_query($sql);
			$val = -1;
			$premiere_fois = 0;
			$bbcode .= "";
			
			while ($row = mysql_fetch_array($result, MYSQL_NUM)) 
			{
			   	$val = $row[0];
			    if($premiere_fois == 1)
				{
				    if ($val_max > $val) 
					{
						break;
					}
					$bbcode .= ", ".$row[1];
				}
			    else
				{
					$premiere_fois=1;
					$val_max = $row[0];
					if($b1=='')  $bbcode .= " - ".$Table_label[$NoBld];
					else  $bbcode .= " - [color=".$b1."]".$Table_label[$NoBld]."[/color]";

					if($b2=='')  $bbcode .= $row[0];
					else  $bbcode .= " : [color=".$b2."]".$row[0]."[/color]";

					if($b3=='')  $bbcode .= $row[1];
					else  $bbcode .= " : [color=".$b3."]".$row[1];
				}

			}
			
			$bbcode .="[/color]\n";
			mysql_free_result($result);
		}
		
		return "";
	}

    // =================================
    // = Sauvegarde des valeurs BBCode =
    // =================================
    function Sauve_BBCode($bbcode_1, $bbcode_2, $bbcode_3, $bbcode_4)
    {
        global $db;
        if (!isset($bbcode_t)) { global $bbcode_t; }
        if (!isset($bbcode_o)) { global $bbcode_o; }
        if (!isset($bbcode_r)) { global $bbcode_r; }
        if (!isset($bbcode_l)) { global $bbcode_l; }
        
        $request = "UPDATE ".TABLE_BTHOF_CONF." SET bbcode_t='$bbcode_1',bbcode_o='$bbcode_2',bbcode_r='$bbcode_3',bbcode_l='$bbcode_4'";
        $result = $db->sql_query($request);

        $bbcode_t = $bbcode_1;
        $bbcode_o = $bbcode_2;
        $bbcode_r = $bbcode_3;
        $bbcode_l = $bbcode_4;
    }

    // ===================================
    // = Reccupere les valeurs de bbcode =
    // ===================================//
    function Get_BBCode()
    {
        global $db;
        if (!isset($bbcode_t)) { global $bbcode_t; }
        if (!isset($bbcode_o)) { global $bbcode_o; }
        if (!isset($bbcode_r)) { global $bbcode_r; }
        if (!isset($bbcode_l)) { global $bbcode_l; }

        $request = "SELECT bbcode_t,bbcode_o,bbcode_r,bbcode_l FROM ".TABLE_BTHOF_CONF;
        $result  = $db->sql_query($request);
        $val     = mysql_fetch_array($result);
        $bbcode_t = $val[0];
        $bbcode_o = $val[1];
        $bbcode_r = $val[2];
        $bbcode_l = $val[3];
    }

    // ==================================
    // = Sauvegarde des valeurs d'Admin =
    // ==================================
    function Save_Adm($icon)
    {
        global $db;
        if (!isset($icon_display)) { global $icon_display; }

        $request = "UPDATE ".TABLE_BTHOF_CONF." SET icon_display_active='$icon'";
        $result  = $db->sql_query($request);
        $icon_display = $icon;
	}

    // =================================
    // = Reccupere les valeurs d'Admin =
    // =================================
    function Get_Adm()
    {
        global $db;
        if (!isset($icon_display)) { global $icon_display; }
        
        $request = "SELECT icon_display_active FROM ".TABLE_BTHOF_CONF;
        $result  = $db->sql_query($request);

        $val = mysql_fetch_array($result);
        $icon_display = $val[0];
    }

    //-----------------------------------		
    //Update table bthof_flottes 
    //-----------------------------------
    function Update_Flotte()
    {
        global $db;
        $sql = 'DELETE FROM ' . TABLE_BTHOF_FLOTTES . '';
        $resultat = mysql_query ($sql);
            
        // Controle de l'existance du mod flotte et de son activation.
        /* inutile maintenant puisque appelé seulement par la page flotte quand le mod est installé
        $query = "SELECT active FROM `".TABLE_MOD."` WHERE `title`='Flottes'";
        $result = $db->sql_query($query);
        $modflotte = $db->sql_fetch_row($result);
        if ($modflotte[0] == "1")
          {*/
          // Je suis quasiment sur qu'on peux faire sans cette table ... à voir !!
        $req = mysql_query ("SELECT SUM(PT) as PT, SUM(GT) AS GT, SUM(CLE) AS CLE, SUM(CLO) AS CLO, SUM(CR) AS CR, SUM(VB) AS VB, SUM(VC) AS VC, SUM(REC) AS REC, SUM(SE) AS SE, SUM(BMD) AS BMD, SUM(DST) AS DST, SUM(EDLM) AS EDLM, SUM(TRA) AS TRA, SUM(SAT) AS SAT,user_id FROM ".TABLE_FLOTTES." GROUP BY user_id");
        while($resultat = mysql_fetch_array ($req))
        {
            $resultat = "INSERT INTO ".TABLE_BTHOF_FLOTTES." (user_id, PT, GT, CLE, CLO, CR, VB, VC, REC, SE, BMD, DST, EDLM, TRA, SAT) VALUES ('$resultat[user_id]', '$resultat[PT]', '$resultat[GT]', '$resultat[CLE]', '$resultat[CLO]', '$resultat[CR]', '$resultat[VB]', '$resultat[VC]', '$resultat[REC]', '$resultat[SE]', '$resultat[BMD]', '$resultat[DST]', '$resultat[EDLM]', '$resultat[TRA]', '$resultat[SAT]')";
            $resultat = mysql_query($resultat);
        }
          //}
    }
    
    function url_exists($url) {
        //url local :
        if (file_exists($url)) {
            return true;
        }
        
        //url distant :
        if(function_exists('curl_init')) {
        // Version php 4.x supported
            $handle   = curl_init($url);
            if (false === $handle)
            {
                return false;
            }
            curl_setopt($handle, CURLOPT_HEADER, false);
            curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
            curl_setopt($handle, CURLOPT_NOBODY, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
            $connectable = curl_exec($handle);
            curl_close($handle);   
            return $connectable;
        } else {
          //version php 5
            $file_headers = get_headers($url);
            if (preg_match("|200|",$file_headers[0])) {
                return true;
            } else {
                return false;
            }
        }
    }

    function aff_img($imag)
    {
        global $lien;
        //echo $lien . $imag;
        if(url_exists($lien . $imag)) {
            // elle existe donc on l'affiche
            echo "<img src='" . $lien . $imag . "' /><br />";
        } else {
            //affichage des images du mod par défaut
            echo "<img src='mod/bthof/pictures/".$imag."'/><br />";
        }
    }


?>
