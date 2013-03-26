<?php
/* ***************************************************************************** *
 *	Filename      :  function.php Version 1.0
 *	Author        :  erikosan / Savinien Cyrano (Univers 14)
 *	Contributor   :  Pitch314
 *	Mod OGSpy     :  Building & Techno HOF
 *  Modifications :
 *   - 11/02/2013 par Pitch314 : reformatage et correction erreur html
 *   - 23/02/2013 par Pitch314 : Optimation HOF production.                
 * **************************************************************************** */
 
/** $Id: functions.php  2008-03-08  ericc $ **/
/**
* @file functions.php
*
* functions.php Défini les fonctions du mod
*
* @package [MOD] bt_hof
* @author  erikosan / Savinien Cyrano (Univers 14)
* @created 2007
* @version 1.0
* @modified 23/02/2013
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
        require_once("includes/ogame.php");
        
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
        
        $sql = "SELECT DISTINCT u.user_id,user_name,`off_ingenieur`,`off_geologue`, `NRJ`, `Plasma` ".
               "FROM ". TABLE_USER ." u JOIN ". TABLE_USER_TECHNOLOGY ." b ".
                    "ON u.user_id = b.user_id ".
               "WHERE user_active='1'";
        $result = $db->sql_query($sql);
        $nplayer=0;
        //Début boucle sur joueur
        // while ($player = mysql_fetch_array($result, MYSQL_NUM))
        while ($player = $db->sql_fetch_row($result))
        {
            $metal_heure   = 0;
            $cristal_heure = 0;
            $deut_heure    = 0;
            
            list($user_id, $user_name, $off_ingenieur, $off_geologue, $NRJ, $plasma) = $player;
            $nb_planet = find_real_nb_planete_user($user_id);
// echo $user_name.' ing '.$off_ingenieur.' geo '.$off_geologue.' nrj '.$NRJ.' plasma '.$plasma.'<br />';
            if ($nb_planet == 0) { //Si le joueur n'a pas de planète encore répertoriée
                continue;
            } 

         // Récupération des informations sur les mines du joueur
            $sql = 'SELECT DISTINCT planet_id,planet_name,coordinates,`fields`,'.
                        'temperature_min,temperature_max,Sat,M,C,D,CES,CEF,'.
                        'M_percentage, C_percentage, D_percentage, '.
                        'CES_percentage, CEF_percentage, Sat_percentage '.
                   'FROM '.TABLE_USER_BUILDING. 
                  ' WHERE user_id = '.$user_id.' and planet_id < 199 '.
                  'ORDER BY planet_id';
            $quet = $db->sql_query($sql);
            $user_building = array_fill(1, $nb_planet, $planet);

        // Boucle sur les systèmes d'un joueur
            // while ($row = mysql_fetch_assoc($quet))
            while ($row = $db->sql_fetch_assoc($quet))
            {

                $production_CES = ($row['CES_percentage'] / 100) * floor(production("CES", $row['CES'], $off_ingenieur));
                $production_CEF = ($row['CEF_percentage'] / 100) * floor(production("CEF", $row['CEF'], $off_ingenieur, $row['temperature_max'], $NRJ));
                $production_SAT = ($row['Sat_percentage'] / 100) * floor(production_sat($row['temperature_min'], $row['temperature_max'], $off_ingenieur )) * $row['Sat'];
                $prod_energie   = $production_CES + $production_CEF + $production_SAT;

                $consommation_M = ($row['M_percentage'] / 100) * ceil(consumption("M", $row['M']));
                $consommation_C = ($row['C_percentage'] / 100) * ceil(consumption("C", $row['C']));
                $consommation_D = ($row['D_percentage'] / 100) * ceil(consumption("D", $row['D']));
                $cons_energie   = $consommation_M + $consommation_C + $consommation_D;

                if ($cons_energie == 0) {
                    $cons_energie = 1;
                }
                $ratio = floor(($prod_energie / $cons_energie) * 100) / 100;
                if ($ratio > 1) {
                    $ratio = 1;
                }
                $metal_heure   = $metal_heure   + floor((production("M", $row['M'], $off_geologue,0,0,$plasma) * $ratio));
                $cristal_heure = $cristal_heure + floor((production("C", $row['C'], $off_geologue,0,0,$plasma) * $ratio));
                $deut_heure    = $deut_heure    + floor((production("D", $row['D'], $off_geologue, $row['temperature_max']) * $ratio));
                $deut_heure    = $deut_heure - (floor(consumption("CEF", $row['CEF']) * $row['CEF_percentage'] / 100));

// echo '<p><b>'.$row['planet_name'].'</b><br />';
// echo 'CES='.$production_CES.' CEF='.$production_CEF.' SAT='.$production_SAT;
// echo ' cons_M='.$consommation_M.' cons_C='.$consommation_C.' cons_D='.$consommation_D;
// echo ' ratio='.$ratio.'<br />';
// echo 'M='.(production("M", $row['M'], $off_geologue,0,0,$plasma) * $ratio).'C='.(production("C", $row['C'], $off_geologue,0,0,$plasma) * $ratio);
// echo ' D+='.(production("D", $row['D'], $off_geologue, $row['temperature_max']) * $ratio);
// echo ' D-='.(consumption("CEF", $row['CEF']) * $row['CEF_percentage'] / 100);
// echo '</p>';
            }
            $production_joueur[$nplayer] = $user_name;
            $production_metal[$nplayer]     = 24 * $metal_heure;
            $production_cristal[$nplayer]   = 24 * $cristal_heure;
            $production_deuterium[$nplayer] = 24 * $deut_heure;
            $production_total[$nplayer]     = 24 * ($metal_heure + $cristal_heure + $deut_heure);
            
            $nplayer ++;
        } //Fin boucle joueur
        if ($quet != NULL) {
            mysql_free_result($quet);
        }
        if ($result) {
            mysql_free_result($result);
        }
        return $nplayer;
    }

    // ===========================================
    // = Calcul du classement pour une catégorie =
    // ===========================================//
    function Create_HOF($Table_name, $Table_label, $Table_icon, $Title,
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
        print("<tr><th width='150px'><font color='#00F0F0'>".$Title."</font></th><th width='50px'><font color='#00F0F0'>Max</font></th><th width='300px'><font color='#00F0F0'>Joueur(s)</font></th>");
        if ($Title != "Flottes" and $Title != "Technologies") {
            print ("<th width='50px'><font color='#00F0F0'>Cumul&nbsp;Total</font></th><th width='300px'><font color='#00F0F0'>Joueur(s)</font></th>");
        }
        print ("</tr>");
        print ("<tr> <td width=\"30px\">&nbsp;</td> </tr>");

        //Pour chaque Batiment/techno/flotte
        for ($NoBld=0 ; $NoBld <= $NbItems ; $NoBld ++)
        {
          //Requète SQL pour récupérer la valeur Max de chaque type et le nom du joueur associé classé par ordre décroissant			
            $sql = "SELECT MAX($Table_name[$NoBld]) ,user_name FROM ".$table_prefix.$OGSpy_Table." T JOIN ".TABLE_USER.
                   " U ON U.user_id = T.user_id WHERE U.user_active='1' GROUP BY user_name ORDER BY 1 DESC";
            $result = $db->sql_query($sql);
            
          //Requète SQL pour récupérer le total par joueur classé par ordre décroissant
            $sql2 ="SELECT SUM($Table_name[$NoBld]) ,user_name FROM ".$table_prefix.$OGSpy_Table." T JOIN ".TABLE_USER.
                   " U ON U.user_id = T.user_id WHERE U.user_active='1' GROUP BY user_name ORDER BY 1 DESC";
            $result2 = $db->sql_query($sql2);

            $val = -1;
            $premiere_fois = 0;
            //while ($row = mysql_fetch_array($result, MYSQL_NUM))
            //while ($row = $db->sql_fetch_row($result))
            $row = $db->sql_fetch_row($result);
            do {
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
                        aff_img($Table_icon[$NoBld],$Table_label[$NoBld]);
                    } else {
                        echo "&nbsp;";
                    }
                    echo $Table_label[$NoBld] . '</td>' . "\n\t\t\t\t";
                    echo '<td style=\'color : #FF80F0; background-color : #273234; text-align: center; \'>' . $row[0] . '</td>' . "\n\t\t\t\t";
                    echo '<td style=\'color : #FFFFF0; background-color : #273234; text-align: center; \'>' . $row[1];
                }
            } while (($row = $db->sql_fetch_row($result)));
            
            if ($Title != "Flottes" and $Title != "Technologies") 
            {
                print("</td>");
                $val = -1;
                $flag = 0;
                $row2 = mysql_fetch_array($result2);
                //while ($row2 = mysql_fetch_array($result2)) 
                do {
                    $val = $row2[0];
                    if ($val == 0) {
                        $row2[1] = '-';
                    }
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
                } while ($row2 = mysql_fetch_array($result2)) ;
            }
            echo '</td>' . "\n\t\t\t" . '</tr>';
            mysql_free_result($result);
            mysql_free_result($result2);
        }
        echo "\n\t\t" . '</table>';
        return 1;
    }

    // ================================
    // = Creation de la chaine BBcode =
    // ================================//
    function HOF_bbcode($Table_name, $Table_label, $Title, $OGSpy_Table, $NbItems,
                        $b1, $b2, $b3)
    {
        if (!isset($table_prefix)) { global $table_prefix; }
        if (!isset($db))           { global $db; }
        if (!isset($lien))         { global $lien; }
        if (!isset($bbcode))       { global $bbcode; }

        for ($NoBld=0;$NoBld<=$NbItems;$NoBld ++)
        {
            $sql = "SELECT MAX($Table_name[$NoBld]) ,user_name FROM ".$table_prefix.$OGSpy_Table." T JOIN ".TABLE_USER.
                   " U ON U.user_id = T.user_id WHERE U.user_active='1' GROUP BY user_name ORDER BY 1 DESC";
            //echo $NoBld .' : '.$sql.'<br />';
            $result = $db->sql_query($sql);
            $val = -1;
            $premiere_fois = 0;
            $bbcode .= "";

            //while ($row = mysql_fetch_array($result, MYSQL_NUM))
            while ($row = $db->sql_fetch_row($result))
            {
                $val = $row[0];
                if($premiere_fois != 0)
                {
                    $premiere_fois++;
                    if ($val_max > $val) {
                        break;
                    }
                    $bbcode .= ", ".$row[1];
                } else {
                    $premiere_fois++;
                    $val_max = $row[0];
                    if($b1=='') {
                        $bbcode .= " - ".$Table_label[$NoBld];
                    } else {
                        $bbcode .= " - [color=".$b1."]".$Table_label[$NoBld]."[/color]";
                    }
                    if($b2=='') {
                        $bbcode .= $row[0];
                    } else {
                        $bbcode .= " : [color=".$b2."]".$row[0]."[/color]";
                    }
                    if($b3=='') {
                        $bbcode .= $row[1];
                    } else {
                        $bbcode .= " : [color=".$b3."]".$row[1];
                    }
				}
			}
            if ($premiere_fois!=0) {
                $bbcode .= "[/color]\n";
            }
			mysql_free_result($result);
		}
		
		return "";
	}

    function Mine_HOF_bbcode($prod_metal, $prod_cristal, $prod_deuterium, 
                             $prod_total, $prod_joueur, $b1, $b2, $b3)
    {
        if (!isset($bbcode))       { global $bbcode; }
        
        if (!is_array($prod_metal)) {
            return "";
        }
        
        $maxvalue = doublemax($prod_metal);
        if($b1=='') {
            $bbcode .= "- Métal : ";
        } else {
            $bbcode .= "- [color=".$b1."]Métal : [/color]";
        }
        if($b2=='') {
            $bbcode .= number_format($maxvalue['m'], 0, ',', ' ')." : ";
        } else {
            $bbcode .= "[color=".$b2."]".number_format($maxvalue['m'], 0, ',', ' ')."[/color] : ";
        }
        if($b3=='') {
            $bbcode .= $prod_joueur[$maxvalue['i']]."[/color]\n";
        } else {
            $bbcode .= "[color=".$b3."]".$prod_joueur[$maxvalue['i']]."[/color]\n";
        }

        $maxvalue = doublemax($prod_cristal);
        if($b1=='') {
            $bbcode .= "- Cristal : ";
        } else {
            $bbcode .= "- [color=".$b1."]Cristal : [/color]";
        }
        if($b2=='') {
            $bbcode .= number_format($maxvalue['m'], 0, ',', ' ')." : ";
        } else {
            $bbcode .= "[color=".$b2."]".number_format($maxvalue['m'], 0, ',', ' ')."[/color] : ";
        }
        if($b3=='') {
            $bbcode .= $prod_joueur[$maxvalue['i']]."[/color]\n";
        } else {
            $bbcode .= "[color=".$b3."]".$prod_joueur[$maxvalue['i']]."[/color]\n";
        }

        $maxvalue = doublemax($prod_deuterium);
        // arsort($prod_deuterium);
        // list($key,$val) = each($prod_deuterium);
        if($b1=='') {
            $bbcode .= "- Deutérium : ";
        } else {
            $bbcode .= "- [color=".$b1."]Deutérium : [/color]";
        }
        if($b2=='') {
            $bbcode .= number_format($maxvalue['m'], 0, ',', ' ')." : ";
        } else {
            $bbcode .= "[color=".$b2."]".number_format($maxvalue['m'], 0, ',', ' ')."[/color] : ";
        }
        if($b3=='') {
            $bbcode .= $prod_joueur[$maxvalue['i']]."[/color]\n";
        } else {
            $bbcode .= "[color=".$b3."]".$prod_joueur[$maxvalue['i']]."[/color]\n";
        }
        
        arsort($prod_total);
        $bbcode .= "\n\n[b][color=".$bbcode_t."]Classement production minière :[/color][/b]\n";

        $bbcode .= '[table cellspacing="2"]'."\n";
        $bbcode .= '[tr][td colspan="2"][color=#ff00ff][b]Production par jour[/b][/color][/td]';
        $bbcode .= '[td][color=#00ffff][b]Métal[/b][/color][/td]';
        $bbcode .= '[td][color=#00ffff][b]Cristal[/b][/color][/td]';
        $bbcode .= '[td][color=#00ffff][b]Deutérium[/b][/color][/td]';
        $bbcode .= '[td align="center"][b]Total[/b][/td][/tr]'."\n";
        
        $nb = 1;
        foreach ($prod_total as $key => $val) {
            $bbcode .= '[tr][td][color=white][b]'.$nb.'[/b][/color][/td]';
            $bbcode .= '[td]'.$prod_joueur[$key].'[/td]';
            $bbcode .= '[td align="right"][color=red][b]'.number_format($prod_metal[$key], 0, ',', ' ').'[/b][/color][/td]';
            $bbcode .= '[td align="right"][color=lightblue][b]'.number_format($prod_cristal[$key], 0, ',', ' ').'[/b][/color][/td]';
            $bbcode .= '[td align="right"][color=green][b]'.number_format($prod_deuterium[$key], 0, ',', ' ').'[/b][/color][/td]';
            $bbcode .= '[td align="right"][color=grey]'.number_format($prod_total[$key], 0, ',', ' ').'[/color][/td][/tr]'."\n";
            
            $nb++;
        }
        $bbcode .= "[/table]\n";
    
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
        
        $request = "UPDATE ".TABLE_BTHOF_CONF.
                  " SET bbcode_t='$bbcode_1', bbcode_o='$bbcode_2', bbcode_r='$bbcode_3', bbcode_l='$bbcode_4'";
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
        $resultat = mysql_query($sql);
            
        // Controle de l'existance du mod flotte et de son activation.
        /* inutile maintenant puisque appelé seulement par la page flotte quand le mod est installé
        $query = "SELECT active FROM `".TABLE_MOD."` WHERE `title`='Flottes'";
        $result = $db->sql_query($query);
        $modflotte = $db->sql_fetch_row($result);
        if ($modflotte[0] == "1")
          {*/
          // Je suis quasiment sur qu'on peux faire sans cette table ... à voir !!
        $req = mysql_query("SELECT SUM(PT) as PT, SUM(GT) AS GT, SUM(CLE) AS CLE, SUM(CLO) AS CLO, SUM(CR) AS CR, SUM(VB) AS VB, SUM(VC) AS VC, SUM(REC) AS REC, SUM(SE) AS SE, SUM(BMD) AS BMD, SUM(DST) AS DST, SUM(EDLM) AS EDLM, SUM(TRA) AS TRA, SUM(SAT) AS SAT,user_id FROM ".TABLE_FLOTTES." GROUP BY user_id");
        while($resultat = mysql_fetch_array($req))
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
            if (false == $handle)
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

    function aff_img($imag, $labelimg)
    {
        global $lien;
        //echo $lien . $imag;
        if(url_exists($lien . $imag)) {
            // elle existe donc on l'affiche
            echo "<img src='" . $lien . $imag . "' alt='".$labelimg."' /><br />";
        } else {
            //affichage des images du mod par défaut
            echo "<img src='mod/bthof/pictures/".$imag."'/><br />";
        }
    }
    
    /**
     * Find the number of planet of an user.
     *
     * @param   user id in database
     * @return  the number of planet
     */
    function find_real_nb_planete_user($user_id)
    {
        global $db;

        $request  =  "SELECT count(planet_id) ";
        $request .= " FROM " . TABLE_USER_BUILDING;
        $request .= " WHERE user_id = " . $user_id;
        $request .= " AND planet_id < 199 ";
        $request .= " ORDER BY planet_id";
        
        $result = $db->sql_query($request); 
      //result is alway an (1,1)array even if user_id doesn't exist
        $tmp = $db->sql_fetch_row();
        return $tmp[0];
    }

    /**
     * (Matlab max), find highest value of an array and return also the index.
     *
     * @param   associative array
     * @return  array ('m'=>highest_value, 'i'=>its index)
     */
    function doublemax($mylist){
        if (!is_array($mylist)) {
            return NULL;
        }
        $maxvalue = max($mylist);
        while(list($key, $value) = each($mylist)) {
            if($value == $maxvalue) {
                $maxindex = $key;
            }
        }
        return array("m"=>$maxvalue,"i"=>$maxindex);
    }
function downloadFile($url, $path) {

  $newfname = $path;
  $file = fopen ($url, "rb");
  if ($file) {
    $newf = fopen ($newfname, "wb");
    if ($newf) {
        while(!feof($file)) {
          fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
        }
        fclose($newf);
    }
    fclose($file);
  }
}
/**
* Lit les n premiers octect/caractère d'un fichier.
* @param string $url L'emplace du fichier ou son adresse URL
* @param int $lenght La longueur en octet lu. (Attention à un longueur trop grande (>8192 soit 4ko))
* @return NULL si pas lu, sinon la caractère lu.
*/
function read_part_file($url, $lenght) {
  
  $file = fopen($url, "rb");
  if ($file) {
    $result = fread($file, $lenght);
    fclose($file);
    return $result;
  }
  return NULL;
}


?>
