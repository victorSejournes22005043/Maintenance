<?php 
		
	$VERSION = "2012.11.25";
	$ADRSITE = "http://ugselweb.org";
	
	if (!(isset($UGSELNOM))) $UGSELNOM = "";
	if (!(isset($UGSELNOMDEP))) $UGSELNOMDEP = "";
	if (!(isset($HOSTNAME))) $HOSTNAME = "localhost";
	if (!(isset($UTILISATEUR))) $UTILISATEUR = "root";  
	if (!(isset($MDP))) $MDP = "";
	if (!(isset($BDD))) $BDD = "";
	if (!(isset($LIGNES_PAR_PAGE))) $LIGNES_PAR_PAGE = 500;
	if (!(isset($TAILLE))) $TAILLE = 4;
	if (!(isset($TRANSFERT_DONNEES))) $TRANSFERT_DONNEES = "Bdd";
	if (!(isset($COULEUR))) $COULEUR = 0;
	if (!(isset($SON))) $SON = "Non";
	if (!(isset($CONSULTATION))) $CONSULTATION = "Oui";
	if (!(isset($QUOTA))) $QUOTA = 20*1024*1024;
	if (!(isset($PURGE))) $PURGE = 0;
	if (!(isset($LICENCES))) $LICENCES = "Non";
	if (!(isset($REQUETES))) $REQUETES = "Non";
	
	$Couleurs = Array(
		Array("Océan"    ,"darkblue","White","White","aliceblue","#C0DBEC","#DDDDDD","#EEEEEE","#C0DBD0","#a0e0e0","#e0a0ff","indigo","Green","Red","#000080","darkblue","#FFFF99"),
		Array("Campagne" ,"darkolivegreen","White","White","lightyellow","#CCCC66","#DEDFA5","#FFF8DC","#CCCC00","#CCCC99","#CCCC33","Olive","Green","Red","darkolivegreen","darkolivegreen","#CCCC66"),
		Array("Terre"    ,"#CC6600","White","White","PapayaWhip","#FFCC66","#FFCC99","#FFF8DC","#CCCC00","#FFCC33","Gold","#8B4500","Green","Red","#CC6600","#CC6600","#FFCC66"),
		Array("Cendre"   ,"Black","White","white","Gainsboro","DarkGrey","White","Silver","#708090","DarkGrey","#696969","Black","Green","Red","Black","Black","White")
	);
	
Function JoueSon($Son) {
	Global $SON;
	if ($SON == "Oui") {if (file_exists($Son)) echo "<EMBED width='0' height='0' src='$Son' loop='false' autostart='true' hidden='true'>";}
}	
	
Function EcritParam($par) {
 	Global $TRANSFERT_DONNEES;
	if ($TRANSFERT_DONNEES == "Url") return "par=".addslashes(urlencode($par))."&";
	if ($TRANSFERT_DONNEES == "Bdd") {
		bf_mysql_query("UPDATE Connexions SET Param = '".addslashes(urlencode($par))."' WHERE Session = '".session_id()."'");
		return "par=0&";
	}
}

Function MajConnexions($id = "") {
	Global $Consult;
	$tpsAdm = 600; $tpsEtab = 300; $tpsConsult = 60; 
	$temps_actuel = date("U");
	bf_mysql_query('UPDATE Connexions SET Ip = "Out" WHERE Id = "Admin" AND Temps < "'.($temps_actuel - $tpsAdm).'"');
	bf_mysql_query('UPDATE Connexions SET Ip = "Out" WHERE Id <> "Admin" AND Id <> "Consultant" AND Temps < "'.($temps_actuel - $tpsEtab).'"');
	bf_mysql_query('UPDATE Connexions SET Ip = "Out" WHERE Id = "Consultant" AND Temps < "'.($temps_actuel - $tpsConsult).'"');
	$req = bf_mysql_query('SELECT Session FROM Connexions WHERE Ip = "Out"');if (!(!$req)) { while ($data = mysql_fetch_array($req)) { unlink(session_save_path().'/sess_'.$data['Session']);} }	
	bf_mysql_query('DELETE FROM Connexions WHERE Ip = "Out"');
	if ((!(file_exists(session_save_path().'/sess_'.session_id()))) && (!($Consult)) ) logout("Veuillez vous reconnecter.");
	$req = bf_mysql_query('SELECT Temps, Ip FROM Connexions WHERE Session = "'.session_id().'"');
	if (!(!$req)) {
		$data = mysql_fetch_array($req);
		if (!($data)) bf_mysql_query('INSERT INTO Connexions VALUES("'.$_SERVER['REMOTE_ADDR']. '","'.session_id(). '","'.$temps_actuel.'","'.$id.'",Now(),"")');
		else bf_mysql_query('UPDATE Connexions SET Id = "'.$id.'", Temps = "'.$temps_actuel.'", Depart = Depart WHERE Session = "'.session_id().'"');
	}
}
	
function bf_stop($message = "Echec de la connexion.") {
	logout($message);
}

function bf_mysql_connect($MonUtilisateur = "", $MonMdp = "") {
	global $HOSTNAME, $UTILISATEUR, $MDP;
	if ($MonUtilisateur == "") $MonUtilisateur = $UTILISATEUR;
	if ($MonMdp == "") $MonMdp = $MDP;
	$retour = @mysql_connect($HOSTNAME, $MonUtilisateur, $MonMdp);
	if (!(is_resource($retour))) bf_stop("Echec de la connexion avec le serveur.");
	return $retour;
	bf_mysql_close($retour);
}
	
function bf_mysql_select_db($MaBdd = "", $link_identifier = 0, $MonUtilisateur = "", $MonMdp = "" ){
	global $BDD;
	if ($MaBdd == "") {$MaBdd = $BDD; $ext = 0 ; $mysql_connect = bf_mysql_connect();} else { $ext = 1; $mysql_connect = bf_mysql_connect($MonUtilisateur, $MonMdp);}
	if ($link_identifier <> 0) $retour = @mysql_select_db($MaBdd, $link_identifier); else $retour = @mysql_select_db($MaBdd, $mysql_connect);
	if (!($retour) && ($ext == 0)) bf_stop("Echec de la connexion. <BR><BR>".urlencode("Le site est actuellement fermé."));
	return $retour;
	bf_mysql_close($mysql_connect);
}	

function bf_mysql_query ($query, $link_identifier = 0, $ALocker = "", $MaBdd = "", $MonUtilisateur = "", $MonMdp = "") {
	Global $Adm, $action, $BDD;
	
	if ($MaBdd == "") {
		$MaBdd = $BDD;
		$mysql_connect = bf_mysql_connect();
		bf_mysql_select_db($MaBdd); 
	} else {
		$mysql_connect = bf_mysql_connect($MonUtilisateur, $MonMdp);
		bf_mysql_select_db($MaBdd, $link_identifier, $MonUtilisateur, $MonMdp); 
	}
	
	if ( (!($Adm)) && ($action != "logout") ) {
		$req = @mysql_query("SELECT `Maintenance` FROM `Paramweb`", $mysql_connect);
		if ((!(!$req)) && (mysql_num_rows($req) > 0)) {
			$data = mysql_fetch_assoc($req);
			mysql_free_result($req);
			if (($data['Maintenance']) == 1) echo bf_stop("Echec de la connexion. <BR><BR>".urlencode("Le site est actuellement fermé."));
		} else {
			bf_stop("Echec de la connexion. <BR><BR>".urlencode("Le site est actuellement fermé."));
		}
	}
	
	if (($ALocker != "")) {
		if (!(is_array($ALocker))) $ALocker = array("$ALocker");
		$arrayLock = array("`Sports`", "`Catégories`", "`Epreuves`", "`Groupes`", "`Secteurs`", "`Etablissements`", "`Licenciés`", "`Compétitions`", "`Epreuves Compétitions`", "`Tours Epreuves Compétitions`", "`Participations`", "`Equipes`", "`Paramètres`","`Paramweb`"); 
		$rr = mysql_query("LOCK TABLES ".implode(" WRITE, ", $ALocker)." WRITE, " .implode(" READ, ", array_diff($arrayLock,$ALocker)). " READ");
	}
	
	if ($query != "") {
		if ($link_identifier <> 0) $retour = @mysql_query($query, $link_identifier); else $retour = @mysql_query($query, $mysql_connect);
	} else $retour = true;
	
	return $retour;
	bf_mysql_close($mysql_connect);
	
}

function bf_mysql_close($link_identifier = 0){
	if ($link_identifier <> 0) $retour = @mysql_close($link_identifier); else @mysql_close();
	return $retour;
}

function getTime() {
    static $chrono = false, $deb;
    if ($chrono === false) {
        $deb = array_sum(explode(' ',microtime()));
		$chrono = true;
        return NULL;
    } else {
        $chrono = false;
        $fin = array_sum(explode(' ',microtime()));
		return round(($fin - $deb), 3);
	}
}
 
function CompteEnr($matable, $maclausewhere = "") {
	$mastr = "SELECT COUNT(*) FROM `$matable`";
	if ($maclausewhere != "") $mastr = $mastr." WHERE $maclausewhere";
	$req = bf_mysql_query($mastr); 
	if (!(!($req))) {
		$res = mysql_fetch_array($req);
		if ($res) Return($res[0]); Else Return("?");
	}
	else return(-1);
}
  
function ConstruitZone($zone) {
	for ($i = 0; $i < count($zone); $i++) {	
		echo "<input type='hidden' name=".$zone[$i][0]." value='".$zone[$i][1]."'>\n";
	}
}
 
function CalculCat($ChpRetour, $DateNaiss, $Sexe, $Sport) {
	$req = bf_mysql_query("SELECT CatLibelCourt FROM Catégories WHERE (STR_TO_DATE('$DateNaiss','%Y-%m-%d') BETWEEN CatDateDéb AND CatDateFin) AND CatSexCode =".$Sexe." And CatSpoCode = ".$Sport." order by Ordre"); 
	$res = mysql_fetch_array($req);
	if ($res) Return($res[0]); Else Return("?");
}

function TrouveSport($Compet, $ChpRetour) {
	$req = bf_mysql_query("SELECT * FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode WHERE CompetCode = $Compet"); 
	if (!(!$req)) {
		$res = mysql_fetch_array($req);
		if (!(!$res)) Return($res["$ChpRetour"]); Else Return("?");
	}
}

function TrouveParamweb($ChpRetour, $Valdef=0) {
	$ret = $Valdef;
	$req = bf_mysql_query("SELECT `$ChpRetour` FROM `Paramweb`"); 
	if ((!(!$req)) && (mysql_num_rows($req) > 0)) {
		$res = mysql_fetch_assoc($req); 
		if (!(!$res)) $ret = $res["$ChpRetour"];
	}
	Return $ret;
}

function TrouveMax($Sql) {
	$req = bf_mysql_query($Sql); 
	$res = mysql_fetch_array($req);
	if ($res) Return($res[0]); Else Return("0");
}

function listederoulante($ListeNom, $ListePrompt, $ListeSql, $ListeChampsAff, $ListeChampsFormat, $ListeClé, $ListeInit, $Largeur="100%") {
	If ($Largeur == "") $Largeur="100%"; else $Largeur = $Largeur."px";
	if (!(is_array($ListeSql))) {
		echo "<select size=1 name='$ListeNom' CLASS ='listederoulante' style='width: $Largeur;'>";
		if ($ListePrompt <> "") echo "<option value=''>$ListePrompt</option>";
		$req = bf_mysql_query($ListeSql);
		while ($res = mysql_fetch_array($req)) {
			echo $res;
			echo "<option value='$res[$ListeClé]'";
			if (isset($ListeInit)) {if($res["$ListeClé"] == "$ListeInit") echo " selected";} 
			$option = "";
			for ($i = 0; $i < count($ListeChampsAff); $i++) {
				if ($ListeChampsAff[$i] == "-") $option = $option."- ";
				else if ($ListeChampsFormat[$i] == "") $option = $option.$res[$ListeChampsAff[$i]]." "; else $option = $option.sprintf($ListeChampsFormat[$i], $res[$ListeChampsAff[$i]])." ";
			}
			echo ">$option</option>\n";
		}
		echo "</select>";
	} else {
		echo "<select size=1 name='$ListeNom' CLASS ='listederoulante' style='width: $Largeur;'>";
		if ($ListePrompt <> "") echo "<option value=''>$ListePrompt</option>";
		$MonTab = array_values($ListeSql);
		for( $i = 0; $i < count($ListeSql); $i++ ) {
			echo "<option value='";
			if ( (!(isset($ListeClé))) || $ListeClé == "") echo array_search($MonTab[$i],$ListeSql); else echo $ListeClé[$i];
			echo "'";
			if (isset($ListeInit)) if($MonTab[$i] == $ListeInit) echo " selected"; 
			echo "> $MonTab[$i] </option>\n";
		}
		echo "</select>";
	}
}

function debut_html($AffDeconnexion = false) {
	Global $PHP_SELF, $VERSION, $UGSELNOM, $UGSELNOMDEP, $action, $Etab, $Adm, $menu, $sousmenu, $CONSULTATION, $BDD, $ADRSITE;
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
	if (($action == "logon") || (($action == "logout")) ) {
		echo "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>\n";
	} else {
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0//EN'>\n";
	}
	echo "<html>\n";
	echo "<head>\n";
	echo "<title>UGSEL Web</title>\n";
	echo "<link rel='shortcut icon' type='image/x-icon' href='".$ADRSITE."/favicon.ico' />\n";
	echo "<link rel='icon' type='image/png' href='".$ADRSITE."/favicon.png' />\n";
	style_html();
	echo "</head>\n";
	if (($action == "logon") || (($action == "logout")) ) {
		echo "<body onLoad='document.forms[\"formlogon\"].elements[\"login\"].focus()'>\n"; 
	} else {
		if (($sousmenu == "individuels") && ($action == "ajoutedata")) echo "<body onLoad='document.forms[\"formaffichelignes\"].elements[\"ParLicCode\"].focus()'>\n"; else echo "<body>\n";
	}
	echo "<DIV id = 'entete'>";
	echo "<TABLE class = 'tabledeb'>";
	echo "<TR CLASS = 'trdeb'>";
    echo "<TD Width = '80%'>&nbsp; UGSEL Web &nbsp;&nbsp;&nbsp;$UGSELNOM&nbsp;&nbsp;$UGSELNOMDEP&nbsp;&nbsp;&nbsp;&nbsp;";
	
	if ($Adm) {
		$req = bf_mysql_query("SELECT COUNT(Session) AS Nbre FROM Connexions");
		if (!(!($req))) {
			$res = mysql_fetch_array($req);
			echo $res['Nbre']." connecté";
			if ($res['Nbre'] > 1) echo "s";
		}
	}
	
	if (($action != "logon") && ($action != "logout") && (!($Adm)) && ($CONSULTATION == "Non"))  {
		if ($res = mysql_fetch_array(bf_mysql_query("SELECT * FROM Etablissements WHERE EtabNum = ".$Etab))) 
			echo sprintf('%06s',$res["EtabNum"])." - ".$res["EtabNomCourt"]." - ".$res["EtabNom"]." - ".$res["EtabVille"];
			else echo "Aucun établissement trouvé !";
	}
	echo "</TD>";
	
	echo "<TD Width = '10%' align = 'right'>";
	if (($AffDeconnexion)) {
		$pageAide = 1;
		if ($Adm) $ficAideAdm = "-Admin"; else $ficAideAdm = "";
		if ($Adm) {
			if (($menu == 'parametres') && ($sousmenu == 'sports'))        $pageAide = 8;
			if (($menu == 'parametres') && ($sousmenu == 'categories'))    $pageAide = 9;
			if (($menu == 'parametres') && ($sousmenu == 'epreuves'))      $pageAide = 10;
			if ($menu == 'etablissements')                                 $pageAide = 11;
			if ($menu == 'licencies')                                      $pageAide = 12;
			if (($menu == 'competitions') && ($sousmenu == 'references'))  $pageAide = 13;
			if (($menu == 'competitions') && ($sousmenu == 'individuels')) $pageAide = 15;
			if (($menu == 'competitions') && ($sousmenu == 'equipes'))     $pageAide = 16;
			if ($menu == 'options')                                        $pageAide = 17;
			if ($menu == 'outils')                                         $pageAide = 18;
			if ($menu == 'connexions')                                     $pageAide = 20;
			if ($menu == 'apropos')                                        $pageAide = 20;
		} else {
			if (($menu == 'competitions') && ($sousmenu == 'individuels')) $pageAide = 4;
			if (($menu == 'competitions') && ($sousmenu == 'equipes'))     $pageAide = 7;
		}
		echo "<a TARGET='_blank' href='".$ADRSITE."/UgselWeb-Documentation$ficAideAdm.pdf#page=$pageAide&pagemode=bookmarks' CLASS = 'adecon' >  Aide  </a>";
	}
	echo "</TD>";
	
	echo "<TD Width = '10%' align = 'center'>";
	if (($AffDeconnexion)) { 
		echo "<a href='$PHP_SELF?action=logout' CLASS = 'adecon'>Déconnexion</a>";
	} else {
		if ($CONSULTATION == "Non") {
			$MonUg = split('/', $_SERVER['SCRIPT_NAME']);
			echo "<a CLASS = 'adecon'; href='".$ADRSITE."/".$MonUg[1]."'>&nbsp Retour &nbsp</a>";
		}
	}
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>\n";
}

function fin_html($AffDeconnexion = false) {
	global $PHP_SELF, $VERSION, $UGSELNOM, $UGSELNOMDEP, $Adm, $action, $CONSULTATION, $ADRSITE;
	echo "</DIV>";	
	echo "<DIV id = 'pied'>";
	echo "<TABLE class = 'tablefin'>";
	echo "<TR CLASS = 'trfin'>";
	echo "<TD Width = '90%' >&nbsp; ";
	if ($Adm) echo "Page générée par le serveur le ".date("d/m/y à H:i:s ")." en ".getTime()."s" ; else echo "UGSEL Web &nbsp;&nbsp;&nbsp;$UGSELNOM&nbsp;&nbsp;$UGSELNOMDEP&nbsp;&nbsp;&nbsp;";
	echo "</TD>";
	echo "<TD Width = '10%' Align ='Center' >";
	if (($AffDeconnexion)) {
		echo "<a href='$PHP_SELF?action=logout' CLASS = 'adecon'>Déconnexion</a>";
	} else {
		if ($CONSULTATION == "Non") {
			$MonUg = split('/', $_SERVER['SCRIPT_NAME']);
			echo "<a CLASS = 'adecon'; href='".$ADRSITE."/".$MonUg[1]."'>&nbsp Retour &nbsp</a>";
		}
	}
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>\n";
	echo "</DIV>";
	echo "</body>\n";
	echo "</html>\n";
}

function style_html() {
	global $action, $taille, $tailleinf, $COULEUR, $Couleurs, $ENTRER;
	$CTexte        = $Couleurs[$COULEUR][1];
	$CTexteLien	   = $Couleurs[$COULEUR][2];
	$CTexteLienOver= $Couleurs[$COULEUR][3];
	$CFond         = $Couleurs[$COULEUR][4];
	$CFondTableTh  = $Couleurs[$COULEUR][5];
	$CFondTable1   = $Couleurs[$COULEUR][6];
	$CFondTable2   = $Couleurs[$COULEUR][7];
	$CFondFiltre   = $Couleurs[$COULEUR][8];
	$CFondEdit	   = $Couleurs[$COULEUR][9];
	$CFondSuppr	   = $Couleurs[$COULEUR][10];
	$CFondLienOver = $Couleurs[$COULEUR][11];
	$CMessInfo     = $Couleurs[$COULEUR][12];
	$CMessAlerte   = $Couleurs[$COULEUR][13];
	$CBouton       = $Couleurs[$COULEUR][14];
	$CTexteCompet  = $Couleurs[$COULEUR][15];
	$CFondCompet   = $Couleurs[$COULEUR][16];
	
	echo "<style type='text/css'>\n";
	if (( ($action == "logon") || ($action == "logout") ) && (!(isset($ENTRER))) ){
		echo "body {margin:8%; font-family: verdana, arial; font-size: $taille; color: $CTexte;}\n";
	} else {
		if ( (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== FALSE) || (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5') !== FALSE) ) { 
			echo "body {font-family: verdana, arial; font-size: $taille; color: $CTexte; background-color:$CFond;}\n";
			echo "div#entete  {left:8px; right:8px; font-family: verdana, arial; font-size: $taille; color: $CTexte; background-color:$CFond;}\n";
			echo "div#contenu {left:8px; right:8px; font-family: verdana, arial; font-size: $taille; color: $CTexte; background-color:$CFond;}\n";
			echo "div#pied    {left:8px; right:8px; font-family: verdana, arial; font-size: $taille; color: $CTexte; background-color:$CFond;}\n"; 
		} else {	
			echo "body {font-family: verdana, arial; font-size: $taille; color: $CTexte; background-color:$CFond;}\n";
			echo "div#entete  {left:8px; right:8px; top:0; position:fixed; background-color:$CFond;padding-top:0.5em; padding-bottom:0.5em;}\n"; 
			echo "div#contenu {left:8px; right:8px; padding-top:3.5em; padding-bottom:2em;}\n";
			echo "div#pied    {left:8px; right:8px; bottom:0; position:fixed; background-color:$CFond;padding-top:0.5em;padding-bottom:0.5em;}\n";
		}
	}
	
	echo "form {margin-top: 0; margin-bottom: 0;} \n";
	echo "table {border-collapse:separate; font-size: $taille;} \n";
	echo "table th {padding:5px;background-color: $CFondTableTh; font-weight:normal;} \n";
	echo "table td {padding:4px;} \n";
	echo ".tabledeb,.tablefin,.tableopt,.tableconopt,.tablemenu{width: 100%; border-collapse: collapse;} \n";
	echo ".tablemenu {width: 100%;border-collapse: collapse;} \n";
	echo ".tablemenu td {background-color:$CFondTable1;padding:4px;}\n";
	echo ".tablesousmenu {width: 100%;} \n";
	echo ".tablesousmenu td {padding:2px;} \n";
	echo ".tablecompet {width: 100%;} \n";
	echo ".tablecompet td {padding:6px; font-size:$taille; background-color:$CFondCompet; color:$CTexteCompet;} \n";
	echo ".tablecompet1 {width: 100%;} \n";
	echo ".tablecompet2 {width: 100%; font-size: $tailleinf;border-collapse: collapse;} \n";
	echo ".tablecompet2 td {padding:2px;} \n";
	echo ".tablecompets {background-color: $CFondTable2;font-size: $tailleinf;} \n";
	echo ".tablecompets td {padding:5px 2px;} \n";
	echo ".tableselecteur {font-size:$tailleinf; margin-left:15pt;} \n";
	echo ".tableselecteurEtab {margin: 10px 2px 10px 2px; background-color: $CFondTable2; Color:$CMessInfo; Width:100%; font-size:$tailleinf;border: 1px solid $CFondTableTh;} \n";
	echo ".tableselecteurEtab td {padding:7px 7px;} \n";
	echo ".tablemessage {margin-top: 10px; background-color: $CFondTable2; Color:$CMessInfo; Width:100%; font-size:$tailleinf;border: 4px double $CMessInfo;} \n";
	echo ".tablemessageerreur{margin-top: 10px; background-color: $CFondTable2; Color:$CMessAlerte; Width:100%; font-size:$tailleinf;border: 4px double $CMessAlerte;}\n";
	echo ".thfiltre,.thdercolfiltre {background-color: $CFondFiltre;} \n";
	echo ".thdercol {background-color: $CFondTableTh;} \n";
	echo ".trdeb,.trfin {background-color: $CTexte; color:white; font-size: $tailleinf;} \n";
	echo ".trcompet1,.tr1 {background-color: $CFondTable1;} \n";
	echo ".trcompet2,.tr2 {background-color: $CFondTable2;} \n";
	echo ".trsel {background-color: $CFondEdit;} \n";
	echo ".tredit {background-color: $CFondEdit;} \n";
	echo ".tredit td {padding:1px 2px;} \n";
	echo ".trimpexp {background-color: $CFondEdit;} \n";
	echo ".trimpexp td {padding:4px 2px;} \n";
	echo ".trsuppr {background-color: $CFondSuppr;} \n";
	echo ".trtotal {background-color: $CFondTableTh;} \n";
	echo ".tddercol {text-align: center;}\n";
	echo ".hr1,.hr2 {color: $CTexte; height: 1px; border:1px; width: 100%; background-color: $CTexte;} \n";
	echo "input {font-family: verdana, arial; font-size: $tailleinf;}";
	echo ".listederoulante {font-family: verdana, arial; font-size: $tailleinf; border-width:1px;}";
	echo "textarea {font-family : verdana, arial; width: 100%; font-size: $tailleinf; margin-top: 5px; margin-bottom:5px;}";
	echo "a {text-decoration:none; color: $CTexte;} \n";
	echo "a:link {text-decoration:none;} \n";
	echo "a:visited {text-decoration:none;} \n";
	echo "a:hover {text-decoration:none; background-color:$CFondLienOver; color:$CTexteLienOver;} \n";
	echo ".adecon {text-decoration:none; color:$CTexteLien} \n";
	echo ".tailleur {text-decoration:none; color:$CTexte} \n";
	echo ".inv {text-decoration:none; background-color:$CTexte; color:$CTexteLien;} \n";
	echo ".navig {text-decoration:none; color:$CTexte;} \n";
	echo ".bouton {border-color:$CBouton;font-size:$tailleinf;text-decoration:none;color:white;background-color:$CBouton;padding:0px;}\n";
	echo ".boutongrand{border-color:$CBouton;font-size:$tailleinf;text-decoration:none;color:white;background-color:$CBouton;padding:0px;}\n";
	echo ".boutonmoyen{border-color:$CBouton;font-size:$tailleinf;text-decoration:none;color:white;background-color:$CBouton;padding:0px;}\n";
	echo ".boutonpetit{border-color:$CBouton;font-size:$tailleinf;text-decoration:none;color:white;background-color:$CBouton;padding:0px;}\n";
	
	echo "@media screen {";
		echo ".filmenu, .hr2{display:none} \n";
	echo "}";
	
	echo "@media print{";
		echo "body{font-family: verdana,arial;font-size:$taille; color:black; border:1px solid #cccccc;padding:3px;}";
		echo "div#contenu {padding-top:0; padding-bottom:0;}";
		echo "div#entete, div#pied, .tabledeb,.tablefin,.tablemenu,.tablesousmenu,.tableopt,.thdercol,.thdercolfiltre,.trcompet2,.tredit,.tddercol,.bouton,.boutongrand,.boutonmoyen,.boutonpetit,.pasimprimer,.navig{display :none}";
		echo "a,.hr2 {text-decoration:none; color: black;} \n";
		echo ".tablecompet td {text-decoration:none; color:black;} \n";
		echo ".tablecompets {border-collapse: collapse;border-spacing:0pt; margin-left:auto;margin-right:auto;width:100%;font-size: $tailleinf;background-color: #EEEEEE;} \n";
		echo ".tablecompets th {padding: 2pt; color:black; font-weight:normal;} \n";
		echo ".tablecompets td {padding: 2pt; color:black; border-bottom-style: solid;border-bottom-width: 1pt;} \n";
		echo ".tableselecteur{margin-left:2pt;margin-right:2pt;width: 100%; margin-top: 0pt; margin-bottom: 0pt; font-size:$tailleinf; Color: black;} \n";
		echo ".tableselecteurEtab {color:black; font-weight:bold; border: 0px;} \n";
		echo ".trcompet1 {font-size:12pt;border-style:double;border-width:4pt;} \n";
		echo "th {font-size: $tailleinf; border-bottom-style: double;border-bottom-width: 2pt;} \n";
		echo ".filmenu {font-size:7pt;} \n";
		echo ".tablemessage, .tablemessageerreur{display:none} \n";
	echo "}";
	echo "</style>\n";
}

function logon() {
	global $PHP_SELF, $message, $Couleurs, $COULEUR, $HOSTNAME, $UTILISATEUR, $MDP, $BDD, $CONSULTATION, $UGSELNOM, $UGSELNOMDEP;
	if ($CONSULTATION == "Non") { 
		$connect = @mysql_connect($HOSTNAME, $UTILISATEUR, $MDP);
		@mysql_select_db($BDD, $connect);
		$messageAccueil = "<B>UGSEL Web</B><BR><BR><BR>Bienvenue dans l'espace d'inscription aux compétitions<BR><BR>";
		$req = @mysql_query("SELECT `Accueil` FROM `Paramweb`",$connect); 
		if ((!(!$req)) && (mysql_num_rows($req) > 0)) {
			$data = mysql_fetch_assoc($req);
			$messageAccueil = urldecode($data["Accueil"]);
		}
	}
	debut_html(false);
	echo "<form method='post' name='formlogon' action='$PHP_SELF'>";
	echo "<table bgcolor='".$Couleurs[$COULEUR][4]."' bordercolor='".$Couleurs[$COULEUR][1]."' bordercolordark = '".$Couleurs[$COULEUR][1]."' bordercolorlight = '".$Couleurs[$COULEUR][1]."' border='1' cellpadding='0' cellspacing='0' width='100%' height='80%'>";
	echo " <tr>";
	echo "   <td>";
    if ($CONSULTATION == "Non") { 
		echo "        <p align='center'<b>$messageAccueil</b></p>";
		echo "        <table CLASS = 'tableconopt'>";
		echo "            <tr>";
		echo "              <td width='50%' align='right'> Utilisateur &nbsp;</td>";
		echo "              <td width='50%' align='left'> <input type='text' name='login' value=''> </td>";
		echo "            </tr>";
		echo "            <tr><TD>&nbsp;</TD></tr>";
		echo "            <tr>";
		echo "              <td width='50%' align='right'> Mot de passe &nbsp;</td>";
		echo "              <td width='50%' align='left'> <input type='password' name='password' value = ''></td>";
		echo "            </tr>";
		echo "        </table>";
		echo "        <BR>";
		echo "        <p align='center'><input type='submit' name='action' value='Connexion' class='boutongrand'>";
		echo "        <p align='center'>$message";
	} else {
		echo "          <p align='center' <B>$UGSELNOM&nbsp;&nbsp;$UGSELNOMDEP</B></P>
						<BR>
						<p align='center' <B>Bienvenue dans l'espace d'inscription aux compétitions</B></P>
						<BR><BR>
						<p align='center'<BLINK>$message</BLINK></p>
						<BR>
						<p align='center'><input type='submit' name='ENTRER' value=' Entrer ' class='boutongrand'>";
	}
	echo "   </td>";
    echo " </tr>";
	echo "</table>";
	echo " </form>";
	fin_html();
}

function logout($message = "") {
	Global $PHP_SELF, $HOSTNAME, $UTILISATEUR, $MDP, $BDD, $UGSELNOM, $Consult;
	$_SESSION = array();
	if (isset($_COOKIE[session_name()])) @setcookie(session_name(),'', time() - 42000, '/');
	session_destroy();
	echo "<META HTTP-EQUIV=Refresh CONTENT='0; URL=$PHP_SELF?action=logon&message=$message'>";
	@mysql_close();
	die();
}

function logon_submit() {
	global $Adm, $login, $password, $PHP_SELF, $ADMINLOGIN, $ADMINMDP, $ADMINREGLOGIN, $ADMINREGMDP, $LIGNES_PAR_PAGE, $COULEUR, $SON, $BDD, $UGSELNOM;
	$loginOK = false;
	$login = substr($login,0,15);
	$password = substr($password,0,15);
	if ( isset($_POST) && (!empty($_POST['login'])) && (!empty($_POST['password'])) ) {
	if ( (($login == $ADMINLOGIN) && ($password == $ADMINMDP)) || (($login == $ADMINREGLOGIN) && ($password == $ADMINREGMDP))){
			$loginOK 			= true;
			$_SESSION['login']  = "Admin";
			$_SESSION['log  ']  = $BDD;
			$_SESSION['LignesParPage']  = $LIGNES_PAR_PAGE;
			$_SESSION['Couleur'] = $COULEUR;
			$_SESSION['Son']  = $SON;
			$view = "VoirMenu";
			$Adm = true;
			bf_mysql_query("CREATE TABLE IF NOT EXISTS `Paramweb` (`Maintenance` INT DEFAULT '1' NOT NULL, `Accueil` TEXT, `BasesExternes` VARCHAR(100), `ImpressionLic` INT DEFAULT '0' NOT NULL, `InscriptionLic` INT DEFAULT '0' NOT NULL)");
			bf_mysql_query("ALTER TABLE `Paramweb` ADD `ImpressionLic` INT DEFAULT '0' NOT NULL");
			bf_mysql_query("ALTER TABLE `Paramweb` ADD `InscriptionLic` INT DEFAULT '0' NOT NULL");
			bf_mysql_query("ALTER TABLE `Paramweb` ADD `AssUgsel` VARCHAR(25) NULL");
			$req = bf_mysql_query("SELECT `Maintenance` FROM `Paramweb`");
			if ((!($req)) || ((mysql_num_rows($req)) == 0)) bf_mysql_query("INSERT INTO `Paramweb` (`Maintenance`,`Accueil`,`ImpressionLic`,`InscriptionLic`) VALUES ('1','Identification UGSEL','0','0')");
			bf_mysql_query("CREATE TABLE IF NOT EXISTS Connexions (Ip VARCHAR(15) NOT NULL, Session VARCHAR(50), Temps bigint (16) NOT NULL default '0', Id VARCHAR(15) NOT NULL, Depart TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',`Param` TEXT, PRIMARY KEY (`Session`))"); 
			bf_mysql_query("ALTER TABLE `Compétitions` CHANGE `CompetCode` `CompetCode` INT(11)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetEqu` VARCHAR(30)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetEtat` VARCHAR(30)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetStatut` VARCHAR(30)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetObs` VARCHAR(50)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetChpSup` VARCHAR(255)");
			bf_mysql_query("ALTER TABLE `Compétitions` ADD `CompetDemLic` TEXT");
			bf_mysql_query("UPDATE `Compétitions` SET `CompetEqu`  = '0' WHERE isnull(CompetEqu)", 0, "`Compétitions`");
			bf_mysql_query("UPDATE `Compétitions` SET `CompetEtat` = '0' WHERE isnull(CompetEtat)");
			bf_mysql_query("UPDATE `Compétitions` SET `CompetStatut` = 'Inscriptions fermées' WHERE isnull(CompetStatut)");
			bf_mysql_query("ALTER TABLE `paramètres` ADD PRIMARY KEY (`ParVersion`, `ParSousVersion`)");
			bf_mysql_query("ALTER TABLE `Licenciés` ADD `LicDateDem` DATE NULL");
			bf_mysql_query("ALTER TABLE `Licenciés` ADD `LicDateValid` DATE NULL");
			bf_mysql_query("ALTER TABLE `Licenciés` ADD INDEX `LicEtabCode` ( `LicEtabCode` )");
			bf_mysql_query("UNLOCK TABLES");
			$reqtables = bf_mysql_query("SHOW TABLES FROM `$BDD`");
			if (!(!$reqtables)) {
				while ($row = mysql_fetch_row($reqtables)) {
					$arraytable = explode(' ', $row[0]);
					if ($arraytable[0] == "trans") {
						$reqtrans = bf_mysql_query('SELECT Session FROM Connexions WHERE Session = "'.$arraytable[1].'"');
						if ((!(!($reqtrans))) && ((mysql_num_rows($reqtrans)) == 0)) {
							bf_mysql_query("DROP TABLE `".$row[0]."`");	
						}
					}
				}
			}
	
			bf_mysql_query("UPDATE Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode SET EtabMemo3 = IF(RAND() > 0.33, IF(RAND() > 0.66, CONCAT(SecLibel, LOWER(EtabNomCourt), FLOOR(RAND()*100)), CONCAT(LOWER(EtabNomCourt), SecLibel, FLOOR(RAND()*100))), CONCAT(FLOOR(RAND()*100), LOWER(EtabNomCourt), SecLibel)) WHERE EtabMemo3 = '' OR EtabMemo3 IS NULL");
		
			$now= time();			
			$journow = date('w', $now);
			if ($journow >= 0) {
				$chemin  = ".";
				$handle  = @opendir($chemin);
				$datenow = mktime(0,0,0, date('m', $now), date('d', $now), date('Y', $now));
				$trouveauto = "non";
				while ($file = @readdir($handle)) {
					if( (!(is_dir("$chemin/$file"))) && (strrchr($file,".") == '.ugw') && (strstr($file, 'Auto') == True) ){
						if ((($datenow - mktime(0,0,0, date('m', filemtime("$chemin/$file")), date('d', filemtime("$chemin/$file")), date('Y', filemtime("$chemin/$file")))) / 86400) >= 7) {
							@unlink($file);
						} else {
							if (strstr($file, 'Auto'.$journow) == True) $trouveauto = "oui"; 
						}
					}		
					if( (!(is_dir("$chemin/$file"))) && (strstr($file, "Temp") == True) ) {
						if ((($datenow - mktime(0,0,0, date('m', filemtime("$chemin/$file")), date('d', filemtime("$chemin/$file")), date('Y', filemtime("$chemin/$file")))) / 86400) > 1) @unlink($file);
					}
				}	
				@closedir($handle);
			}
			
			OptimizeTables();
		
		} else {
			$req = bf_mysql_query("SELECT EtabNum, EtabNomCourt, EtabMemo3 FROM Etablissements WHERE EtabNum = ".addslashes($login));
			if ((!(!$req)) && (mysql_num_rows($req) > 0)) {
				$data = mysql_fetch_assoc($req);
				if ( (($data['EtabMemo3'] != "") && ($password == $data['EtabMemo3'])) || (($data['EtabMemo3'] == "") && ($password == $data['EtabNomCourt'])) ) {
					$loginOK            = true;
					$_SESSION['login']  = $data['EtabNum'];
					$_SESSION['log  ']  = $BDD;
					$_SESSION['LignesParPage'] = $LIGNES_PAR_PAGE;
					$_SESSION['Couleur']= $COULEUR;
					$_SESSION['Son']    = $SON;
					$view               = "VoirMenu";
					$Adm				= false;
				} 
			}
		}
	}
	echo "<HTML>";
	echo "<head>";
	if ($loginOK) echo "<META HTTP-EQUIV=Refresh CONTENT='0; URL=$PHP_SELF?action=$view'>"; 
	else bf_stop("Echec de la connexion. <BR><BR> Verifiez vos identifiants (Utilisateur et Mot de passe).");
	echo "</head>";
	echo "</HTML>";
}

function EffaceTables($Base, $Masque) {
	$reqtables = bf_mysql_query("SHOW TABLES FROM `$Base`");
	if (!(!$reqtables)) {
		while ($row = mysql_fetch_row($reqtables)) {
			if (!($row[0] == "Paramweb" || $row[0] == "Connexions")) {
				if (substr($row[0],0,strlen($Masque)) != "trans ".session_id()) {
					bf_mysql_query("DELETE FROM `".$row[0]."`");
				}
			}
		}
	}
}

function SupprimeTables($Base, $Masque) {
	$reqtables = bf_mysql_query("SHOW TABLES FROM `$Base`");
	if (!(!$reqtables)) {
		while ($row = mysql_fetch_row($reqtables)) {
			if (substr($row[0],0,strlen($Masque)) == "trans ".session_id()) {
				bf_mysql_query("DROP TABLE `".$row[0]."`");	
			}
		}
		PurgeTables();
	}
}

function Maj($MajType, $Source, $ResCible, $Exceptions = array(), $result = "") {
	if ($result == "") $result = bf_mysql_query("SELECT * FROM $Source");
	$i = 0;
	$strSource = array();
	$strCible = array();
	$strOnDuplicate = array();
	while ($i < mysql_num_fields($result)) { 
		$champ = mysql_fetch_field($result, $i);
		$flags = explode(' ', mysql_field_flags($result, $i));
		if (!(in_array("auto_increment" ,$flags))) {
			$strSource[$i] = $champ->name;
			if (array_key_exists($champ->name, $Exceptions)) {
				$result1 = bf_mysql_query($Exceptions[$champ->name]);
				if (!(!($result1))) {
					$row = mysql_fetch_row($result1);
					$strCible[$i] = $row[0];
				} else $strCible[$i] = null;
			} else {
				if (in_array("primary_key" ,$flags)) {
					$strCible[$i] = TrouveMax("SELECT MAX(".$champ->name.") FROM $Source") + 1 ;
				} else $strCible[$i] = $ResCible[$champ->name];
			} 
			if (is_null($strCible[$i])) $strCible[$i] = 'NULL'; else $strCible[$i] = '"'.$strCible[$i].'"';
			if (!(in_array("primary_key" ,$flags))) $strOnDuplicate[$i] = $champ->name." = ".$strCible[$i];
		}
		$i++;
	}
	$strSource = implode(",", $strSource);
	$strCible = implode(",", $strCible);
	$strOnDuplicate = implode(",", $strOnDuplicate);
	if ($MajType == 1) bf_mysql_query("INSERT INTO $Source ($strSource) VALUES ($strCible) ON DUPLICATE KEY UPDATE $strOnDuplicate");
	return array($strSource, $strCible);
}

Function MajOrdre($TablesNames = "") {
	if ($TablesNames != "") {
		if (!(is_array($TablesNames))) $TablesNames = array("$TablesNames");
		for ($t = 0; $t < count($TablesNames); $t++) {
			$cpte = 0;
			$mastr = "SELECT * FROM ".$TablesNames[$t][0]." ORDER BY ";
			if (count($TablesNames[$t]) == 1) $mastr = $mastr."Ordre"; else $mastr = $mastr.$TablesNames[$t][1].", Ordre"; 
			$res = bf_mysql_query($mastr);
			$Chp = mysql_fetch_field($res, 0);
			if (!(!$res)) {
				while ($row = mysql_fetch_array($res)) {
					$cpte = $cpte + 1;
					bf_mysql_query("UPDATE ".$TablesNames[$t][0]." SET Ordre = $cpte WHERE $Chp->name = ".$row[0],0,"`".$TablesNames[$t][0]."`");
				}
			}
		}
	}
}

Function RetRelayeurs($Relayeurs, $TypeInfo = 2, $SaisieOblige = False) {
	Global $PHP_SELF, $Adm, $Etab;
	$RetRelayeurs = "";
	if (!($Adm)) $mawhere = " AND (EtabNum = ".$Etab.RetAS($Etab).")"; else $mawhere = "";
	switch ($TypeInfo) {
		Case 0 : {
			$req = bf_mysql_query("SELECT Licenciés.LicInscrit, Licenciés.LicNumLicence, Licenciés.LicNom, Licenciés.LicPrénom, CatLibelCourt FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode WHERE (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode AND CatSpoCode = 1 AND LicNumLicence = ".$Relayeurs.$mawhere);
			if ($req) {
				$res = mysql_fetch_array($req);
				if ($res) $RetRelayeurs = "(".$res['CatLibelCourt'].") ".$res['LicNom']." ".$res['LicPrénom']; else $RetRelayeurs = "Erreur";
			} else { 
				if ($Relayeurs != "") $RetRelayeurs = "Erreur";
			}
			Break;
		}
		Case 1 :
		Case 2 : 
		Case 3 :
		Case 4 : {
			$arrayres = array();
			$arrayrels1 = array();
			$arrayrels  = array();
			
			If ($TypeInfo >= 3) {
				for ($i = 0; $i < 4; $i++) {
					if ((isset($_POST["EquRelayeurs$i"])) && ($_POST["EquRelayeurs$i"] != "") ) {
						if ($Relayeurs != "") $Relayeurs = $Relayeurs."-";
						$Relayeurs = $Relayeurs.$_POST["EquRelayeurs$i"];
					}
				}
			}
			if ($Relayeurs != "") {
				$arrayrels1 = explode("-",$Relayeurs);
				$arrayrels  = array_unique($arrayrels1);
			} 
			for ($i = 0; $i < count($arrayrels); $i++) {
				$arrayrel = explode(" ",trim($arrayrels[$i]));
				if (is_numeric($arrayrel[0])) {
					If ($TypeInfo == 1) array_push($arrayres, $arrayrel[0]);
					If (($TypeInfo == 2)||($TypeInfo == 3)||($TypeInfo == 4)) {
						$req = bf_mysql_query("SELECT Licenciés.LicInscrit, Licenciés.LicNumLicence, Licenciés.LicNom, Licenciés.LicPrénom, CatLibelCourt FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode WHERE (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = 1 AND LicNumLicence = ".$arrayrel[0].$mawhere);
						$res = mysql_fetch_array($req);
						if ($res) array_push($arrayres, $arrayrel[0]." (".$res['CatLibelCourt'].") ".$res['LicNom']." ".$res['LicPrénom']); 
						if (isset($_POST['EprLibelCourt']) && ($_POST['EprLibelCourt'] != "")) {
							$rescat = mysql_fetch_array(bf_mysql_query("SELECT EprCatCode, EprLibelCourt, SpoGestionPerf, SpoCode FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode INNER JOIN `Epreuves Compétitions` ON Compétitions.CompetCode = `Epreuves Compétitions`.EprCompetCompetCode INNER JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode INNER JOIN Catégories ON Epreuves.EprCatCode = Catégories.CatCode WHERE `Epreuves Compétitions`.EprCompetCode = ".$_POST["EprLibelCourt"]));
							if ($rescat['SpoGestionPerf'] == -5) {
								$reslic =  mysql_fetch_array(bf_mysql_query("SELECT CatCode FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode WHERE (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = ".$rescat['SpoCode']." AND LicNumLicence = ".$arrayrel[0].$mawhere));
								if ($rescat['EprCatCode'] != $reslic['CatCode']) $RetRelayeurs = "Erreur";
							}
						}
						if ($SaisieOblige) {if (count($arrayrels) < 4) $RetRelayeurs = "Erreur";}
					}
				}
			}
			if ($TypeInfo != 4) $RetRelayeurs = implode(" - ", $arrayres); else {
				if (count($arrayres) != count($arrayrels1)) $RetRelayeurs = "Erreur";
			}
			break;
		}
	}
	Return ($RetRelayeurs);
}

function TriTableau() {
   $args   = func_get_args();
   $arrays = $args[0];
   for ($i = (count($args)-1); $i > 0; $i--) {
       if (in_array($args[$i], array("ASC" , "DESC", "STR" , "DATE" , "NB"))) continue;
       $compstr = create_function('$a,$b','return strcasecmp($a["'.$args[$i].'"], $b["'.$args[$i].'"]);');
       $compnb = create_function('$a,$b','return $a["'.$args[$i].'"] <= $b["'.$args[$i].'"];');
       if ($args[$i+1] == "STR") usort($arrays, $compstr); else usort($arrays, $compnb);
       if ($args[$i+2] == "DESC") $arrays = array_reverse($arrays);
   }
   return $arrays;
}

function RetourneFic ($chemin = ".", $Masque = "", $Type = "", $TriFic = "") {
	$chemin  = ".";
	$handle  = @opendir($chemin);
	$now= time();
	$datenow = mktime(0,0,0, date('m', $now), date('d', $now), date('Y', $now)); 
	$arrayjours = array('dim' => 0, 'lun' => 1, 'Mar' => 2, 'Mer' => 3, 'Jeu' => 4, 'Ven' => 5, 'Sam' => 6,);
	$fileind = 0;
	while ($file = @readdir($handle)) {
		if( (!(is_dir("$chemin/$file"))) && (!(!(strstr($file, $Masque)))) ){		
			$tabfile[$fileind]["Nom"]       = $file;
			$tabfile[$fileind]["Taille"]    = sprintf ("%.1f K%s",(filesize("$chemin/$file")/1024),"o");
			$tabfile[$fileind]["Date"]      = array_search(Date("w", filemtime("$chemin/$file")),$arrayjours)." ".Date("d/m/Y", filemtime("$chemin/$file"));
			$tabfile[$fileind]["Heure"]     = Date("H:i:s", filemtime("$chemin/$file"));
			$tabfile[$fileind]["DateTri"]   = filemtime("$chemin/$file");
			$tabfile[$fileind]["TailleTri"] = filesize("$chemin/$file");
			$tabfile[$fileind]["Age"]       = ceil((($datenow - mktime(0,0,0, date('m', filemtime("$chemin/$file")), date('d', filemtime("$chemin/$file")), date('Y', filemtime("$chemin/$file")))) / 86400));
			if (strstr($file, $Type) == True) $tabfile[$fileind]["Type"] = $Type; else $tabfile[$fileind]["Type"] = "Man";

			if ($Masque == "Compétition") {
				$tabfile[$fileind]["Ugsel"] = "";
				$tabfile[$fileind]["Sport"] = "";
				$tabfile[$fileind]["Description"] = "";
				$tabfile[$fileind]["Obs"] = "";
				$TheFile=gzopen($file, 'rb');
				while (!gzeof($TheFile)){
					$ligne=trim(gzgets($TheFile,65535));
					if (strlen($ligne) > 0) {
						if(substr($ligne, 0, 4) == "-- #") {
							$tab = explode("#", $ligne);	
							if($tab[1] != "") $tabfile[$fileind]["Ugsel"] = $tab[2];
							if($tab[2] != "") $tabfile[$fileind]["Sport"] = $tab[3]; 
							if($tab[3] != "") $tabfile[$fileind]["Description"] = $tab[4]; 
							if($tab[4] != "") $tabfile[$fileind]["Obs"] = $tab[5];
							$tabfile[$fileind]["Résumé"] = $tabfile[$fileind]["Sport"]." - ".$tabfile[$fileind]["Ugsel"]." -> ".$tabfile[$fileind]["Date"]." à ".$tabfile[$fileind]["Heure"]." (".$tabfile[$fileind]["Taille"].") ".$tabfile[$fileind]["Description"]; 
						}
					}  
				}
				gzclose($TheFile);	
			}
			
			$fileind++;
		}
	}
	@closedir($handle);
	if ($fileind > 0) {
		if ($Masque == "Compétition") {
			switch($TriFic) {
				case "Date"   : $tabfile = TriTableau($tabfile, "DateTri","DATE","ASC", "Ugsel","STR","ASC", "Sport","STR","ASC"); Break;
				case "Taille" : $tabfile = TriTableau($tabfile, "TailleTri","NB","ASC", "Ugsel","STR","ASC", "Sport","STR","ASC") ; Break;
				case "Ugsel"  : $tabfile = TriTableau($tabfile, "Ugsel","STR","ASC", "Sport","STR","ASC", "DateTri","DATE","ASC"); Break;
				case "Sport"  : $tabfile = TriTableau($tabfile, "Sport","STR","ASC", "Ugsel","STR","ASC", "DateTri","DATE","ASC"); Break;
				default       : $tabfile = TriTableau($tabfile, "Sport","STR","ASC", "Ugsel","STR","ASC", "DateTri","DATE","ASC");
			}	
		} else {
			switch($TriFic) {
				case "Date"   : $tabfile = TriTableau($tabfile, "DateTri","DATE","ASC"); Break;
				case "Taille" : $tabfile = TriTableau($tabfile, "TailleTri","NB","ASC", "DateTri","DATE","ASC"); Break;
				default       : $tabfile = TriTableau($tabfile, "DateTri","DATE","ASC");
			}
		}
		return $tabfile;
	} else return 0;
}

function RetourneRep ($chemin = ".", $Masque = "") {
	$handle  = @opendir($chemin);
	$fileind = 0;
	while ($file = @readdir($handle)) {
		if( (is_dir("$chemin/$file")) && (!(!(strstr($file, $Masque)))) ){		
			$monIndex = "$chemin/$file/index.php";
			if (file_exists($monIndex)) {
				$tabfile[$fileind]["Nom"] = TrouveDansFic($monIndex,"UGSELNOM");
				$tabfile[$fileind]["Bdd"] = TrouveDansFic($monIndex,"BDD");
				$tabfile[$fileind]["Utilisateur"] = TrouveDansFic($monIndex,"UTILISATEUR");
				$tabfile[$fileind]["Mdp"] = TrouveDansFic($monIndex,"MDP");
				$tabfile[$fileind]["Lic Externe"] = "-";
				$tabfile[$fileind]["Lic Interne"] = "-";
				$reqlicExt = bf_mysql_query("SELECT COUNT(*) AS NB FROM Licenciés", 0, "", $tabfile[$fileind]["Bdd"], $tabfile[$fileind]["Utilisateur"],$tabfile[$fileind]["Mdp"]);
				if (!(!($reqlicExt))) {
					$reslicExt = mysql_fetch_array($reqlicExt);
					if (!(!($reslicExt))) $tabfile[$fileind]["Lic Externe"] = $reslicExt["NB"];
				} 
				$reqlicInt = bf_mysql_query("SELECT COUNT(*) AS NB FROM (Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode) INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE SecLibel = ".substr($tabfile[$fileind]["Utilisateur"],5,3) );
				if (!(!($reqlicInt))) {
					$reslicInt = mysql_fetch_array($reqlicInt);
					if (!(!($reslicInt))) $tabfile[$fileind]["Lic Interne"] = $reslicInt["NB"];
				}
				$fileind++;
			}
		}
	}
	@closedir($handle);
	if ($fileind > 0) {
		$tabfile = TriTableau($tabfile, "Nom","STR","ASC");
		return $tabfile;
	} else return 0;
}

function TailleRep ($chemin = ".", $ext = ".ugw") {
	$handle  = @opendir($chemin);
	$taille = 0;
	while ($file = @readdir($handle)) {
		if  ( (!(is_dir("$chemin/$file"))) &&  (strtolower(strrchr($file,".")) == strtolower($ext)) ){			
			$taille = $taille + filesize("$chemin/$file");
		}
	}
	@closedir($handle);
	return $taille;
}

function TrouveDansFic($Fic, $Atrouver) { 
	$HTheFile = fopen($Fic, 'r');
	while (!feof($HTheFile)){
		$Ligne = fgets($HTheFile,1024);
		$MaPos = strpos($Ligne, $Atrouver);
		if (!(is_bool($MaPos))) {
			$arraydata = explode('"', $Ligne);
			return $arraydata[1];
		}
	}
	fclose($HTheFile);
}

function ConvertTaille($Taille) {
    $symbols = array('o', 'Ko', 'Mo', 'Go', 'To', 'Po', 'Eo', 'Zo', 'Yo');
    $exp = $Taille ? floor(log($Taille) / log(1024)) : 0;
	return sprintf('%.1f '.$symbols[$exp], ($Taille/pow(1024, floor($exp))));
} 

function ConstruitStat($TypeStat = 0, $MonSport = 1, &$queryStr, &$NomsColonnes, &$ChampsAli, &$ChampsFor, &$ChampsAff, &$ChampsType, &$Choix, $Etab = "") {			
	$NomsColonnes  = array('','Code ','Numéro','Code','Nom','Ville','Total','Ins','Non','F','G');
	$ChampsAli     = array('','','center','center','','','right','right','right','right','right');
	$ChampsFor     = array('','','%06d','','','','','','','','');
	$ChampsAff     = array(false,false,true,true,true,true,true,true,true,true,true);
	$ChampsType    = array("","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte");
	$Choix = array("exporter", "liste");
	$MaStr = " SUM(1) AS Total, SUM(IF(LicInscrit,1,0)) AS Ins, SUM(IF(NOT LicInscrit,1,0)) AS Non, SUM(IF(LicSexCode=2,1,0)) AS F, SUM(IF(LicSexCode=1,1,0)) AS G,";
	$ResCat = bf_mysql_query("SELECT * FROM Sports INNER JOIN Catégories ON Sports.SpoCode = Catégories.CatSpoCode WHERE SpoCode = $MonSport AND CatPrim = TRUE ORDER BY Catégories.Ordre");
	while ($resCat = mysql_fetch_array($ResCat)) {
				$MaStr = $MaStr." SUM(IF(CatLibelCourt='".$resCat['CatLibelCourt']."',1,0)) AS ".$resCat['CatLibelCourt'].",";
				array_push ($NomsColonnes, $resCat['CatLibelCourt']);
				array_push ($ChampsAli,"right");
				array_push ($ChampsFor,"");
				array_push ($ChampsAff,true);
				array_push ($ChampsType,"Texte");
	}
	$MaStr = substr($MaStr,0,-1);
	$strRel = "";
	if ($TypeStat >= -1) {
		if ($TypeStat == -1) $reqRelay = bf_mysql_query("SELECT EquRelayeurs FROM Equipes WHERE NOT(EquRelayeurs = '')"); else $reqRelay = bf_mysql_query("SELECT EquRelayeurs FROM Equipes WHERE NOT(EquRelayeurs = '') AND EquCompetCode = ".$TypeStat);
		if ($reqRelay) {
			$strRel = "''";
			while ($resRel = mysql_fetch_array($reqRelay)) {
				$strRel .= ",".str_replace(" - ", ",", RetRelayeurs($resRel['EquRelayeurs'],1));
			}
			$strRel = " SELECT LicNumLicence AS ParLicCode, ".$TypeStat." AS ParCompetCode FROM Licenciés WHERE LicNumLicence IN (".$strRel.") ";
		}
	}
	for( $i = 0; $i <= 3; $i++ ) {
		$Fin = "WHERE ( (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = $MonSport And CatPrim = TRUE) ";
		if ($i == 0) $Fin = $Fin.$Etab;
		if ($TypeStat == -2) $MaStrTab[$i] = $MaStr." FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Secteurs ON Secteurs.SecCode = Etablissements.EtabSecCode ".$Fin;
		
		$maUnionRelayeurs = "";
		if (($TypeStat >= -1) && ($strRel != "")) {
			bf_mysql_query("CREATE TEMPORARY TABLE TempParRelayeurs$i $strRel");
			if ($TypeStat > 0) $maUnionRelayeurs = " UNION (SELECT ParLicCode, ParCompetCode FROM TempParRelayeurs$i GROUP BY ParLicCode, ParCompetCode HAVING ParCompetCode = $TypeStat) "; else $maUnionRelayeurs = " UNION (SELECT ParLicCode FROM TempParRelayeurs$i GROUP BY ParLicCode) ";
		}

		if ($TypeStat > 0) $select = "(SELECT ParLicCode, ParCompetCode FROM Participations GROUP BY ParLicCode, ParCompetCode HAVING ParCompetCode = $TypeStat) $maUnionRelayeurs"; else $select = "(SELECT ParLicCode FROM Participations GROUP BY ParLicCode) $maUnionRelayeurs";
		if ($TypeStat >= -1) {
			bf_mysql_query("CREATE TEMPORARY TABLE TempPar$i $select");
			$MaStrTab[$i] = $MaStr." FROM Catégories, Licenciés INNER JOIN Etablissements ON LicEtabCode = EtabCode INNER JOIN Secteurs ON SecCode = EtabSecCode INNER JOIN TempPar$i ON Licenciés.LicNumLicence = TempPar$i.ParLicCode ".$Fin;
		}
	}
	$queryStr      = "SELECT '' AS ' ', CONCAT(SecRégionCode, ' ',SecLibel,' ', EtabNomCourt) AS `Code `, EtabNum AS Numéro, EtabNomCourt AS Code, EtabNom AS Nom, EtabVille AS Ville, ".$MaStrTab[0]." GROUP BY EtabNum ";
	If ($Etab == "") $queryStr = $queryStr."
					  UNION SELECT ' ' AS F, CONCAT(SecRégionCode, ' ', SecLibel, '  ') AS `Code `,  '' AS Numéro, '' AS Code, CONCAT(SecLibellé, ' (', COUNT(DISTINCT(EtabCode)),' Etab)') AS Nom, '' AS Ville, ".$MaStrTab[1]." GROUP BY SecCode 
					  UNION SELECT '  ' AS F, CONCAT(SecRégionCode, '   ') AS `Code `,  '' AS Numéro, '' AS Code, CONCAT(SecRégionCode, ' (', COUNT(DISTINCT(EtabCode)),' Etab)') AS Nom, '' AS Ville, ".$MaStrTab[2]." GROUP BY SecRégionCode
					  UNION SELECT '   ' AS F, '   ' AS `Code `,  '' AS Numéro, '' AS Code, CONCAT('Ugsel', ' (', COUNT(DISTINCT(EtabCode)),' Etab)')  AS Nom, '' AS Ville, ".$MaStrTab[3]." GROUP BY `Code `";  
	$queryStr = $queryStr." ORDER BY `Code `";
	Return($queryStr);
}

function RenumeroteEquipes($Compet) {
	bf_mysql_query("UPDATE Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode INNER JOIN Equipes ON Compétitions.CompetCode = Equipes.EquCompetCode INNER JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode INNER JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode SET EquCatCode = EprCatCode WHERE (ISNULL(EquCatCode) OR EquCatCode = 0) AND SpoGestionPerf = -5 AND EquCompetCode = $Compet", 0, "`Equipes`");
	bf_mysql_query("UPDATE Equipes SET EquNum = EquNum * -1, EquComplément = Null Where EquCompetCode = $Compet", 0, "`Equipes`");
	$Res = bf_mysql_query("SELECT EquCode, EtabNum, EquTour, EquEtabCode, CatCode FROM Etablissements INNER JOIN Secteurs On Etablissements.EtabSecCode = Secteurs.SecCode, Catégories INNER JOIN Equipes ON Catégories.CatCode = Equipes.EquCatCode LEFT JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode LEFT JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode where Etablissements.EtabCode = Equipes.EquEtabCode AND EquCompetCode = $Compet ORDER BY Catégories.Ordre, Epreuves.Ordre, Secteurs.Ordre, Etablissements.EtabNum, EquNum Desc, EquTour");
	while ($res = mysql_fetch_array($Res)) {
		if ( ($res["EquTour"] == 1) || ($res["EquTour"] == Null) ) { 
			$Maxnum = mysql_fetch_array(bf_mysql_query("SELECT MAX(EquNum) AS Max FROM Equipes INNER JOIN Etablissements ON Equipes.EquEtabCode = Etablissements.EtabCode WHERE EquNum > 0 AND EquCompetCode = $Compet AND EquEtabCode = ".$res["EquEtabCode"])); 
			if ($Maxnum["Max"] != Null) $NumEqu = $Maxnum["Max"] + 1; else $NumEqu = ($res["EtabNum"] * 10) + 1;
			if (Floor($NumEqu / 10) - $res["EtabNum"] == 1) $NumEqu = (($NumEqu / 10) - 1) * 100 + 10;
			$Countcomp = mysql_fetch_array(bf_mysql_query("SELECT COUNT(EquCode) AS Nbre FROM Equipes INNER JOIN Etablissements ON Equipes.EquEtabCode = Etablissements.EtabCode WHERE EquTour = 1 AND EquNum > 0 AND EquCatCode = ".$res["CatCode"]." AND EquNum < $NumEqu AND EquCompetCode = $Compet AND EquEtabCode = ".$res["EquEtabCode"])); 
			$NumComp = $Countcomp["Nbre"] + 1;
			bf_mysql_query("UPDATE Equipes SET EquNum = $NumEqu, EquComplément = $NumComp Where EquCompetCode = $Compet AND EquCode = ".$res["EquCode"]);
			$SauveNumEqu = $NumEqu;
			$SauveCompEqu = $NumComp;
		} Else {
			bf_mysql_query("UPDATE Equipes SET EquNum = $SauveNumEqu, EquComplément = $SauveCompEqu Where EquCompetCode = $Compet AND EquCode = ".$res["EquCode"]);
		}
	}
}

function OptimizeTables() {
	Global $BDD;
	$table = mysql_list_tables($BDD);
	$sql = "";
	$req = mysql_query('SHOW TABLE STATUS');
	if (!(!($req))) {
		while($data = mysql_fetch_assoc($req)) {
			if($data['Data_free'] > 0) 	$sql .= '`'.$data['Name'].'`, ';
		}
		if ($sql != "")	bf_mysql_query("OPTIMIZE TABLE ".substr($sql, 0, (strlen($sql)-2)));
	}
}

function FormatPerf($Nbre) {
	$Nbre = floor(round($Nbre*100))/100;
	$Nb = explode(".",$Nbre);
	$Perf = "";
	for ($i = 0 ; $i < strlen($Nb[0]) ; $i++) {
		$Perf .= $Nb[0][$i];
		if ( ($i%2) != (strlen($Nb[0])%2) ) $Perf .= " ";
	}
	$Perf = rtrim($Perf);
	if($Nb[1] > 0) $Perf.= ".".$Nb[1];
	Return $Perf;
}

function RetAS($Etab) {
	$Ret = "";
	$res = bf_mysql_query("SELECT EtabAS FROM Etablissements WHERE EtabNum = ".$Etab);
	if (!(!$res)) {
		$row = mysql_fetch_array($res);
		if ($row[0] != "") $Ret = " OR (EtabAS = '".$row[0]."')";
	}
	Return $Ret;
}

function PurgeTables() {
	Global $PURGE, $Adm;
	if (($Adm) && ($PURGE != 0)) {
		bf_mysql_query("DELETE Participations FROM Participations INNER JOIN Licenciés ON Participations.ParLicCode = Licenciés.LicNumLicence INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE SecLibel <> $PURGE");
		bf_mysql_query("DELETE Equipes FROM Equipes INNER JOIN Etablissements ON Equipes.EquEtabCode = Etablissements.EtabCode INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE SecLibel <> $PURGE");
		bf_mysql_query("DELETE Licenciés FROM Licenciés INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE SecLibel <> $PURGE");
		bf_mysql_query("DELETE Etablissements FROM Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE SecLibel <> $PURGE");
		bf_mysql_query("DELETE Secteurs FROM Secteurs WHERE SecLibel <> $PURGE");
	}
}

function GereData($tablename, $queryStr, $MaKey="", $NomsColonnes="", $ChampsTri="", $ChampsAli="", $ChampsFor="", $ChampsAff="", $action="GereData", $orderby="", $Choix="", $ChampsEdit="", $ChampsInsert="", $ChampsType="", $ChampsTypeExt="", $ChampsFiltre="", $where="", $ChampsNomFil="", $ChampsRacFiltre = "", $ChampsRacParam = "", $sousqueryStr, $messagedel = "", $MajChpOrdre = "", $stat = 0, $strCatEpr = "", $maxInsc = 99999 ) {
	global $Adm, $filter, $filtre1, $BFiltrer, $BAjouter, $BModifier, $tablename, $PHP_SELF, $errMsg, $page, $rowperpage, $coul, $code, $codewhere,  $order, $selection, $menu, $sousmenu, $Etab, $Compet, $Lic, $Epr, $ColNom, $Tri, $suppr, $modif, $aj, $fi, $supprtout, $ListeSport, $BValidernumlicence, $ParLicCode, $affcompet, $BSupprimerTout, $ADMINREGLOGIN; 
	global $message;
	global $racnom, $racval;
	global $TAILLE, $tailleinf, $taille;
	global $exportegrille, $changeordre;	
	global $dataext;	
	global $imp, $exp;
	global $valideimportcompet, $valideimportsport;
	global $ListeImportCompet, $ListeImportSport;
	global $Sport, $BDD, $optionexporttype, $exporttype;
	global $optionexport;
	global $Consult;
	global $ListeImportCompetInterne, $EtabExport, $EtabImport, $horscat;
	Global $SurClass, $BAjouterSurclassement, $BModifierSurclassement ;
	Global $licence, $seltous;
	Global $selectionner;
	Global $optioninslic, $valideinslic, $reflicins;
	
	$MaReqErr = "";
	$TabErr   = array();
	if (isset($BAjouterSurclassement)) $BAjouter = "Inscrire";
	if (isset($BModifierSurclassement)) $BModifier = "Valider";
	
	if ((isset($BAjouter)) || (isset($BModifier)) || (isset($BFiltrer)) || (isset($racnom)) ) {
		$queryStr = stripslashes($queryStr);
		$pResult = bf_mysql_query($queryStr." LIMIT 0");
		$col = mysql_num_fields($pResult);
	}
	
	if (isset($BAjouter)) {
		$MaReq1="INSERT INTO `$tablename` (";
		$MaReq2="VALUES(";
		for( $j = 0; $j < $col; $j++ ) {
			$field = mysql_fetch_field( $pResult, $j );
						
			if ((isset($_POST[$field->name]) && ($ChampsInsert[$j][1])) || ($field->name == "EquRelayeurs")) {
				if ($field->name == "EquRelayeurs") {
					$Data = addslashes(RetRelayeurs("",3)); 
					if (RetRelayeurs("",4,$ChampsInsert[$j][6]) == "Erreur") $TabErr[$j] = $NomsColonnes[$j];
				} else $Data = addslashes($_POST[$field->name]);
				if (is_array($ChampsInsert[$j][4])) {
					if ($ChampsInsert[$j][4][0] == "Max") {
						$Data = TrouveMax("SELECT ".$ChampsInsert[$j][4][0]."(".$ChampsInsert[$j][4][1].") AS Result FROM $tablename") + 1;
						$_POST[$field->name] = $Data;
					}
				}
				if ($ChampsType[$j] == "Perf") {
					$Data = str_replace(" ","",$Data);
					$Data = str_replace(",",".",$Data);
				}			
				if ($field->type == 'date') { 
					$MaErrDate = true;
					$maDate = explode("/", $Data);
					if (count($maDate) == 3) { 
						$jour  = sprintf('%02s',$maDate [0]);
						$mois  = sprintf('%02s',$maDate [1]);
						if ($maDate[2] < 50) $monPrefix = "%2002s"; Else $monPrefix = "%1902s"; 
						$annee = sprintf($monPrefix,$maDate[2]);
						if ((is_numeric($jour)) && (is_numeric($mois)) && (is_numeric($annee))) {
							if (checkdate($mois,$jour,$annee)) {  
								if ($field->name == "LicNaissance") {
									$datesaisie = $annee.$mois.$jour;
									$dateinf  = date("19700101"); $datesup  = date("20301231");
									if (($datesaisie <= $datesup) && ($datesaisie >= $dateinf)) {
										$Data = $annee."-".$mois."-".$jour;
										$MaErrDate = false;
									}
								} else {
									$Data = $annee."-".$mois."-".$jour;
									$MaErrDate = false;
								}
							}
						}
					}
					if ((!($ChampsInsert[$j][6])) && ($MData == "")) {
						$MData = "Null"; 
						$MaErrDate = false;
					}
				}		
				if (($ChampsInsert[$j][0]=="ListeD") && ($ChampsInsert[$j][3][7] != "")) {
					$req = bf_mysql_query($ChampsInsert[$j][3][7]."'$Data'");
					if ($req) {
						$res = mysql_fetch_array($req); 
						$Data =$res[0];
					}
				}
				if (($ChampsInsert[$j][0]=="ListeS") && (!(array_key_exists($Data,$ChampsInsert[$j][3][2])))) {
					$tabkeys = array_keys($ChampsInsert[$j][3][2]);
					$Data = $tabkeys[0];
				}
				if ($ChampsInsert!= "") { 
					if ($ChampsInsert[$j][2] != "") {
						$MaReq1 = $MaReq1."`".$ChampsInsert[$j][2]."`,";
						$result1 = mysql_query("SELECT ". $ChampsInsert[$j][2]. " FROM $tablename LIMIT 1");
						$field1 = mysql_fetch_field( $result1, 0);
					} else {
						$MaReq1 = $MaReq1."`".$field->name."`,";
						$field1 = $field;
					}
				}
				if ($field1->numeric) {
					if ((empty($Data)) && ($Data != "0")) $MaReq2 = $MaReq2."'',"; else $MaReq2 = $MaReq2."$Data,"; 
				} else {
					if ($Data == "Null") $MaReq2 = $MaReq2."$Data,"; else $MaReq2 = $MaReq2."'$Data',";
				}
				if ((($field1->not_null) || ($ChampsInsert[$j][6]) || ($field1->primary_key)) && ((empty($Data)) && ($Data != "0")) ){
					$TabErr[$j] = $NomsColonnes[$j];
				}
				if (!(empty($Data))) {
					if (($field1->numeric) && !is_numeric($Data)) {
						$TabErr[$j] = $NomsColonnes[$j];
					}
					if (($field1->type == "date") && ($MaErrDate)) {
						$TabErr[$j] = $NomsColonnes[$j];
					}
				}
			}
		}
		
		if (Count($TabErr) > 0) $MaReqErr = "Impossible de valider !  Erreur sur : ". implode(", ", $TabErr).".";
				
		$MaReq = substr( $MaReq1, 0, strlen($MaReq1)-1 ).") ". substr( $MaReq2, 0, strlen($MaReq2)-1 ).")";
		
		if ($MaReqErr == "") {
			if (($menu == "competitions") && ($sousmenu == "individuels")) {
				if ((!(isset($BValidernumlicence))) || (empty($BValidernumlicence))) {
					$MaReqErr = "Cliquez sur le bouton Ok pour rechercher le licencié.";
				} else {
					$reqlicstr = "SELECT LicInscrit, LicNumLicence, LicNom, LicPrénom, LicNaissance, LicSexCode, LicAss, LicDateDem FROM Licenciés INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode WHERE LicNumLicence = ".$_POST["ParLicCode"];
					if (!($Adm)) $reqlicstr .= " And (EtabNum = ".$Etab.RetAS($Etab).")";
					$reqlic = bf_mysql_query($reqlicstr);
					if ($reqlic) {
						$reslic = mysql_fetch_array($reqlic);
						if ($reslic) {
							$reflic = sprintf('%010s',$reslic['LicNumLicence'])." ".$reslic['LicNom']." ".$reslic['LicPrénom'];
							if ($reslic["LicInscrit"] != 1) {
								$MaReqErr  = "Aucune licence n'a été établie pour ".$reflic.".";
								$optionIns = TrouveParamweb("InscriptionLic");
								if ($optionIns == 0) {
									$MaReqErr .= " Contactez votre Ugsel pour établir la licence.";
								} else {
									if ( ($optionIns == 1) && (!(is_null($reslic['LicDateDem']))) && (CompteEnr("Compétitions", $reslic['LicNumLicence']." IN(0".TrouveSport($Compet, "CompetDemLic").")") > 0) ) { 
										$MaReqErr .= "<BR><BR> Une demande de licence a déjà été effectuée pour ".$reslic['LicPrénom']." ".$reslic['LicNom'].". Vous pourrez l'inscrire dans la compétition une fois la demande validée par l'Ugsel.";
									} else {									
										$MaReqErr .= "<BR><BR> &nbsp; Voulez-vous demander une licence pour ".$reslic['LicPrénom']." ".$reslic['LicNom']." ?";
										$MaReqErr .= "
										<BR><BR>
										<FORM method='post'>
										&nbsp;&nbsp; <input type='radio' name='optioninslic' value='0' checked='checked'> Non";
										if ($reslic['LicAss'] == 1) {
											$MaReqErr .= "&nbsp;&nbsp; <input type='radio' name='optioninslic' value='2'> Oui";
										} else {
											$trouveAss = TrouveParamweb("AssUgsel", "");
											$MaReqErr .= "&nbsp;&nbsp; <input type='radio' name='optioninslic' value='2'> Oui AVEC Assurance $trouveAss Ugsel
											&nbsp;&nbsp; <input type='radio' name='optioninslic' value='1'> Oui SANS Assurance $trouveAss Ugsel";
										}
										$MaReqErr .= "&nbsp;&nbsp; <input name='valideinslic' type='submit' id='valideinslic' value='Valider' class='bouton'>
										&nbsp;&nbsp; <input name='ParLicCode' type='hidden' id='ParLicCode' value='".$reslic['LicNumLicence']."'>
										&nbsp;&nbsp; <input name='reflicins' type='hidden' id='reflicins' value='".$reflic."'>									
										</FORM>";
									}
								}
							} else {	
								$resins = mysql_fetch_array(bf_mysql_query("SELECT ParCode FROM Participations WHERE ParEprCode = ".$_POST["EprLibelCourt"]." AND ParLicCode = ".$_POST["ParLicCode"]));
								$resepr = mysql_fetch_array(bf_mysql_query("SELECT EprLibelCourt, CatDateDéb, CatDateFin, CatSexCode FROM Catégories INNER JOIN Epreuves ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCompetCode = ".$_POST["EprLibelCourt"]));
								if ( ($Sport == 13) || ($Sport == 15) || ($Sport == 16) || ($Sport == 17) || ($Sport == 18) || ($Sport == 19)) {
									$resuni = mysql_fetch_array(bf_mysql_query("SELECT ParCode FROM Participations WHERE ParCompetCode = ".$Compet." AND ParLicCode = ".$_POST["ParLicCode"]));
									if ($resuni) $MaReqErr = "Le licencié ".$reflic." est déjà inscrit dans la compétition (vous pouvez modifier son inscription)";
								} 
									if ($resins) {
										$MaReqErr = "Le licencié ".$reflic." est déjà inscrit dans l'épreuve ".$resepr['EprLibelCourt'];
									} else {
										if ($resepr["CatSexCode"] <3) {
											if (stristr($resepr['EprLibelCourt'], "OPEN") === false) {
												if (!(($reslic["LicSexCode"] == $resepr["CatSexCode"]) && ($reslic["LicNaissance"] >= $resepr["CatDateDéb"]) && ($reslic["LicNaissance"] <= $resepr["CatDateFin"]))) {
													if (isset($_POST["EprLibelCourt"])) $MaReqErr = "La catégorie du licencié ".$reflic." est différente de celle de l'épreuve ".$resepr['EprLibelCourt'];
														else $MaReqErr = "Saisissez un N° de licence puis cliquez sur le bouton Ok";
													
													if ( ($Sport == 7) || ($Sport == 11) || ($Sport == 12) || ($Sport == 20) ) { 
														if ((($reslic["LicSexCode"] == $resepr["CatSexCode"]) && ($reslic["LicNaissance"] >= $resepr["CatDateDéb"]) && ($reslic["LicNaissance"] <= date('Y/m/d', strtotime('+1 year',strtotime($resepr["CatDateFin"])))  ))) {
															if (!(isset($BAjouterSurclassement))) {
																$MaReqErr .= "<BR><BR> &nbsp; < Vous pouvez cliquer sur le bouton 'SurClasser' pour inscrire ce participant (vérifiez au pralable si le règlement vous l'autorise) >"; 
																$SurClass = 1;
															} else {
																$MaReqErr = "";
																$SurClass = 0;
															}
														}
													}
												}
											} else {
												if (!(($reslic["LicSexCode"] == $resepr["CatSexCode"]))) {
													if ($MaReqErr == "") $MaReqErr = "La catégorie du licencié ".$reflic." est différente de celle de l'épreuve ".$resepr['EprLibelCourt'];
												}
											}
										}
									} 
								
								$nbTot = 0; $nbInsc = 0;
								while (isset($_POST['Epr'.$nbTot])) {
									if (isset($_POST['EprLibelCourt'.$_POST['Epr'.$nbTot]])) {$nbInsc++;}
									$nbTot++;
								}
								if ($nbInsc > $maxInsc) {
									$MaReqErr = "Vous ne pouvez pas inscrire un participant dans plus de $maxInsc épreuve";
									if ($maxInsc > 1) $MaReqErr .= "s";
									$MaReqErr .= ".";
								} else {
								
								$l=0; $messageAj = ""; $messageSu = ""; $messageMo = "";
								while (isset($_POST['Epr'.$l])) {	
									$MaReq = ""; $strPost = ""; $MaReqErrLigne = false;
									if ($l == 0) $MaReqErr = "";
									if ($_POST['Lic'.$l] != $_POST['ParLicCode']) {$MaReqErr = "Cliquez sur le bouton 'Ok' après avoir modifié le numéro de licence."; break;}
									$postParQuadra = "Null"; $postEquNum = "Null"; $postParPerfQualif = "0";
									if ($_POST['ParQuadra'.$_POST['Epr'.$l]] > 0) $postParQuadra = "True";
									if ($_POST['EquNum'.$_POST['Epr'.$l]] > 0) $postEquNum = $_POST['EquNum'.$_POST['Epr'.$l]];
									if ($_POST['ParPerfQualif'.$_POST['Epr'.$l]] > 0){
										$_POST['ParPerfQualif'.$_POST['Epr'.$l]] = str_replace(" ","",$_POST['ParPerfQualif'.$_POST['Epr'.$l]]);
										$_POST['ParPerfQualif'.$_POST['Epr'.$l]] = str_replace(",",".",$_POST['ParPerfQualif'.$_POST['Epr'.$l]]);
										$postParPerfQualif = $_POST['ParPerfQualif'.$_POST['Epr'.$l]];
									}
									for ( $k = 1; $k < 6; $k++ ) {
										$postParObs{$k}= "Null";
										if (isset($_POST['ParObs'.$k.$_POST['Epr'.$l]])) {
											if ($_POST['ParObs'.$k.$_POST['Epr'.$l]] != "") $postParObs{$k} = "'".$_POST['ParObs'.$k.$_POST['Epr'.$l]]."'";
											if ( (isset($_POST['EprLibelCourt'.$_POST['Epr'.$l]])) && ( $postParObs{$k} == "Null" ) && ( $ChampsInsert[(13+$k)][6] ) ) {
												$TabErr[(13+$k)] = $NomsColonnes[(13+$k)] ;
												$MaReqErr = "Impossible de valider !  Erreur sur : ". implode(", ", $TabErr).".";
												$MaReqErrLigne = true;
											}
										} else {
											if ( (isset($_POST['EprLibelCourt'.$_POST['Epr'.$l]])) && ($ChampsInsert[(13+$k)][6]) ) {
												$TabErr[(13+$k)] = $NomsColonnes[(13+$k)] ;
												$MaReqErr = "Impossible de valider !  Erreur sur : ". implode(", ", $TabErr).".";
												$MaReqErrLigne = true;
											}
										}
									}
									if (isset($_POST['EprLibelCourt'.$_POST['Epr'.$l]])) {
										$strPost = "ParQuadra = ".$postParQuadra.", ParEquCode = ".$postEquNum.", ParPerfQualif = ".$postParPerfQualif.", ParObs1 = ".$postParObs{1}.", ParObs2 = ".$postParObs{2}.", ParObs3 = ".$postParObs{3}.", ParObs4 = ".$postParObs{4}.", ParObs5 = ".$postParObs{5};
										if ($_POST['Par'.$l] == "") { 
											$strPost = "INSERT INTO Participations SET ParCompetCode = ".$Compet.", ParEprCode = ".$_POST['Epr'.$l].", ParLicCode = ".$_POST['Lic'.$l].", ".$strPost;
											if ($messageAj != "") $messageAj = $messageAj.", "; else $messageAj = " <BR> &nbsp;&nbsp; Ajout en : "; $messageAj = $messageAj.$_POST['Lib'.$l];
										} else {
											$strPost = "UPDATE Participations SET ".$strPost." WHERE ParCode = ".$_POST['Par'.$l]; 
											if ($messageMo != "") $messageMo = $messageMo.", "; else $messageMo = " <BR> &nbsp;&nbsp; Mise à jour en : "; $messageMo = $messageMo.$_POST['Lib'.$l];
										}
									} else {
										if ($_POST['Par'.$l] != "")	{
											$strPost = "DELETE FROM Participations WHERE ParCode = ".$_POST['Par'.$l];
											if ($messageSu != "") $messageSu = $messageSu.", "; else $messageSu = " <BR> &nbsp;&nbsp; Suppression en : "; $messageSu = $messageSu.$_POST['Lib'.$l];
										}
									}
									if (!($MaReqErrLigne)) bf_mysql_query($strPost);
									$l++;
								}
								
								}
							}
						} else {
							$MaReqErr = "Le licencié ".$_POST["ParLicCode"]." est introuvable.";
						}
					} else {
						$MaReqErr = "Le licencié ".$_POST["ParLicCode"]." est introuvable.";
					}
				}
			}
			
			if (($menu == "competitions") && ($sousmenu == "equipes") && isset($_POST["EprLibelCourt"])) {
				$reqetabcat = bf_mysql_query("SELECT EprCatCode, EprLibelCourt, SpoGestionPerf FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode INNER JOIN `Epreuves Compétitions` ON Compétitions.CompetCode = `Epreuves Compétitions`.EprCompetCompetCode INNER JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode INNER JOIN Catégories ON Epreuves.EprCatCode = Catégories.CatCode WHERE `Epreuves Compétitions`.EprCompetCode = ".$_POST["EprLibelCourt"]);
				if (!(!($reqetabcat))) {
					$resetabcat = mysql_fetch_array($reqetabcat);
					if (!(!($resetabcat))) {
						if ($resetabcat['SpoGestionPerf'] == -1) {
							if ($resetabcat['EprCatCode'] != $_POST["CatLibelCourt"]) {
								$rescat = mysql_fetch_array(bf_mysql_query("SELECT CatLibelCourt FROM Catégories WHERE CatCode = ".$_POST["CatLibelCourt"]));
								$MaReqErr = "La catégorie de l'équipe ".$rescat['CatLibelCourt'] ." est différente de celle de l'épreuve ".$resetabcat['EprLibelCourt'];
							}
						}
					}
				}
			}
			
			if ($MaReqErr == "") { 
				bf_mysql_query($MaReq, 0, "`$tablename`");
				if (!($MajChpOrdre == "")) MajOrdre($MajChpOrdre);
				if ($tablename != "Participations") $aj= "";
			}
		
		} 
	
		if ($MaReqErr == ""){
		
			if (($menu == "parametres") && ($sousmenu == "epreuves")) {
				$Res = bf_mysql_query("SELECT CompetCode FROM Compétitions WHERE CompetSpoCode = $ListeSport");
				while ($res = mysql_fetch_array($Res)) {
					bf_mysql_query("INSERT INTO `Epreuves Compétitions` (`EprCompetEprCode`, `EprCompetCompetCode`) SELECT `EprCode`, ".$res['CompetCode']." AS Compet FROM `Epreuves` WHERE `EprCode` = ".$_POST['EprCode'],0 , "`Epreuves Compétitions`");
				}
			}
		
			if (($menu == "competitions") && ($sousmenu == "references")) {
				$res = mysql_fetch_array(bf_mysql_query("SELECT SpoCode FROM Sports WHERE SpoLibelCourt = '".$_POST['SpoLibelCourt']."'"));
				$resepr = bf_mysql_query("SELECT `EprCode` FROM `Epreuves` WHERE `EprSpoCode` = ".$res["SpoCode"]);
				while ($res = mysql_fetch_array($resepr)) {
					bf_mysql_query("INSERT INTO `Epreuves Compétitions` (`EprCompetEprCode`, `EprCompetCompetCode`) VALUES(".$res["EprCode"].",".$_POST["CompetCode"].")",0 , "`Epreuves Compétitions`"); 
				}
			}
	
			if (($menu == "competitions") && ($sousmenu == "equipes")) { 
				RenumeroteEquipes($Compet);
			}
	
			if (($menu == "competitions") && ($sousmenu == "individuels")) {
				$message = "Inscription de ".$reflic;
				if (!(isset($_POST['Epr1']))) {
					if ($ChampsAff[8]) $message = $message." en ".$resepr['EprLibelCourt']." effectuée."; else $message = $message." effectuée.";
				} else {
					if (($messageAj != "") || ($messageMo != "") || ($messageSu != "")) $message = $message." : ".$messageAj.$messageMo.$messageSu; else $message = "";
				}
				$BValidernumlicence = '';
				$_POST['ParLicCode'] = substr(sprintf('%010s',$_POST['ParLicCode']),0,6);
				unset($selectionner);
			}
			
			if ($menu == "etablissements") bf_mysql_query("UPDATE Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode SET EtabMemo3 = IF(RAND() > 0.33, IF(RAND() > 0.66, CONCAT(SecLibel, LOWER(EtabNomCourt), FLOOR(RAND()*100)), CONCAT(LOWER(EtabNomCourt), SecLibel, FLOOR(RAND()*100))), CONCAT(FLOOR(RAND()*100), LOWER(EtabNomCourt), SecLibel)) WHERE EtabMemo3 = '' OR EtabMemo3 IS NULL");
			
			for( $j = 0; $j < $col; $j++ ) {
				$field = mysql_fetch_field( $pResult, $j );
				if (isset($_POST[$field->name]) && ($ChampsInsert[$j][7] == false)) unset($_POST[$field->name]);
			}
		}
		bf_mysql_query("UNLOCK TABLES");
	}
	
	if (isset($BModifier)) { 
		$MaReq1="UPDATE `$tablename` SET ";
		for( $j = 0; $j < $col; $j++ ) {
			$field = mysql_fetch_field( $pResult, $j );
			if ((isset($_POST[$field->name]) && ($ChampsEdit[$j][1])) || ($field->name == "EquRelayeurs")) {
				if ($field->name == "EquRelayeurs") {
					$MData = addslashes(RetRelayeurs("",3)); 
					if (RetRelayeurs("",4,$ChampsEdit[$j][4]) == "Erreur") $TabErr[$j] = $NomsColonnes[$j];
				} else $MData = addslashes($_POST[$field->name]);
				if ($ChampsType[$j] == "Perf") {
					$MData = str_replace(" ","",$MData);
					$MData = str_replace(",",".",$MData);
				}			
				if ($field->type == 'date') { 
					$MaErrDate = true;
					$maDate = explode("/", $MData);
					if (count($maDate) == 3) { 
						$jour  = sprintf('%02s',$maDate [0]);
						$mois  = sprintf('%02s',$maDate [1]);
						if ($maDate[2] < 50) $monPrefix = "%2002s"; Else $monPrefix = "%1902s"; 
						$annee = sprintf($monPrefix,$maDate[2]);
						if ((is_numeric($jour)) && (is_numeric($mois)) && (is_numeric($annee))) {
							if (checkdate($mois,$jour,$annee)) {  
								if ($field->name == "LicNaissance") {
									$datesaisie = $annee.$mois.$jour;
									$dateinf = date("19700101"); $datesup  = date("20301231");
									if (($datesaisie <= $datesup) && ($datesaisie >= $dateinf)) {
										$MData = $annee."-".$mois."-".$jour;
										$MaErrDate = false;
									}
								} else { 
									$MData = $annee."-".$mois."-".$jour;
									$MaErrDate = false;
								}
							}
						}
					} 
					if ((!($ChampsEdit[$j][4])) && ($MData == "")) {
						$MData = "Null"; 
						$MaErrDate = false;
					}
				}	
				if ($ChampsEdit[$j][0] == "ListeD") {
					if ($ChampsEdit[$j][3][7] != "") {
						$req = bf_mysql_query($ChampsEdit[$j][3][7]."'$MData'");
						$res = mysql_fetch_array($req); 
						$MData =$res[0];
					}
				}
				if (($ChampsEdit[$j][0]=="ListeS") && (!(array_key_exists($MData,$ChampsEdit[$j][3][2])))) {
					$tabkeys = array_keys($ChampsEdit[$j][3][2]);
					$MData = $tabkeys[0];
				}
				if ($ChampsEdit != ""){ 
					if (($ChampsEdit[$j][2] != "")) {
						$MaReq1 = $MaReq1."`".$ChampsEdit[$j][2]."`="; 
						$result1 = mysql_query("SELECT ". $ChampsEdit[$j][2]. " FROM $tablename LIMIT 1");
						$field1 = mysql_fetch_field( $result1, 0);
					} else {
						$MaReq1 = $MaReq1."`".$field->name."`=";
						$field1 = $field;
					}
				}
				if ($field1->numeric) {
					if ((empty($MData)) && ($MData != "0")) $MaReq1 = $MaReq1."'',"; else $MaReq1 = $MaReq1."$MData,"; 
				} else {
					if ($MData == "Null") $MaReq1 = $MaReq1."$MData,"; else $MaReq1 = $MaReq1."'$MData',";
				}
				if ((($field1->not_null) || ($ChampsEdit[$j][4]) || ($field1->primary_key)) && ((empty($MData)) && ($MData != "0")) ){
					$TabErr[$j] = $NomsColonnes[$j];
				}
				if (!(empty($MData))) {
					if (($field1->numeric) && !is_numeric($MData)) {
						$TabErr[$j] = $NomsColonnes[$j];
					}
					if (($field1->type == "date") && ($MaErrDate)) {
						$TabErr[$j] = $NomsColonnes[$j];
					}
				}
			}
		}

		if (Count($TabErr) > 0) $MaReqErr = "Impossible de valider !  Erreur sur : ". implode(", ", $TabErr).".";
		
		if ($MaReqErr == "") {
			if (($menu == "competitions") && ($sousmenu == "individuels") && isset($_POST["EprLibelCourt"]) ) {
				$reslic = mysql_fetch_array(bf_mysql_query("SELECT LicNumLicence, LicNom, LicPrénom, LicNaissance, LicSexCode FROM Licenciés INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode WHERE ParCode = ".$modif));
				if ($reslic) {
					$reflic = sprintf('%010s',$reslic['LicNumLicence'])." ".$reslic['LicNom']." ".$reslic['LicPrénom'];
					$resins = mysql_fetch_array(bf_mysql_query("SELECT ParCode FROM Participations WHERE ParCode <> ".$modif." AND ParEprCode = ".$_POST["EprLibelCourt"]." AND ParLicCode = ".$reslic["LicNumLicence"]));
					$resepr = mysql_fetch_array(bf_mysql_query("SELECT EprLibelCourt, CatDateDéb, CatDateFin, CatSexCode, CatLibelCourt FROM  Catégories INNER JOIN Epreuves ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCompetCode = ".$_POST["EprLibelCourt"]));	
					if ($resins) {
						$MaReqErr = "Le licencié $reflic est déjà inscrit dans l'épreuve ".$resepr['EprLibelCourt'];
					} else {
						if (stristr($resepr['EprLibelCourt'], "OPEN") === false) {
							if (!(($reslic["LicSexCode"] == $resepr["CatSexCode"]) && ($reslic["LicNaissance"] >= $resepr["CatDateDéb"]) && ($reslic["LicNaissance"] <= $resepr["CatDateFin"]))) {
								if (!(((CalculCat("", $reslic["LicNaissance"], $reslic["LicSexCode"], 1) == "PF") && ($resepr["CatLibelCourt"] == "BF")) || ((CalculCat("", $reslic["LicNaissance"], $reslic["LicSexCode"], 1) == "PG") && ($resepr["CatLibelCourt"] == "BG")))) {
									$MaReqErr = "La catégorie du licencié $reflic est différente de celle de l'épreuve ".$resepr['EprLibelCourt'];
								}
								if ( ($Sport == 7) || ($Sport == 11) || ($Sport == 12) || ($Sport == 20) ) { 
									if ((($reslic["LicSexCode"] == $resepr["CatSexCode"]) && ($reslic["LicNaissance"] >= $resepr["CatDateDéb"]) && ($reslic["LicNaissance"] <= date('Y/m/d', strtotime('+1 year',strtotime($resepr["CatDateFin"])))  ))) {
										if (!(isset($BModifierSurclassement))) {
											$MaReqErr .= "<BR><BR> &nbsp; < Vous pouvez cliquer sur le bouton 'SurClasser' pour inscrire ce participant < Cliquez sur le bouton 'SurClasser' pour inscrire ce participant (vérifiez au pralable si le règlement l'autorise) >>"; 
											$SurClass = 1;
										} else {
											$MaReqErr = "";
											$SurClass = 0;
										}
									}
								}
							}	
						} else {
							if (!(($reslic["LicSexCode"] == $resepr["CatSexCode"]))) {
								$MaReqErr = "La catégorie du licencié ".$reflic." est différente de celle de l'épreuve ".$resepr['EprLibelCourt'];
							}
						}
					}
				} else {
					$MaReqErr = "Le licencié ".$_POST["ParLicCode"]." est introuvable.";
				}
			}
			
			if (($menu == "competitions") && ($sousmenu == "equipes") && isset($_POST["EprLibelCourt"])) {
				$reqetabcat = bf_mysql_query("SELECT EprCatCode, EprLibelCourt, SpoGestionPerf FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode INNER JOIN `Epreuves Compétitions` ON Compétitions.CompetCode = `Epreuves Compétitions`.EprCompetCompetCode INNER JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode INNER JOIN Catégories ON Epreuves.EprCatCode = Catégories.CatCode WHERE `Epreuves Compétitions`.EprCompetCode = ".$_POST["EprLibelCourt"]);
				if (!(!($reqetabcat))) {
					$resetabcat = mysql_fetch_array($reqetabcat);
					if (!(!($resetabcat))) {
						if ($resetabcat['SpoGestionPerf'] == -1) {
							if ($resetabcat['EprCatCode'] != $_POST["CatLibelCourt"]) {
								$rescat = mysql_fetch_array(bf_mysql_query("SELECT CatLibelCourt FROM Catégories WHERE CatCode = ".$_POST["CatLibelCourt"]));
								$MaReqErr = "La catégorie de l'équipe ".$rescat['CatLibelCourt'] ." est différente de celle de l'épreuve ".$resetabcat['EprLibelCourt'];
							}
						}
					}
				}
			}
									
			if (($menu == "competitions") && ($sousmenu == "references")) {
				$MaCompet = $_POST["modif"]; 
				$ressport = mysql_fetch_array(bf_mysql_query("SELECT SpoLibelCourt FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode WHERE CompetCode = ".$MaCompet));
				if ($_POST['SpoLibelCourt'] <> $ressport['SpoLibelCourt']) {
					$respar = mysql_fetch_array(bf_mysql_query("SELECT COUNT(ParCode) AS NbPar FROM Participations WHERE ParCompetCode = ".$MaCompet));
					$resequ = mysql_fetch_array(bf_mysql_query("SELECT COUNT(EquCode) AS NbEqu FROM Equipes WHERE EquCompetCode = ".$MaCompet));
					if ( ($respar) && ($resequ) ) { 
						if (($respar['NbPar'] > 0) || ($resequ['NbEqu'] > 0) ) {
							$MaReqErr = "Impossible de changer de sport car des participations existent dans la compétition.";
						} else {
							bf_mysql_query("DELETE FROM `Epreuves Compétitions` WHERE EprCompetCompetCode = $MaCompet",0,"`Epreuves Compétitions`");
							$resspo = mysql_fetch_array(bf_mysql_query("SELECT SpoCode FROM Sports WHERE SpoLibelCourt = '".$_POST['SpoLibelCourt']."'"));
							bf_mysql_query("INSERT INTO `Epreuves Compétitions` (`EprCompetEprCode`, `EprCompetCompetCode`) SELECT `EprCode`, $MaCompet AS Compet FROM `Epreuves` WHERE `EprSpoCode` = ".$resspo["SpoCode"]);
						}
					}
				}
				
				$rescompetequ = mysql_fetch_array(bf_mysql_query("SELECT CompetEqu FROM Compétitions WHERE CompetCode = ".$MaCompet));
				if ( ($_POST['CompetEqu'] == 0) && ($rescompetequ['CompetEqu'] == 1) ) {
					$resequ = mysql_fetch_array(bf_mysql_query("SELECT COUNT(EquCode) AS NbEqu FROM Equipes WHERE EquCompetCode = ".$MaCompet));
					if ( $resequ ) { 
						if ($resequ['NbEqu'] > 0) {
							if ($MaReqErr == "") $MaReqErr = "Impossible de changer 'Equ' car des équipes existent dans la compétition.";
						}
					}
				}
			
			}
		
		}
		
		if ($MaReqErr == "") {
			$MaReq = substr( $MaReq1, 0, strlen($MaReq1)-1 );
			$MaReq = $MaReq. " Where $MaKey = $modif ";
			if ($MaReqErr == "") {
				bf_mysql_query($MaReq, 0, "`$tablename`");
				if (($menu == "competitions") && ($sousmenu == "equipes")) {
					bf_mysql_query("UPDATE Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode INNER JOIN Equipes ON Compétitions.CompetCode = Equipes.EquCompetCode INNER JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode INNER JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode SET EquCatCode = EprCatCode WHERE SpoGestionPerf = -5 AND EquCompetCode = $Compet", 0, "`Equipes`");
					RenumeroteEquipes($Compet);
				}
				if ($licence == 1) {
					if (isset($_GET['valid'])) {
						bf_mysql_query("UPDATE Licenciés SET LicInscrit = TRUE, LicDateValid = CURDATE() WHERE $MaKey = $modif");
						$message = "La demande de licence de ".$_POST["LicNom"]." ".$_POST["LicPrénom"]." a été validée.";
					}
					if (!($Adm)) bf_mysql_query("UPDATE Licenciés SET LicDateAss = CURDATE() WHERE LicAss = TRUE AND $MaKey = $modif");
				}
				if ($menu == "etablissements") bf_mysql_query("UPDATE Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode SET EtabMemo3 = IF(RAND() > 0.33, IF(RAND() > 0.66, CONCAT(SecLibel, LOWER(EtabNomCourt), FLOOR(RAND()*100)), CONCAT(LOWER(EtabNomCourt), SecLibel, FLOOR(RAND()*100))), CONCAT(FLOOR(RAND()*100), LOWER(EtabNomCourt), SecLibel)) WHERE EtabMemo3 = '' OR EtabMemo3 IS NULL");
				$modif = "";
			}
		}
		bf_mysql_query("UNLOCK TABLES");
	}
	
	if ($action == "deleteData") {
	
		$queryStrDelete = "DELETE FROM `$tablename` WHERE $MaKey = $suppr";
		$mesTables = "`$tablename`";
	
		if ( ($menu == "parametres") && ($sousmenu == "sports") ) {
			$mesTables = array("`Sports`", "`Participations`", "`Equipes`", "`Compétitions`","`Epreuves Compétitions`", "`Tours Epreuves Compétitions`", "`Epreuves`", "`Catégories`", "`Groupes`");
			bf_mysql_query("DELETE `Participations` FROM Participations INNER JOIN Compétitions ON Participations.ParCompetCode = Compétitions.CompetCode WHERE CompetSpoCode = $suppr",0,$mesTables);
			bf_mysql_query("DELETE `Equipes` FROM Equipes INNER JOIN Compétitions ON Equipes.EquCompetCode = Compétitions.CompetCode WHERE CompetSpoCode = $suppr");
			bf_mysql_query("DELETE `Tours Epreuves Compétitions` FROM Compétitions INNER JOIN `Epreuves Compétitions` ON Compétitions.CompetCode = `Epreuves Compétitions`.EprCompetCompetCode INNER JOIN `Tours Epreuves Compétitions` ON `Epreuves Compétitions`.EprCompetCode = `Tours Epreuves Compétitions`.TouEprCompetEprCompetCode WHERE CompetSpoCode = $suppr");
			bf_mysql_query("DELETE `Epreuves Compétitions` FROM `Epreuves Compétitions` INNER JOIN Compétitions  ON `Epreuves Compétitions`.EprCompetCompetCode = Compétitions.CompetCode WHERE CompetSpoCode = $suppr");
			bf_mysql_query("DELETE `Compétitions` FROM Compétitions WHERE CompetSpoCode = $suppr");
			bf_mysql_query("DELETE `Epreuves` FROM `Epreuves` WHERE EprSpoCode = $suppr");
			bf_mysql_query("DELETE `Catégories` FROM `Catégories` WHERE CatSpoCode = $suppr");
			bf_mysql_query("DELETE `Groupes` FROM `Groupes` WHERE GrSpoCode = $suppr");
		}

		if ( ($menu == "parametres") && ($sousmenu == "categories") ) {
			$mesTables = array("`Catégories`", "`Participations`", "`Equipes`", "`Epreuves Compétitions`", "`Tours Epreuves Compétitions`", "`Epreuves`");
			bf_mysql_query("DELETE `Participations` FROM `Epreuves` INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`. EprCompetEprCode INNER JOIN Participations ON `Epreuves Compétitions`.EprCompetCode = Participations.ParEprCode WHERE EprCatCode = $suppr",0,$mesTables);
			bf_mysql_query("DELETE `Equipes` FROM Equipes WHERE EquCatCode = $suppr");
			bf_mysql_query("DELETE `Tours Epreuves Compétitions` FROM `Epreuves` INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`. EprCompetEprCode INNER JOIN `Tours Epreuves Compétitions` ON `Epreuves Compétitions`.EprCompetCode = `Tours Epreuves Compétitions`.TouEprCompetEprCompetCode WHERE EprCatCode = $suppr");
			bf_mysql_query("DELETE `Epreuves Compétitions` FROM Epreuves INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCatCode = $suppr");
			bf_mysql_query("DELETE `Epreuves` FROM `Epreuves` WHERE EprCatCode = $suppr");	
		}
		
		if ( ($menu == "parametres") && ($sousmenu == "epreuves") ) {
			$mesTables = array("`Epreuves`", "`Participations`", "`Equipes`", "`Epreuves Compétitions`", "`Tours Epreuves Compétitions`");
			bf_mysql_query("DELETE `Participations` FROM `Epreuves Compétitions` INNER JOIN Participations ON `Epreuves Compétitions`.EprCompetCode = Participations.ParEprCode WHERE EprCompetEprCode = $suppr",0,$mesTables);
			bf_mysql_query("DELETE `Equipes` FROM `Epreuves Compétitions` INNER JOIN Equipes ON `Epreuves Compétitions`.EprCompetCode = Equipes.EquEprCompetCode WHERE EprCompetEprCode = $suppr");
			bf_mysql_query("DELETE `Tours Epreuves Compétitions` FROM `Epreuves Compétitions` INNER JOIN `Tours Epreuves Compétitions` ON `Epreuves Compétitions`.EprCompetCode = `Tours Epreuves Compétitions`.TouEprCompetEprCompetCode WHERE EprCompetEprCode = $suppr");
			bf_mysql_query("DELETE `Epreuves Compétitions` FROM `Epreuves Compétitions` WHERE EprCompetEprCode = $suppr");
		} 
	
		if ( ($menu == "etablissements") ) {
			$mesTables = array("`Etablissements`", "`Participations`", "`Equipes`", "`Licenciés`");
			bf_mysql_query("DELETE Participations FROM Participations INNER JOIN Licenciés ON Participations.ParLicCode = Licenciés.LicNumLicence WHERE LicEtabCode = $suppr",0,$mesTables);
			bf_mysql_query("DELETE Equipes FROM Equipes WHERE EquEtabCode = $suppr");
			bf_mysql_query("DELETE Licenciés FROM Licenciés INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode WHERE LicEtabCode = $suppr");
		}
	
		if ( ($menu == "licencies") && (!($licence == 1)) ) {
			$mesTables = array("`Licenciés`", "`Participations`");
			bf_mysql_query("DELETE Participations FROM Participations INNER JOIN Licenciés ON Participations.ParLicCode = Licenciés.LicNumLicence WHERE LicCode = $suppr");
		}
	
		if ( ($menu == "competitions") && ($sousmenu == "references") ) {
			$mesTables = array("`Compétitions`", "`Participations`", "`Equipes`", "`Epreuves Compétitions`", "`Tours Epreuves Compétitions`");
			bf_mysql_query("DELETE `Epreuves Compétitions` FROM `Epreuves Compétitions` WHERE EprCompetCompetCode = $suppr",0,$mesTables);
			bf_mysql_query("DELETE Participations FROM Participations WHERE ParCompetCode = $suppr");
			bf_mysql_query("DELETE Equipes FROM Equipes WHERE EquCompetCode = $suppr");
			bf_mysql_query("DELETE `Tours Epreuves Compétitions` FROM `Tours Epreuves Compétitions` WHERE EprCompetCompetCode = $suppr");
		}
		
		if ($licence == 1) {
			$queryStrDelete = "";
			$nbDem = 0;
			$req = bf_mysql_query("SELECT COUNT(*) FROM Compétitions WHERE CompetDemLic LIKE '%".$_GET['Lic']."%'"); 
			if ( (!(!($req))) && ($menu == "competitions") ) {
				$res = mysql_fetch_array($req);
				if ($res) $nbDem = $res[0];
			}
			if ($nbDem == 0) bf_mysql_query("UPDATE Licenciés SET LicInscrit = FALSE, LicAss = FALSE, LicNomAss = NULL, LicDateAss = NULL, LicDateDem = NULL, LicDateValid = NULL WHERE $MaKey = $suppr");
			$message = "La demande de licence de ".$_GET["LicNom"]." ".$_GET["LicPrénom"]." a été annulée.";
		}
		
		bf_mysql_query($queryStrDelete);
		if (!($MajChpOrdre == "")) MajOrdre($MajChpOrdre);
		
		if (($menu == "competitions") && ($sousmenu == "equipes")) {RenumeroteEquipes($Compet);}
		
		bf_mysql_query("UNLOCK TABLES");
		$action = "VoirMenu";
	}
	
	if (($action == "monter") || ($action == "descendre")) {
		$WhereCompOrdre = "";
		if ($tablename == "Catégories") $WhereCompOrdre = "AND CatSpoCode = $ListeSport";
		if ($tablename == "Epreuves")   $WhereCompOrdre = "AND EprSpoCode = $ListeSport";
		$ResOrdre = mysql_fetch_array(bf_mysql_query("SELECT $MaKey, Ordre From $tablename Where $MaKey = $changeordre"));
		if ($ResOrdre) {
			if( $action == "descendre" ) {
				$ResMax = mysql_fetch_array(bf_mysql_query("SELECT MAX(Ordre) AS Max FROM $tablename WHERE Ordre < ".$ResOrdre["Ordre"]." $WhereCompOrdre ORDER BY Ordre")); 
				$ResPrec = mysql_fetch_array(bf_mysql_query("SELECT $MaKey, Ordre From $tablename Where Ordre = ".$ResMax["Max"])); 
				if ($ResPrec) {
					$ResChange = bf_mysql_query("UPDATE $tablename SET Ordre = ".$ResPrec["Ordre"] ." WHERE $MaKey = ".$ResOrdre["$MaKey"],0 , "`$tablename`"); 	
					$ResChange = bf_mysql_query("UPDATE $tablename SET Ordre = ".$ResOrdre["Ordre"] ." WHERE $MaKey = ".$ResPrec["$MaKey"]); 	
				}
			} else {
				$ResMin = mysql_fetch_array(bf_mysql_query("SELECT MIN(Ordre) AS Min FROM $tablename WHERE Ordre > ".$ResOrdre["Ordre"]." $WhereCompOrdre ORDER BY Ordre")); 
				$ResSuiv = mysql_fetch_array(bf_mysql_query("SELECT $MaKey, Ordre From $tablename Where Ordre = ".$ResMin["Min"])); 
				if ($ResSuiv) {
					$ResChange = bf_mysql_query("UPDATE $tablename SET Ordre = ".$ResSuiv["Ordre"] ." WHERE $MaKey = ".$ResOrdre["$MaKey"],0 , "`$tablename`"); 	
					$ResChange = bf_mysql_query("UPDATE $tablename SET Ordre = ".$ResOrdre["Ordre"]." WHERE $MaKey = ".$ResSuiv["$MaKey"]); 	
				}
			}
		}
		$action = "VoirMenu"; 
		bf_mysql_query("UNLOCK TABLES");
	}

	if ((isset($BValidernumlicence)) && (!(empty($BValidernumlicence))) && (!(empty($_POST["ParLicCode"])))) {
		$monsport = 1;
		$reqsport = bf_mysql_query("SELECT CompetSpoCode FROM Compétitions WHERE CompetCode = $Compet");
		if ($reqsport) {
			$ressport = mysql_fetch_array($reqsport);
			if ($ressport) $monsport = $ressport["CompetSpoCode"];	
		}
		$reqlicstr = "SELECT Secteurs.*, Etablissements.*, Licenciés.*, CatLibelCourt FROM Catégories, Licenciés INNER JOIN Etablissements On Licenciés.LicEtabCode = Etablissements.EtabCode INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode WHERE (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = "."1"." And LicNumLicence = ".$_POST["ParLicCode"];
		if (!($Adm)) $reqlicstr .= " And (EtabNum = ".$Etab.RetAS($Etab).")";
		
		$reqlic = bf_mysql_query($reqlicstr);
		
		if (!(!($reqlic))) {
			$reslic = mysql_fetch_array($reqlic);
			if (!($reslic)) {
				$baseext = bf_mysql_query("SELECT `BasesExternes` FROM `Paramweb`"); 
				if ((!(!$baseext)) && (mysql_num_rows($baseext) > 0)) {
					$dataext = mysql_fetch_assoc($baseext); 
					$tabdataext = explode(";", $dataext["BasesExternes"]);
					for ($i = 0; $i < count($tabdataext); $i++) {
						$ficext = "../$tabdataext[$i]/inscriptions/index.php";
						if (file_exists($ficext)) {
							$reslic = mysql_fetch_array(bf_mysql_query($reqlicstr, 0, "", TrouveDansFic($ficext,"BDD"), TrouveDansFic($ficext,"UTILISATEUR"),TrouveDansFic($ficext,"MDP")));
							if (!(!($reslic))) {
								Maj(1,"Secteurs", $reslic);
								Maj(1,"Etablissements", $reslic, Array("EtabSecCode" => "SELECT SecCode From Secteurs WHERE SecLibel = ".$reslic["SecLibel"]));
								Maj(1,"Licenciés", $reslic, Array("LicEtabCode" => "SELECT EtabCode From Etablissements WHERE EtabNum = ".$reslic["EtabNum"]));
								break;
							}
						}
					}
				} 
			}
		}
		if ( (!($reqlic)) || ((!(!($reqlic))) && (!($reslic)))) $MaReqErr = "Le licencié ".$_POST["ParLicCode"]." est introuvable.";
	}
	
	if ( ($menu == "competitions") && ($sousmenu == "individuels") ) {
		if (  ((isset($BValidernumlicence)) && (!(empty($BValidernumlicence))) && (empty($_POST["ParLicCode"]))) || ((!(isset($message))) && (isset($BAjouter)) && ( (empty($BValidernumlicence)) || (empty($_POST["ParLicCode"])) || (strlen($_POST["ParLicCode"]) < 9)))) {
			$MaReqErr = "Saisissez un N° de licence puis cliquez sur le bouton Ok";
		}
	}
	if ((isset($BFiltrer)) || (isset($racnom))) { 
		$MaReq1="";
		
		if (isset($BFiltrer)) { 
			if (isset($filtre1[$racnom])) unset ($filtre1[$racnom]);
			if (isset($racnom)) unset($racnon); 
			if (isset($racval)) unset ($racval); 
		}
		
		for( $j = 0; $j < $col; $j++ ) {
			$field = mysql_fetch_field( $pResult, $j );
			if (isset($_POST["filtre".$field->name])) {
			} else {
				if ( (isset($racnom)) && ($racnom == $field->name) ) {
					$_POST["filtre".$field->name] = $racval;	
				}
				if (is_array($filtre1)) { 
					if (array_key_exists($field->name, $filtre1)) {
						if ( (isset($racnom)) && ($racnom == $field->name) ) {
							if (empty($filtre1[$field->name])) {
								$_POST["filtre".$field->name] = $filtre1[$field->name]; 
							} else {
								unset($filtre1[$field->name]);
								$_POST["filtre".$field->name] = "";
							}
						} else {
							$_POST["filtre".$field->name] = $filtre1[$field->name];
						}
					}
				}
			}
			
			if (isset($_POST["filtre".$field->name]) && ($ChampsFiltre[$j]) && ($_POST["filtre".$field->name] != "")) {
				$Data = $_POST["filtre".$field->name];
				$filtre1["$field->name"] = $Data;
				if ($field->type == 'date') { 
					$jour=substr($Data,0,2);
					$mois=substr($Data,3,2);
					$annee=substr($Data,-4);
					if ((strlen($Data) == 10) && (is_numeric($jour)) && (is_numeric($mois)) && (is_numeric($annee))) {
						if (checkdate($mois,$jour,$annee)) {  
							$Data = $annee."-".$mois."-".$jour;
							$MaErrDate = false;
						}
					}
				}		
				if ($ChampsType[$j] == 'ListeS') { 
					if (array_search($Data, $ChampsTypeExt[$j]) !== false) {
						$MonArray = Array_Keys($ChampsTypeExt[$j], $Data);
						$Data = $MonArray[0];
					} 
				}
				if ($ChampsNomFil[$j] != "") $monchamp = $ChampsNomFil[$j]; else $monchamp = "`".$field->name."`";
				if (($field->type == 'numeric') || ($field->type == 'real')) $MaReq1 = $MaReq1."(".$monchamp." = '$Data') AND "; else {
					$moncrit = ' LIKE "'.$Data.'"';
					if (strtolower($Data) == "vide") $moncrit = ' IS NULL'; 
					if (strtolower($Data) == "pas vide") $moncrit = ' IS NOT NULL'; 
					$MaReq1 = $MaReq1.'('.$monchamp.$moncrit.' ) AND ';
				}
			}
		}
		
		$filter = substr($MaReq1, 0, strlen($MaReq1) - 4);
		$page=1;
	
	}

	if ($where != "") {
		$queryStr = $queryStr." WHERE ".$where;
		if ($filter != "")  {
			$queryUnion = explode('UNION',$queryStr);
			if (count($queryUnion) == 1) {	
				$queryStr = $queryStr." AND $filter";
			} else {
				$queryStr = "";
				for( $f = 0; $f < count($queryUnion); $f++ ) { 
					$queryStr .= $queryUnion[$f]." HAVING $filter";
					if ($f < (count($queryUnion)) - 1) $queryStr .= " UNION ";
				}
			}
		}
	} else if ($filter != "") {
		$queryStr = $queryStr." WHERE $filter";
	}
	
	if ($orderby != "") $queryStr = $queryStr." ORDER BY ".$orderby;
	
	if (isset($BSupprimerTout)) {
		if ($menu == "competitions") {
			if ($sousmenu == "individuels") {
				bf_mysql_query("DELETE FROM Participations WHERE ParCompetCode = $Compet");
			}
			if ($sousmenu == "equipes") {
				bf_mysql_query("DELETE FROM Equipes WHERE EquCompetCode = $Compet");
				bf_mysql_query("UPDATE Participations SET ParEquCode = NULL WHERE ParCompetCode = $Compet");
			}
			$supprtout = false;
		}
	}
	
	if (isset($valideinslic)) {
		if ($optioninslic > 0) {
				$message = " Une demande de licence";
				$trouveAss = TrouveParamweb("AssUgsel", "");
				if ($optioninslic == 1) {
					$messageAss.= " SANS assurance $trouveAss Ugsel"; 
				} else {
					$messageAss.= " AVEC assurance $trouveAss Ugsel";
					bf_mysql_query("UPDATE Licenciés SET LicAss = 1 WHERE LicNumLicence = $ParLicCode");
					bf_mysql_query("UPDATE Licenciés SET LicDateAss = CURDATE() WHERE LicNumLicence = $ParLicCode AND LicDateAss IS NULL");
				}
				bf_mysql_query("UPDATE Compétitions SET CompetDemLic = IF(CompetDemLic IS NULL,',".$ParLicCode."',CONCAT(CompetDemLic,',".$ParLicCode."')) WHERE CompetCode = ".$Compet." AND NOT '".$ParLicCode."' IN(0".TrouveSport($Compet, "CompetDemLic").")");
				$optionIns = TrouveParamweb("InscriptionLic");
				if ($optionIns == 1) {
					bf_mysql_query("UPDATE Licenciés SET LicDateDem = CURDATE() WHERE LicNumLicence = $ParLicCode AND LicDateDem IS NULL");
					$message.= $messageAss." a été effectuée pour $reflicins. Vous pourrez l'inscrire dans la compétition une fois la demande validée par l'Ugsel.";
				}
				if ($optionIns == 2) {
					bf_mysql_query("UPDATE Licenciés SET LicInscrit = TRUE WHERE LicNumLicence = $ParLicCode");
					bf_mysql_query("UPDATE Licenciés SET LicDateDem = CURDATE() WHERE LicNumLicence = $ParLicCode AND LicDateDem IS NULL");
					bf_mysql_query("UPDATE Licenciés SET LicDateValid = CURDATE() WHERE LicNumLicence = $ParLicCode AND LicDateValid IS NULL");
					$message.= $messageAss." a été effectuée pour $reflicins. Vous pouvez maintenant l'inscrire dans la compétition.";
				}
				$BValidernumlicence = "Ok";
		}
	};
		
	if ($MaReqErr != "") {
		echo "<TABLE CLASS='tablemessageerreur'> <TR> <TD> <BLINK><B> Attention ! </B></BLINK> $MaReqErr </TD> </TR> </TABLE>";
		JoueSon('sonpb.wav');
	}
	if (($messagedel != "") && ($action == "confirmedeletedata") ) echo "<TABLE CLASS='tablemessageerreur'> <TR> <TD> $messagedel </TD> </TR> </TABLE>";
	if ($message != "") {
		if(stristr($message, 'Erreur') === FALSE) {
			echo "<TABLE CLASS='tablemessage'> <TR> <TD> $message </TD> </TR> </TABLE>";
			JoueSon('sonok.wav');
		} else {
			echo "<TABLE CLASS='tablemessageerreur'> <TR> <TD> $message </TD> </TR> </TABLE>";
			JoueSon('sonpb.wav');
		}
	}
	
	if (($Adm) && (($action == "importer"))&& ($imp == true)) {
		
		if (!(  (($menu == "parametres") || ($menu == "competitions") || ($menu == "outils") )&& (isset($_POST['upload']) && (($_FILES['userfile']['size'] > 0) || ($ListeImportCompet != "") || ($ListeImportCompetInterne != "") )))) {
			
			echo "<form method='post' enctype='multipart/form-data'>";
			echo "<table CLASS = 'tableopt'>";
			echo "<TR>"; 
			echo "<TD>";
			
			if (($menu == "competitions") && ($sousmenu == "references")) { 
				$req = bf_mysql_query("SELECT SpoLibelCourt FROM Sports WHERE SpoCode = $Sport");
				if (!(!$req)) {
					$res = mysql_fetch_array($req);
					if (!(!$res)) $MonSportLibel = $res["SpoLibelCourt"];
				}
				$req = bf_mysql_query("SELECT CompetCode, CompetLibellé, DATE_FORMAT(CompetDateDéb,'%d/%m/%Y') AS CompetDateDéb, CompetLieu, SpoLibelCourt FROM `Sports` INNER JOIN `Compétitions` ON `Sports`.SpoCode = `Compétitions`.CompetSpoCode WHERE CompetCode <> ". $Compet ." AND SpoLibelCourt = '".$MonSportLibel."'");
				if ((!(!$req)) && (mysql_num_rows($req) > 0)) {
					echo " Importer la compétition ";
					listederoulante("ListeImportCompetInterne", "Compétition...", "SELECT CompetCode, CompetLibellé, DATE_FORMAT(CompetDateDéb,'%d/%m/%Y') AS CompetDateDéb, CompetLieu, SpoLibelCourt FROM `Sports` INNER JOIN `Compétitions` ON `Sports`.SpoCode = `Compétitions`.CompetSpoCode WHERE CompetCode <> ". $Compet ." AND SpoLibelCourt = '".$MonSportLibel."'", array("SpoLibelCourt","-","CompetLibellé","-","CompetDateDéb","-","CompetLieu"), array("","","","","","",""),"CompetCode" , 0,  350);
					echo " &nbsp; Ou &nbsp; <BR><BR>";
				}
				$tabImport = RetourneFic(".", "Compétition","Comp", "");
				$listeImport = array();
				if (count($tabImport) > 0) {
					for( $i = 0; $i < count($tabImport); $i++ ) {
						if ( ($MonSportLibel) == ($tabImport[$i]["Sport"]) ) {
							$listeImport[$i] = $tabImport[$i]["Résumé"];
							$listeImportClé[$i] = $tabImport[$i]["Nom"];
						}
					}
				}
				if (count($listeImport) > 0) {
					echo "Importer du serveur une compétition en attente ";
					listederoulante("ListeImportCompet", "Fichiers...", $listeImport, "", "", $listeImportClé, $ListeImportCompet, 230);
					echo " &nbsp; Ou &nbsp; <BR><BR>";
				}
			}
			
			echo " Importer le fichier ";
			echo "<input type='hidden' name='MAX_FILE_SIZE' value='50000000'>";
			echo "<input name='userfile' type='file' id='userfile' >&nbsp;"; 
			echo "<input name='upload' type='submit' id='upload' value='Importer' class='bouton'>";
			if (($menu == "etablissements") || ($menu == "licencies")) echo " <B>&nbsp; Attention ! Cette opération peut durer plusieurs minutes...</B>";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			echo "</FORM>";
		} 
	}
		
	$pResult = bf_mysql_query($queryStr);
	
	if (!$pResult) {
		echo "<TABLE CLASS='tablemessageerreur'> <TR> <TD> Impossible de lire les données pour l'instant.</TD> </TR> </TABLE> <BR>";
	} else {
	
	$row = mysql_num_rows( $pResult );
	$col = mysql_num_fields( $pResult );

	echo "<form name='formaffichelignes' id='formaffichelignes' action='$PHP_SELF' method=post>\n";
	
	ConstruitZone(array(array("menu",$menu),array("sousmenu",$sousmenu),array("action",$action)));
	ConstruitZone(array(array("tablename",$tablename),array("MaKey",$MaKey),array("orderby",$orderby),array("filter",$filter)));
	ConstruitZone(array(array("Compet",$Compet),array("ListeSport",$ListeSport)));
	ConstruitZone(array(array("modif",$modif),array("suppr",$suppr),array("supprtout",$supprtout)));
	ConstruitZone(array(array("fi",$fi),array("aj",$aj)));
	ConstruitZone(array(array("BValidernumlicence",$BValidernumlicence)));
	ConstruitZone(array(array("affcompet",$affcompet)));
	ConstruitZone(array(array("licence",$licence)));
	ConstruitZone(array(array("selectionner",$selectionner)));
	
	$montableau = array(
		"menu" => $menu, "sousmenu" => $sousmenu,"action" => $action,
		"tablename" => $tablename,"MaKey" => $MaKey, "orderby" => $orderby, "filter" => $filter,
		"ListeSport" => $ListeSport,
		"modif" => $modif, "suppr" => $suppr,"supprtout" => $supprtout,
		"fi" => $fi, "aj" => $aj,
		"affcompet" => $affcompet,
		"page" => $page,"filtre1" => $filtre1,
		"stat" => $stat,
		"horscat" => $horscat,
		"licence" => $licence,
		"selectionner" => $selectionner
	);
	if (isset($Compet)) $montableau["Compet"] = $Compet;
	$par = EcritParam(serialize($montableau));
	$alea = Rand(1,9999);
	
	if ($rowperpage == "") $rowperpage = $_SESSION['LignesParPage'];
	if (!($Adm)) $rowperpage = 999999;
	if (!(isset($page))) $page = 0; else $page--;
	if (is_int($row/$rowperpage)) $max = $row/$rowperpage; else $max = (int)($row/$rowperpage) + 1;
	if ($page * $rowperpage >= $row) $page = $max - 1; 
	if ($row == 0) $nbaffiche = 0; else if ($page < $max - 1) $nbaffiche = $rowperpage; else $nbaffiche = $row - ($page * $rowperpage);
	if ($row > 0) mysql_data_seek( $pResult, $page * $rowperpage );
		
	if ($selection == 0) {
		$selecteur = "<TABLE CLASS ='tableselecteur'><TR><TD>";	
		if ($Adm) {
			if( $page > 0 ) {
				$selecteur .= "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&modif=&suppr=&page=1&Etab=$Etab'><< </a>";
				$selecteur .=  "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&modif=&suppr=&page=$page&Etab=$Etab'>< </a>";
			} else $selecteur .=  "<span class='pasimprimer'> << < </span>";
			$selecteur .=  " Page ".($page+1)."/".$max;		
			if( $page < $max-1) {
				$selecteur .=  "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&modif=&suppr=&page=".($page+2)."&Etab=$Etab'> ></a>";
				$selecteur .=  "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&modif=&suppr=&page=$max&Etab=$Etab'> >></a>";
			} else $selecteur .=  "<span class='pasimprimer'> > >> </span>";
		}
		if ($filter != "") $tfiltre = "(filtre actif)"; else $tfiltre = "";
		if ($nbaffiche > 1) $s="s"; else $s="";
		$selecteur .= "&nbsp;&nbsp;$nbaffiche ligne$s affichée$s";
		if ($Adm) $selecteur .= " sur un total de $row";
		$selecteur .= "  $tfiltre"; 
		if ( ($Adm) && ($menu == "licencies") && ($horscat == 0) && ($stat == 0) && ($fi == 0)) {
			$pResulttot = bf_mysql_query("SELECT * FROM Licenciés");
			$rowtot = mysql_num_rows($pResulttot);
			if ($row != $rowtot) $selecteur .=  "&nbsp; &nbsp; <BLINK> Attention ! </BLINK><a href='$PHP_SELF?".$par."action=GereData&horscat=1&aj=0'>Afficher la liste des licenciés Hors Catégories</a>\n";
		}
		$selecteur .=  "</TD></TR></TABLE>\n";
	}	
			
	echo $selecteur;
			
	echo "<table CLASS = 'tablecompets'>\n";
	
	echo "<tr>\n";
	for( $i = 0; $i < $col; $i++ ) {
		$field = mysql_fetch_field( $pResult, $i );
		if ($NomsColonnes !== "") $ColNom = $NomsColonnes[$i]; else $ColNom = $field->name;
		if ($ChampsTri !== "")  {
			$Tri = $field->name;
			if ($ChampsTri[$i] != "") $Tri = $ChampsTri[$i];
			if ($ChampsTri[$i] == "/") $Tri = "";
		}
		if (($ChampsAff !== "") && ($ChampsAff[$i])){
			echo "<th"; 
			if ($fi) echo " CLASS = 'thfiltre' ";
			echo ">";
			if (($Tri != "") && ($row > 1)) echo "<a href='$PHP_SELF?".$par."action=VoirMenu&orderby=$Tri&Etab=$Etab&sousmenu=$sousmenu&Compet=$Compet'>".$ColNom."</a>"; else echo $ColNom;
			echo "</th>\n";
		}
	}

	if ($sousqueryStr != "") {
		echo "<th"; 
		if ($fi) echo " CLASS = 'thfiltre' ";
		echo ">";
		echo $sousqueryStr[1];
		echo "</th>\n";
	}
	
	if ($Choix != "") {

	echo "<TH CLASS = '";
	if ($fi) echo "thdercolfiltre"; else echo "thdercol";
	echo "'>";
	
	if ( (in_array("ajout",$Choix)) || (in_array("filtrage",$Choix)) || (in_array("exporter",$Choix)) || ((in_array("suppressiontout",$Choix)) && ($row > 0)) || (in_array("consultation",$Choix)) || ((in_array("stat",$Choix)) && ($row > 0)) || ((in_array("licence",$Choix)) && ($row > 0)) ) {
		
		if ( (in_array("licence",$Choix)) && ($licence == 1)  ) {
			
			$req = bf_mysql_query("SELECT `ImpressionLic` FROM `Paramweb`"); 
			if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = $data["ImpressionLic"];} else $data = 0;
			
			if ( ((!($Adm))&&($data == '1')) || ((($Adm))&&($data > 0)) ) echo "<input name='exporter' type='submit' id='exporter' value='Imprimer Licence(s)' class='bouton'> ";
			
			echo "<input type='hidden' name='optionexporttype' value='exppdf' checked='checked'>";
			if ((in_array("filtrage",$Choix)) && ($supprtout==false) && ($row > 0) ) {
				if ($fi==true) {
					$invfi=false;
					$annulefiltre="&filter=&filtre1="; 
				} else {
					$invfi=true;
					$annulefiltre="";
				}
				echo "<a href='$PHP_SELF?".$par."action=filtredata&fi=$invfi".$annulefiltre."'>Filtrage</a>\n";
			}
			
			echo "<a href='$PHP_SELF?".$par."action=GereData&licence=0&aj=0";
			if ($menu == "competitions") echo "&sousmenu=individuels&fi=0&filter=&filtre1=&orderby=";
			echo "'>Fermer</a>\n"; 
			
			if ( ((!($Adm))&&($data == '1')) || ((($Adm))&&($data > 0)) ) {
				echo "<BR>Sélection : "; 
				echo " <a href='$PHP_SELF?".$par."action=GereData&licence=1&aj=0&seltous=1'>Tous</a>\n"; 
				echo "/";
				echo " <a href='$PHP_SELF?".$par."action=GereData&licence=1&aj=0&selaucun=1'>Aucun</a>\n"; 
			}
		
		} else {
		
		if (in_array("ajout",$Choix)  && ($supprtout==false) ) {
			if ($aj==true) $invaj=false; else $invaj=true; 
			echo "<a href='$PHP_SELF?".$par."action=ajoutedata&aj=$invaj";
			if (!($Adm)) {;echo "&fi=&filter=";}
			echo "'>";
			if (($menu == "competitions") && (($sousmenu == "individuels") || ($sousmenu == "equipes"))) {
				if ($aj == false) echo "<BLINK>";
				echo "<B>Inscription</B>";
				if ($aj == false) echo "</BLINK>";
			} else echo "Ajout";
			echo "</a>\n";
		}
		
		if ( (in_array("licence",$Choix)) && (!($licence == 1)) && ($supprtout==false) && ( ($row > 0) || ($Adm) ) ) {
			echo " <a href='$PHP_SELF?".$par."action=GereData&licence=1&aj=0";
			if ($menu == "competitions") echo "&fi=0&filter=&filtre1=&orderby=";
			echo "'>Licences</a>\n";
		}
		
		if ((in_array("suppressiontout",$Choix)) && ($row > 0) ) {
			if ($supprtout==true) $invsupprtout=false; else $invsupprtout=true; 
			if ($supprtout==false) {
				echo "<a href='$PHP_SELF?".$par."action=supprdatatout&aj=&supprtout=$invsupprtout&filter=&filtre1=&fi='>Suppression</a>\n";
			} else {	
				echo "<INPUT TYPE='submit' NAME='BSupprimerTout' VALUE='Supprimer";
				echo " tout !";
				echo "' class='boutongrand'>&nbsp;";
				echo "<a href='$PHP_SELF?".$par."action=VoirMenu&supprtout='>&nbsp;Annuler&nbsp;</a>";
			}
		}
		
		if ((in_array("filtrage",$Choix)) && ($supprtout==false) && ($row > 0) ) {
			if ($fi==true) {
				$invfi=false;
				$annulefiltre="&filter=&filtre1="; 
			} else {
				$invfi=true;
				$annulefiltre="";
			}
			echo "<a href='$PHP_SELF?".$par."action=filtredata&fi=$invfi".$annulefiltre."'>Filtrage</a>\n";
		}
		
		if ((in_array("importer",$Choix)) && ($menu != "parametres") && ($menu != "competitions")) {
			if ($imp == true) $invimp =false; else 	$invimp =true;
			echo "<a href='$PHP_SELF?".$par."action=importer&imp=$invimp'>Import</a>\n";
		}
		
		if (($stat >= 1) && ($menu == "competitions") && ($sousmenu == "individuels")) {
			echo " <a "; if ($stat == 1) echo "CLASS = 'inv'"; echo "href='$PHP_SELF?".$par."action=GereData&stat=1'>Licence</a>\n"." / ";
			echo " <a "; if ($stat == 2) echo "CLASS = 'inv'" ; echo "href='$PHP_SELF?".$par."action=GereData&stat=2'>Sport</a>\n";
		}
				
		if (in_array("exporter",$Choix) && ($supprtout==false) && ($row > 0) && (($menu == "etablissements") || ($menu == "licencies") || (($menu == "competitions") && ( ($sousmenu != "references") || ($stat == 1) )))) {
			if ($exp == True) $invexp = False; else $invexp = True;
			if ($menu == "etablissements") {echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&exporttype=$exporttype&optionexport=expetab'>Export</a>";}
			if ($menu == "licencies") 	   {echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&exporttype=$exporttype&optionexport=explic'>Export</a>";}
			if ($menu == "competitions")   {echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&exporttype=$exporttype&optionexport=expcompet&Compet=$Compet'>Export</a> \n";}
		}

		if ((in_array("stat",$Choix)) && ($supprtout==false) && ($fi == false) && ($row > 0) ){
			if ($stat == 0) echo " <a href='$PHP_SELF?".$par."action=GereData&stat=1&aj=0&fi=0'>Stat</a>\n"; else echo " <a href='$PHP_SELF?".$par."stat=0'>Liste</a>\n";
		}
				
		if (in_array("liste",$Choix)) {
			echo " <a href='$PHP_SELF?".$par."action=GereData&stat=0&horscat=0&page=1'>Fermer</a>\n"; 
		}

		if (($menu == "competitions") && ($supprtout==false) && ($stat == 0) && ($row > 0) && ((($sousmenu == "individuels") && ($ChampsAff[8])) || ($sousmenu == "individuels(2)")) ) {
			echo "<a "; if ($sousmenu =="individuels")    echo "CLASS = 'inv'"; echo "href='$PHP_SELF?".$par."action=VoirMenu&menu=competitions&sousmenu=individuels&Etab=$Etab&Compet=$Compet'    > &nbsp;1 </a>"; 
			echo "/";
			echo "<a "; if ($sousmenu =="individuels(2)") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?".$par."action=VoirMenu&menu=competitions&sousmenu=individuels(2)&Etab=$Etab&Compet=$Compet&aj=0' > 2&nbsp</a>"; 
		}
		
		}
	}

	echo "</TH>\n";
	echo "</tr>\n";
	
	}
	
	if ( (!($Adm)) && ($aj) && ($menu == "competitions") ) {
		
		if ($sousmenu == "individuels")  {
			echo "<TR><TD COLSPAN='$col'><SPAN CLASS='pasimprimer'> 
			&nbsp;&nbsp;Pour inscrire un participant : Saisissez son <a CLASS = 'inv'; href='$PHP_SELF?action=VoirMenu&menu=licencies&sousmenu=$sousmenu&Compet=$Compet&licence=0&affcompet=$affcompet&selectionner=true';>&nbsp;n° de Licence&nbsp;</a> puis cliquez sur 'Ok'. &nbsp;Complétez si besoin les autres champs (Epreuve...) et cliquez sur 'Inscrire'.<BR>";
			if ($ChampsAff[12]) 
			echo "<BLINK><B>&nbsp;&nbsp; -> Attention ! </B></BLINK> Si le participant appartient à une équipe qui ne figure pas dans la liste 'Equipe...', procédez au préalable à l'
			<a CLASS = 'inv'; href='$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=equipes&Compet=$Compet&affcompet=$affcompet'>&nbsp;inscription d'une équipe&nbsp;</a>
			</SPAN></TD></TR>";
		}
		if ($sousmenu == "equipes")  {
			echo "<TR><TD COLSPAN='$col'><SPAN CLASS='pasimprimer'>
			&nbsp;&nbsp;Remarque : La numérotation des relais est automatique et les numéros déjà attribués peuvent être modifiés suite à l'inscription d'un nouveau relais. &nbsp;
			<BR> &nbsp; Pour les relayeurs, saisissez les numéros de Licence.";
			echo "</SPAN></TD></TR>";
		}
	}
		
	for( $i = -2; $i < $rowperpage; $i++ ) {
		if ($i>=0) {
			
			$rowArray = mysql_fetch_array( $pResult ); 
			if( $rowArray == false ) break;
		}
	
		$key = "";
		for( $j = 0; $j < $col; $j++ ) {
			$field = mysql_fetch_field( $pResult, $j );
			if ($i>=0) {
				$data = $rowArray[$j];
				if ($MaKey != "" && $field->name == $MaKey) $key = $data;
			}
		}	

		if (($i >= 0 ) || (($i == -2) && (($Choix != "") && in_array("filtrage",$Choix)) && ($fi == true)) || (($i == -1) && (($Choix != "") && in_array("ajout",$Choix)) && ($aj == true))) {
			echo "<TR CLASS = '";
			if ($i == -1) echo "tredit";
			if ($i >=  0) { 
				if ( (($action == "modifiedata") && ($key == $modif))
				|| ( ($menu == "parametres") && ($action == "importer") && ($key == $Sport)  && ($imp == True) )  
				|| ( ($menu == "competitions") && ($action == "importer") && ($key == $Compet)  && ($imp == True) ) 
				|| ( ($menu == "etablissements") && ($action == "importer") && ($key == $EtabImport)  && ($imp == True) ) 
				|| ( ($menu == "parametres") && ($action == "exporter") && ($key == $Sport)  && ($exp == True) )  
				|| ( ($menu == "competitions") && ($action == "exporter") && ($key == $Compet)  && ($exp == True) ) 
				|| ( ($menu == "etablissements") && ($action == "exporter") && ($key == $EtabExport)  && ($exp == True) &&($stat == 0) ) 
				) 
				{
					echo "trimpexp";
				} else {
					if ((($action == "confirmedeletedata") && ($key == $suppr)) || ($supprtout == true) ) {
						echo "trsuppr";
					} else {
						if ($rowArray[0] == " ") echo "trtotal"; else {
							if ($rowArray[0] == "  ") echo "trimpexp"; else {
								if ($rowArray[0] == "   ") echo "trsuppr"; else {
									if (($menu == "licencies") && (isset($selectionner)) && ($selectionner == true)) {
										if ( (round($i / 2) - ($i / 2)) == "0" ) echo "tr1' onmouseover='this.className=\"trsel\"' onmouseout='this.className=\"tr1\""; else echo "tr2' onmouseover='this.className=\"trsel\"' onmouseout='this.className=\"tr2\"";
									} else {
										if ( (round($i / 2) - ($i / 2)) == "0" ) echo "tr1"; else echo "tr2";
									}
								}
							}
						}
					}
				}
			}
			echo "'>\n";
		}
		
		for( $j = 0; $j < $col; $j++ ) {
			
			$field = mysql_fetch_field( $pResult, $j );
			
			if ($i >= 0) {
				$data = $rowArray[$j];
				
				if ($sousqueryStr != "") {
					if ($field->name == $sousqueryStr[2]) $sousqueryStrATrouver = $data;
				}
			
				if ( (strlen( $data ) > 255 ) && ($field->name != 'EquRelayeurs') )$data = substr( $data, 0, 255 ) . "...";
				$data = htmlspecialchars($data);
				if ($field->type == "date") $data = eregi_replace("([0-9].*)-([0-9].*)-([0-9].*)" ,"\\3/\\2/\\1",$data);
				if (($ChampsType[$j] == "ListeS") && (array_key_exists($data,$ChampsTypeExt[$j])) && ($data != "")) {
					$data = $ChampsTypeExt[$j][$data];
				}
			
			}
			
			$testinsert = false; 
			$testinsert = is_array($ChampsInsert); 
			if ($testinsert) $testinsert = $ChampsInsert[$j][1];
			
			if ((($ChampsAff!="") && ($ChampsAff[$j]==true)) || (($ChampsAff!="") && ($ChampsAff[$j]==false) && ($testinsert==true) && ($aj==true)) ) {
				if ($ChampsAli!=='') if ($ChampsAli[$j]=='') $Ali = 'Left'; else $Ali = $ChampsAli[$j];
				if ($ChampsFor!=='') if ($ChampsFor[$j]=='') $Fom = '%s'  ; else $Fom = $ChampsFor[$j];
				if (($i == -2) && ($fi == true)) {
					if (($ChampsAff[$j] == true) && ($ChampsFiltre[$j] == true)) {
						
						$browser = ""; 
						if (ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) $browser = "ie"; 
						echo "<td Align = Center ";
						if ($browser == "") echo " WIDTH = '30px' ";
						echo "><input type='text' maxlength = '255' name='filtre$field->name' style='border-width : 0px; width:";
						if ($browser == "") echo "100"; else echo "95";
						echo "%; text-align:";
						if ($ChampsAli[$j] == '') echo 'Center'; else echo $ChampsAli[$j];
						echo";'";
										
						$maval="";
						if (is_array($filtre1)) { if (array_key_exists($field->name, $filtre1)) $maval = $filtre1[$field->name];}
						if (isset($_POST["filtre".$field->name])) $maval = $_POST["filtre".$field->name];
						if ($maval != "") echo " value = ".'"'.$maval.'"'.""; 
						
						echo "</td>\n";
					} else if ($ChampsAff[$j] == true) echo "<td></td>";
				};
				
				if (($i >= 0) && ($action == "modifiedata") && ($key == $modif))  {
					if ($ChampsEdit[$j][1]==true) {
						if (isset($_POST["$field->name"]))  $data = $_POST["$field->name"]; 
						if (($ChampsEdit[$j][0]=="Texte")  || ($ChampsEdit[$j][0]=="Date")) {
							
							if ($field->name == 'EquRelayeurs') { 
								$arraydata = explode("-",RetRelayeurs($data,1));
								$numre = 1;
								echo "<td>";
								for( $re = 0; $re < 4; $re++ ) {
									if (isset($_POST["EquRelayeurs".$re])) {
										$monre = $_POST["EquRelayeurs".$re];
									} else { 
										if ($re < count($arraydata)) $monre = htmlspecialchars(trim($arraydata[$re])); else $monre = "";
									}
									echo $numre++.". <input type='text' maxlength='255' name='".$field->name.$re."' value='".$monre."' style='border-width : 1px; text-align:".$ChampsAli[$j]."; width:85%';>"; 	
									echo "<BR>&nbsp;&nbsp;".RetRelayeurs($monre,0);
									if ($re < 3) echo "<BR>";
								}
							} else {
								if (($ChampsType[$j] == "Perf") && ($data == 0))  $data = ""; 
								echo "<td><input type='text' maxlength='255' name='$field->name' value= ".'"'.htmlspecialchars($data).'"'." style='border-width : 0px; text-align:".$ChampsAli[$j]."; ";
								if (($menu == "competitions") && ($sousmenu == "individuels") && ($field->name == 'ParLicCode') ) {
									echo "width:80';>"."&nbsp;"."<input type='button' name='BValidernumlicence' value='Ok' class ='boutonpetit'>";
								} else {
									echo "width:100"; 
									if (($row > 0) || ($data != "")) echo "%"; else echo "px";
									echo ";'>";
								}
							}
							echo "</td>\n";
						}
						if (($ChampsEdit[$j][0]=="ListeD") || ($ChampsEdit[$j][0]=="ListeS")) {
							echo "<td>";
							if (($ChampsEdit[$j][0]=="ListeD") && ($ChampsEdit[$j][3][6] != "") && (!(isset($BModifier))) ){
								$req = bf_mysql_query($ChampsEdit[$j][3][6]."'$data'");
								$res = mysql_fetch_array($req); 
								$data =$res[0];
							}
							if (($ChampsEdit[$j][0]=="ListeS") && (isset($BModifier))) {
								$TabFlip = array_flip($ChampsEdit[$j][3][2]);
								$data = array_search("$data",$TabFlip);
							}
							listederoulante($field->name,$ChampsEdit[$j][3][1],$ChampsEdit[$j][3][2],$ChampsEdit[$j][3][3],$ChampsEdit[$j][3][4],$ChampsEdit[$j][3][5], $data, $ChampsEdit[$j][3][8]);
							echo "</td>";
						}
					}
					else echo "<td align=$Ali>".sprintf($Fom, $data)."</td>\n";

				} else if (($i == -1) && ($aj == true)) {
					
					if ($ChampsInsert[$j][1] == true) {
						if ($ChampsAff[$j] != false)  echo "<td style ='white-space:nowrap;'>";
						if ((($ChampsInsert[$j][0]=="ListeD") || ($ChampsInsert[$j][0]=="ListeS")) && ($ChampsInsert[$j][5] == true)) {
							if (isset($_POST["$field->name"])) $data = $_POST["$field->name"]; else $data="";
							if (($ChampsEdit[$j][0]=="ListeS") && (isset($BAjouter))) {
								$TabFlip = array_flip($ChampsInsert[$j][3][2]);
								if (!empty($data)) {
									$data = array_search("$data",$TabFlip);
								} else {
									$TabKeys = array_keys($TabFlip);
								}
							}
							if (($menu == "competitions") && ($sousmenu == "individuels") && ($field->name == "EprLibelCourt") && (isset($_POST["ParLicCode"]))) {
								$reqlicstrinit = "SELECT LicNaissance, LicSexCode FROM Licenciés INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode WHERE LicNumLicence = ".$_POST["ParLicCode"];
								if (!($Adm)) $reqlicstrinit .= " And (EtabNum = ".$Etab.RetAS($Etab).")";
								$reqlicinit = bf_mysql_query($reqlicstrinit);
								if (!(!($reqlicinit))) {
									$reslicinit = mysql_fetch_array($reqlicinit);
									if (!(!($reslicinit))) {
										$reqeprinit = bf_mysql_query("SELECT EprCompetCode
																	  FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																	  WHERE (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND (Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND Catégories.CatDateDéb <= Date('".$reslicinit["LicNaissance"]."') AND Catégories.CatDateFin >= Date('".$reslicinit["LicNaissance"]."')) ORDER BY Epreuves.Ordre LIMIT 1");
										if (!(!($reqeprinit))) {
											$reseprinit = mysql_fetch_array($reqeprinit);
											if ($MaReqErr == "") {if (!(!($reseprinit))) $data = $reseprinit["EprCompetCode"];} else $data = $_POST["EprLibelCourt"];
										}
										$reqeprinitBIS = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt 
																	     FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																	     WHERE Catégories.CatPrim = TRUE $strCatEpr AND (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND (Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND ( (Catégories.CatDateDéb <= Date('".$reslicinit["LicNaissance"]."') AND Catégories.CatDateFin >= Date('".$reslicinit["LicNaissance"]."')) OR (EprLibelCourt LIKE '%Open%') )) 
																		 ORDER BY Epreuves.Ordre");
										$nbLignesEpr = 0;
										$nbLignesEpr = mysql_num_rows($reqeprinitBIS);
										if ($nbLignesEpr > 1) $testNbLignesEpr = true;				
										$reqeprinitTER = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt 
																	     FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																	     WHERE Catégories.CatPrim = TRUE $strCatEpr AND (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND (Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND ( (Catégories.CatDateDéb <= Date('".$reslicinit["LicNaissance"]."') AND Catégories.CatDateFin >= Date('".$reslicinit["LicNaissance"]."')) )) 
																		 ORDER BY Epreuves.Ordre");
										$nbLignesEprSansOpen = 0;
										$nbLignesEprSansOpen = mysql_num_rows($reqeprinitTER);
										if ($nbLignesEprSansOpen == 0) {
											$catInitBIS = CalculCat("", $reslicinit["LicNaissance"], $reslicinit["LicSexCode"], 1);
											if (($catInitBIS == "PF") || ($catInitBIS == "PG")) {
												$reqeprinitBIS = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt 
																				 FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																				 WHERE Catégories.CatPrim = TRUE AND (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND (Catégories.CatLibelCourt ='BF' OR Catégories.CatLibelCourt ='BG' OR EprLibelCourt LIKE '%Open%' OR EprLibelCourt LIKE '%Col %')
																				 ORDER BY Epreuves.Ordre");
												
												$nbLignesEpr = mysql_num_rows($reqeprinitBIS);
												if ($nbLignesEpr > 1) $testNbLignesEpr = true;
											}
										}
									}
								}
							}
							if (!(isset($testNbLignesEpr))) {
								if ($ChampsAff[$j] == true) listederoulante($field->name,$ChampsInsert[$j][3][1],$ChampsInsert[$j][3][2],$ChampsInsert[$j][3][3],$ChampsInsert[$j][3][4],$ChampsInsert[$j][3][5], $data, $ChampsInsert[$j][3][8]);
							}
						} else {	
							if ($field->name == 'EquRelayeurs') { 
								$numre = 1;
								for( $re = 0; $re < 4; $re++ ) {
									if (isset($_POST["EquRelayeurs".$re])) {
										$maval = $_POST["EquRelayeurs".$re]; 
									} else {
										$maval = "";
									}
									echo $numre++.". <input type='text' maxlength='255' name='"."EquRelayeurs".$re."' value='".$maval."' style='border-width : 1px; text-align:".$ChampsAli[$j]."; width:85%';>"; 	
									if (RetRelayeurs($maval,0) != "") echo "<BR> &nbsp; ";
									echo RetRelayeurs($maval,0);
									if ($re < 3) echo "<BR>";
								}
							} else {
							
							if (!(isset($testNbLignesEpr))) {	
							
							$montxt = "";
							echo "<input ";
							if (($ChampsAff[$j] == false) || ($ChampsInsert[$j][5] == false)) echo "Type = 'Hidden' ";
							if (($ChampsInsert != "") && ($ChampsInsert[$j][4] != "")) {
								echo "style='text-align:".$ChampsAli[$j].";' value = '"; 
								if (!(is_array($ChampsInsert[$j][4]))) $montxt = $ChampsInsert[$j][4]; else $montxt = "Max"; 
								echo "$montxt' ";
							}
							if (isset($_POST["$field->name"])) $maval = $_POST["$field->name"]; 
							else {if (($field->name == "ParLicCode") && (isset($Etab)) && ($Etab > 0)) $maval = sprintf('%06s',$Etab); else $maval = "";}
							echo "type='text' maxlength='255' name='$field->name' value='$maval' style='border-width:0px;";
							if (($menu == "competitions") && ($sousmenu == "individuels") && ($field->name == 'ParLicCode') ) {
								echo "width:".(70 + $TAILLE * 7)."px; text-align:center; '>"."&nbsp;";
								echo" <a CLASS = 'inv' href='$PHP_SELF?action=VoirMenu&menu=licencies&sousmenu=$sousmenu&Compet=$Compet&licence=0&affcompet=$affcompet&selectionner=true'>&nbsp;?&nbsp;</a>\n";
								echo "<input type='submit' name='BValidernumlicence' value='Ok' class ='boutonpetit'>"; 
							} else {
								echo "width:100";
								if (($row > 0) || ($montxt != "")) echo "%"; else echo "px";
								echo ";'>";
							}
							echo "\n";
							
							}
							
							}
						}
						if ($ChampsAff[$j] != false) echo "</td>";
					}
					else {
						echo "<td align=$Ali>";
						
						if ((isset($BValidernumlicence)) && (!(empty($BValidernumlicence)) )) {
							$monsport = 1;
							$reqsport = bf_mysql_query("SELECT CompetSpoCode FROM Compétitions WHERE CompetCode = $Compet");
							if ($reqsport) {
								$ressport = mysql_fetch_array($reqsport);
								if ($ressport) $monsport = $ressport["CompetSpoCode"];	
							}
							$reqlicstr = "SELECT EtabNum, EtabNomCourt, LicEtabCode, LicInscrit, LicNumLicence, LicNom, LicPrénom, LicSexCode, LicNaissance, LicDateEnr, LicDateLic, LicAss, LicNomAss, LicObs, CatLibelCourt FROM Catégories, Licenciés INNER JOIN Etablissements On Licenciés.LicEtabCode = Etablissements.EtabCode WHERE (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = 1 And LicNumLicence = ".$_POST["ParLicCode"];
							if (!($Adm)) $reqlicstr .= " And (EtabNum = ".$Etab.RetAS($Etab).")";
							$reqlic = bf_mysql_query($reqlicstr);
														
							if (!(!($reqlic))) {
								$reslic = mysql_fetch_array($reqlic);
								if ($reslic) {
									if ($field->name == "LicNaissance") echo eregi_replace("([0-9].*)-([0-9].*)-([0-9].*)" ,"\\3/\\2/\\1",$reslic["$field->name"]);
									else if ($field->name == "LicSexCode") { if ($reslic["$field->name"] == 1) echo "G"; else echo "F";}
									else echo $reslic["$field->name"]; 
								} else echo " ? ";
							} else echo " ? ";
						}
						
						echo "</td>\n";
					}
				} else if (($i>=0) && ($ChampsAff[$j] == true)) {
					echo "<td align=$Ali";
					if (strlen($data) < 30) echo " NOWRAP";
					echo ">";
					if ($ChampsRacFiltre == "") {
						if ($data != "" ) echo sprintf($Fom, $data);
					} else {
						if (($ChampsRacFiltre[$j] == true)  && (!($Consult)) ){
							$MaVal = sprintf($Fom,$data);
							if ( (isset($racnom)) && (isset($racval)) && ($racnom == $field->name) && ($racval == sprintf($Fom,$data)) && ($data != "") ) $MaVal = "";
							echo "<a href='$PHP_SELF?".$par."action=VoirMenu&fi=1&racnom=".$field->name."&racval=".$MaVal."'>".sprintf($Fom,$data)."</a>";
						} else {
							if ($field->name == 'EquRelayeurs') $data = str_replace(" - ","<BR>",$data); 
							
							if ($field->name == 'CompetLibellé') { 
								$SpoG = TrouveSport($rowArray[0], "SpoGestionPerf") * -1;
								$SpoL = $rowArray["SpoLibelCourt"];
								if ($SpoG == 99) $SpoG = 10;
								if (is_int(strpos($SpoL,"Bad")) || is_int(strpos($SpoL,"TTable"))) $SpoG = 7;
								if (is_int(strpos($SpoL,"Judo")) || is_int(strpos($SpoL,"Escri"))|| is_int(strpos($SpoL,"Combat"))) $SpoG = 8;
								if (is_int(strpos($SpoL,"Vtt")) || is_int(strpos($SpoL,"CO"))|| is_int(strpos($SpoL,"Esca"))|| is_int(strpos($SpoL,"Raid"))) $SpoG = 9;
								$tabCoul = array("green","darkorange","yellow","darkorange","darkorange","Cyan","darkorange","magenta","black","green","white");
								echo " <b style='background:".$tabCoul[$SpoG]."'>&nbsp; &nbsp; </b>&nbsp;";
							}
							
							if ($ChampsType[$j] == "Perf") {
								if ($data != 0) echo FormatPerf($data);
							} else { 
								echo sprintf($Fom,$data);
							}
						}
						echo "</td>\n";
					}
				}
			}
		}

		$mapage = $page + 1;
		
		if (($sousqueryStr != "") ) {
			if ($i >= 0) {
				$Resu = bf_mysql_query($sousqueryStr[0].$sousqueryStrATrouver);
				$Tt ="";
				while ($resu = mysql_fetch_array($Resu)) {
					for ($ii = 0; $ii < count($sousqueryStr[3]); $ii++) {
						$Tt1 = "";
						if ($sousqueryStr[3][$ii][1] != "") {
							if ($sousqueryStr[3][$ii][1] == "==") {if ($resu[$sousqueryStr[3][$ii][0]] == $sousqueryStr[3][$ii][2]) $Tt1 = $sousqueryStr[3][$ii][3];}
							if ($sousqueryStr[3][$ii][1] == "!=") {if ($resu[$sousqueryStr[3][$ii][0]] != $sousqueryStr[3][$ii][2]) $Tt1 = $sousqueryStr[3][$ii][3];}
							if ($sousqueryStr[3][$ii][1] == ">" ) {if ($resu[$sousqueryStr[3][$ii][0]] >  $sousqueryStr[3][$ii][2]) { if ($sousqueryStr[3][$ii][4]) $Tt1 = $resu[$sousqueryStr[3][$ii][3]]; else $Tt1 = $sousqueryStr[3][$ii][3];}}
							if ($sousqueryStr[3][$ii][1] == "<" ) {if ($resu[$sousqueryStr[3][$ii][0]] <  $sousqueryStr[3][$ii][2]) $Tt1 = $sousqueryStr[3][$ii][3];}
						} else {
							$Tt1 = $resu[$sousqueryStr[3][$ii][0]];
						}
						if ($Tt1 != "") {
							if ($Tt != "") $Tt .= " ";
							$Tt .= $Tt1; 
						}
					}
					$Tt .= " / ";
				}
				$Tt = substr($Tt, 0, strlen($Tt) - 3);
				echo "<TD>".$Tt."</TD>";
			}
		}
				
		if ($Choix != "") {

		if ((($i == -2) && in_array("filtrage",$Choix) && ($fi==true)) || (($i == -1) && in_array("ajout",$Choix) && ($aj==true))) echo "<TD CLASS = 'tddercol'>\n";
		
		if (($i == -2) && in_array("filtrage",$Choix) && ($fi==true)) {
				echo "<INPUT TYPE='submit' NAME='BFiltrer' VALUE='Valider' class='boutonmoyen'>&nbsp;\n";
				echo "<a href='$PHP_SELF?".$par."filter=&filtre1=&action=VoirMenu'>&nbsp;Effacer&nbsp;</a>\n";	
				echo "<a href='$PHP_SELF?".$par."filter=&filtre1=&fi=&action=VoirMenu'>&nbsp;Annuler&nbsp;</a>\n";	
			}
		if (($i == -1) && in_array("ajout",$Choix) && ($aj==true)) {
			echo "<INPUT TYPE='submit' NAME='BAjouter' VALUE='";
			if ( ($menu == "competitions") && (($sousmenu == "individuels") || ($sousmenu == "equipes"))) echo "Inscrire"; else echo "Valider";	
			echo "' class='bouton'>&nbsp;\n";
			if ($SurClass == 1)	echo " &nbsp;"."<input type='submit' name='BAjouterSurclassement' value='SurClasser' class ='boutonpetit'> ";
			echo "<a href='$PHP_SELF?".$par."action=VoirMenu&aj='>&nbsp;Annuler&nbsp;</a>\n";
		}
		
		if ((($i == -2) && in_array("filtrage",$Choix) && ($fi==true)) || (($i == -1) && in_array("ajout",$Choix) && ($aj==true))) echo "</TD>";
		
		if ($i >= 0) { 
			if (($action == "modifiedata") && ($key == $modif)) {
				echo "<TD CLASS = 'tddercol'>\n";
				echo "<INPUT TYPE='submit' NAME='BModifier' VALUE='Valider' class='bouton'>&nbsp;\n";
				if ($SurClass == 1)	echo " &nbsp;"."<input type='submit' name='BModifierSurclassement' value='SurClasser' class ='boutonpetit'> ";
				echo "<a href='$PHP_SELF?".$par."action=VoirMenu&modif='>&nbsp;Annuler&nbsp;</a>\n";
				echo "</TD>";
			}
			else {
				if (($action == "confirmedeletedata") && ($key == $suppr)) {
					echo "<TD CLASS = 'tddercol'>\n";
					echo "<a href='$PHP_SELF?".$par."action=deleteData&suppr=$key&LicNom=".$rowArray['LicNom']."&LicPrénom=".$rowArray['LicPrénom']."&Lic=".$rowArray['LicNumLicence']."'>&nbsp;Supprimer&nbsp;</a>\n";
					echo "<a href='$PHP_SELF?".$par."action=VoirMenu'>&nbsp;Annuler&nbsp;</a>\n";
					echo "</TD>\n";
				} else {
					if (count ($Choix) > 0) { 
						echo "<TD CLASS = 'tddercol'>\n";
						if (in_array("monter"   ,$Choix)) {if ((($i > 0) || ( ($i == 0) && ($page > 0))) && ($row > 1 ) && ($filter == "") && (in_array("descendre",$Choix))) echo "<a href='$PHP_SELF?".$par."action=descendre&changeordre=$key&aj='>&nbsp;-&nbsp;</a>"; else echo "<a>&nbsp;-&nbsp;</a>";} 
						if (in_array("descendre",$Choix)) {if ((($page*$rowperpage+$i) < ($row - 1)) && ($row > 1 ) && ($filter == "") && (in_array("monter",$Choix))) echo "<a href='$PHP_SELF?".$par."action=monter&changeordre=$key&aj='>&nbsp;+&nbsp;</a>"; else echo "<a>&nbsp;+&nbsp;</a>";}
						$protect = false; if (($field->name == "SpoGestionPerf") && ($data != -99)) $protect = true;
						
						if ((in_array("modifier" ,$Choix)) && (!($licence == 1)) ) {
							if (!($protect)) {
								$testNbLignesEpr = false;
								if (($menu == "competitions") && ($sousmenu == "individuels")) {
									$reqlicstrinit = "SELECT LicNaissance, LicSexCode FROM Licenciés INNER JOIN Etablissements ON Licenciés.LicEtabCode = Etablissements.EtabCode WHERE LicNumLicence = ".$rowArray[2];
									if (!($Adm)) $reqlicstrinit .= " And (EtabNum = ".$Etab.RetAS($Etab).")";
									$reqlicinit = bf_mysql_query($reqlicstrinit);
									if (!(!($reqlicinit))) {
										$reslicinit = mysql_fetch_array($reqlicinit);
										if (!(!($reslicinit))) {
											$reqeprinitBIS = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt 
																	     FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																	     WHERE Catégories.CatPrim = TRUE AND (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND (Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND Catégories.CatDateDéb <= Date('".$reslicinit["LicNaissance"]."') AND Catégories.CatDateFin >= Date('".$reslicinit["LicNaissance"]."')) ORDER BY Epreuves.Ordre");
											$nbLignesEpr = 0;
											$nbLignesEpr = mysql_num_rows($reqeprinitBIS);
											if ($nbLignesEpr > 1) $testNbLignesEpr = true;				
											if ($nbLignesEpr == 0) {
												$catInitBIS = CalculCat("", $reslicinit["LicNaissance"], $reslicinit["LicSexCode"], 1);
												if (($catInitBIS == "PF") || ($catInitBIS == "PG")) {
													$reqeprinitBIS = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt 
																				 FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
																				 WHERE Catégories.CatPrim = TRUE AND (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet." AND Catégories.CatSexCode = ".$reslicinit["LicSexCode"]." AND (Catégories.CatLibelCourt ='BF' OR Catégories.CatLibelCourt ='BG') ORDER BY Epreuves.Ordre");
													$nbLignesEpr = mysql_num_rows($reqeprinitBIS);
													if ($nbLignesEpr > 1) $testNbLignesEpr = true;
												}
											}
										}
									}
								}
								if ( $testNbLignesEpr) {
									echo "<a href='$PHP_SELF?".$par."supprtout=&BValidernumlicence=Ok&action=ajoutedata&aj=1&selectionner=true&ParLicCode=".$rowArray['ParLicCode']."'>&nbsp;Modifier&nbsp;</a>"; 
								} else {
									echo "<a id='$i' href='$PHP_SELF?".$par."al=".$alea."&action=modifiedata&modif=$key&aj=&supprtout=#".($i-10)."'>&nbsp;Modifier&nbsp;</a>"; 
								}
							} else {
								echo "&nbsp;Modifier&nbsp;";
							}
						}
												
						if ((in_array("supprimer",$Choix)) && (!($licence == 1)) ) {if (!($protect)) echo "<a id='$i' href='$PHP_SELF?".$par."action=confirmedeletedata&suppr=$key&aj=&supprtout=#".($i-4)."'>&nbsp;Supprimer&nbsp;</a>"; else echo "&nbsp;Supprimer&nbsp;";}
						
						if ((in_array("importer",$Choix)) && (($menu == "etablissements") || ($menu == "competitions") || ($menu == "parametres"))) {
							if (($imp == True) && ( ($Compet == $key) || ($Sport == $key) )) $invimp = False; else $invimp = True;
							if ($menu == "etablissements") echo "<a href='$PHP_SELF?".$par."action=importer&imp=$invimp&EtabImport=$key'> Import </a>\n";
							if ($menu == "competitions") echo "<a href='$PHP_SELF?".$par."action=importer&imp=$invimp&Compet=$key'> Import </a>\n";
							if ($menu == "parametres") {if (!($protect)) echo "<a href='$PHP_SELF?".$par."action=importer&imp=$invimp&Sport=$key'> Import </a>\n";else echo "&nbsp;Import&nbsp;";}
						}
						
						if ( ($stat == 0) && (in_array("exporter",$Choix)) && (($menu == "etablissements") || ( ($menu == "competitions")  && ($sousmenu == "references") ) || ($menu == "parametres"))) {
							if (($exp == True) && ( ($EtabExport == $key) || ($Compet == $key) || ($Sport == $key) )) $invexp = False; else $invexp = True;
							if ($menu == "etablissements") echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&EtabExport=$key&exporttype=$exporttype&optionexport=expetab'>&nbsp;Export </a>\n";
							if ($menu == "competitions")   echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&Compet=$key&exporttype=$exporttype&optionexport=expcompet'>&nbsp;Export </a>\n";
							if ($menu == "parametres")     echo "<a href='$PHP_SELF?".$par."action=exporter&exp=$invexp&Sport=$key&exporttype=$exporttype&optionexport=expsport'>&nbsp;Export </a>\n";
						}
						
						if ( in_array("licence",$Choix) && ($licence == 1) ) {
						
							$req = bf_mysql_query("SELECT `ImpressionLic` FROM `Paramweb`"); 
							if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = $data["ImpressionLic"];} else $data = 0;
							$check = "";
							if (isset($_POST['licence'.$key])) $check = "checked" ;
							if ($seltous == 1) $check = "checked";
							if ($selaucun == 1) $check = "";
							
							echo "<INPUT type='hidden' name='licenceLigne".$i."' value='".$key."'>";
							if ( ($rowArray['LicInscrit'] == 1) && ( $data > 0) ) {
								if ( ($Adm) || ( (!($Adm)) && ($data == 1)) ) {
									echo "<p style ='margin:0px; text-align:left;'> &nbsp;";
									echo "<INPUT type='checkbox' name='licence".$key."' value='".$rowArray['LicNumLicence']."'".$check."> ".strtoupper($rowArray['LicNom'])." ".strtoupper(substr($rowArray['LicPrénom'],0,1)).".";
									if ($Adm) echo " &nbsp;-&nbsp; <a id='$i' href='$PHP_SELF?".$par."action=modifiedata&modif=$key#".($i-10)."'>&nbsp;Modifier&nbsp;</a>"; 
									echo "</p>\n";
								}
							} else {
								if ( (!($rowArray['LicInscrit'])) && (!(is_null($rowArray['LicDateDem']))) && (is_null($rowArray['LicDateValid'])) ) echo "<FONT color='red'><BLINK> Demande de licence en cours </BLINK></FONT><BR>";
								
								if ( (!($Adm)) && ($rowArray['LicAss']) ) echo "&nbsp;Modifier&nbsp"; else echo "<a id='$i' href='$PHP_SELF?".$par."action=modifiedata&modif=$key#".($i-10)."'>&nbsp;Modifier&nbsp;</a>"; 
								
								if ( (!($rowArray['LicInscrit'])) && (!(is_null($rowArray['LicDateDem']))) && (is_null($rowArray['LicDateValid'])) ) {
									echo "<a id='$i' href='$PHP_SELF?".$par."action=confirmedeletedata&suppr=$key&aj=&supprtout=#".($i-10)."'>&nbsp;Supprimer&nbsp;</a>";
									if ($Adm) echo "<a id='$i' href='$PHP_SELF?".$par."action=modifiedata&modif=$key&valid=$key#".($i-10)."'>&nbsp;Valider&nbsp;</a>";
								}
								if ( ($menu == "competitions") && (!($rowArray['LicInscrit'])) && ((is_null($rowArray['LicDateDem']))) && (is_null($rowArray['LicDateValid'])) ) {
									if ($Adm) echo "<a id='$i' href='$PHP_SELF?".$par."action=confirmedeletedata&suppr=$key&aj=&supprtout=#".($i-10)."'>&nbsp;Supprimer&nbsp;</a>";
								}
							}
						}
						
						if ( in_array("selectionner",$Choix) ) {
							if ( (isset($Compet)) && (!(empty($Compet))) && ($Compet > 0) && ($selectionner == true) )
								if ($sousmenu == "individuels")	echo "<a href='$PHP_SELF?".$par."action=VoirMenu&menu=competitions&sousmenu=individuels&aj=1&ParLicCode=".$rowArray['LicNumLicence']."&BValidernumlicence=Ok&affcompet=$affcompet&filter=&filtre1=&fi=0'>&nbsp;Sélectionner&nbsp;</a>";
								
						}
												
						if (($ChampsRacParam != "") && (!($Consult)) ) {
							for ($b = 0; $b < count($ChampsRacParam); $b++) {
								if ($ChampsRacParam[$b][0] == 1) echo "<a href='$PHP_SELF?".$par."action=VoirMenu&menu=".$ChampsRacParam[$b][1]."&sousmenu=".$ChampsRacParam[$b][2]."&filtre1=&fi=1&orderby=&racnom=".$ChampsRacParam[$b][3]."&racval=".$rowArray[$ChampsRacParam[$b][4]]."'>&nbsp;".$ChampsRacParam[$b][5]."&nbsp;</a>";
								if ($ChampsRacParam[$b][0] == 2) echo "<a href='$PHP_SELF?".$par."action=VoirMenu&menu=".$ChampsRacParam[$b][1]."&sousmenu=".$ChampsRacParam[$b][2]."&".$ChampsRacParam[$b][3]."=".$rowArray[$ChampsRacParam[$b][4]]."&filtre1=&fi=&orderby=&racnom=&racval=&orderby='>&nbsp;".$ChampsRacParam[$b][5]."&nbsp;</a>";
							}
						}
					}
				}
			}
		}

		if (($i >= 0 ) || (($i == -2) && in_array("filtrage",$Choix) && ($fi==true)) || (($i == -1) && in_array("ajout",$Choix) && ($aj==true))) echo "</tr>\n";
		
		if (($menu == "competitions") && ($sousmenu == "individuels") && ($nbLignesEpr > 1) && (!(strstr($MaReqErr, "bouton") == True))) {
			if  (($i == -1) && ($aj == true) && (isset($_POST["ParLicCode"]))) {
				for( $l = 0; $l < $nbLignesEpr; $l++ ) {
					echo "<TR class='tredit'>";
					for( $j = 0; $j < ($col-1); $j++ ) {
						if ($ChampsAff[$j] == true) {
							echo "<TD style='padding:2px;'>\n";
							$field = mysql_fetch_field( $pResult, $j );
							$check = ""; $valeur = "";
							if ($j == 2) {
								$rowz = mysql_fetch_array($reqeprinitBIS);
								$reqeprz = bf_mysql_query("SELECT * FROM Participations WHERE ParEprCode = ".$rowz[0]." AND ParCompetCode = ".$Compet." AND ParLicCode = ".$_POST["ParLicCode"]);
								$rowzz = mysql_fetch_array($reqeprz);
							}
							if ($field->name == "EprLibelCourt") {
								if (!(!($rowzz))) $check = "checked"; else if (isset($_POST['EprLibelCourt'.$rowz[0]])) $check = "checked";
								echo "<p style ='margin: 0px; white-space:nowrap;'>";
								echo "<INPUT type='hidden' name='Lic".$l."' value='".$_POST["ParLicCode"]."'> ";
								echo "<INPUT type='hidden' name='Par".$l."' value='".$rowzz['ParCode']."'> ";
								echo "<INPUT type='hidden' name='Epr".$l."' value='".$rowz[0]."'> ";
								echo "<INPUT type='hidden' name='Lib".$l."' value='".$rowz[1]."'> ";
								echo "<INPUT type='checkbox' name='EprLibelCourt".$rowz[0]."' value='".$rowz[0]."'".$check."> ";
								if ($check == "checked") echo "<B><I>";
								echo $rowz[1];
								if ($check == "checked") echo "</B></I>";
								echo "</p>\n";
							}
							if ($field->name == "ParQuadra") {
								if (!(!($rowzz))) if ($rowzz['ParQuadra']) $check = "checked";else if (isset($_POST['ParQuadra'.$rowz[0]])) $check = "checked";
								echo "<p style ='margin: 0px; text-align:center;'>";
								echo "<INPUT type='checkbox' name='ParQuadra".$rowz[0]."' value='".$rowz[0]."'".$check.">";
								echo "</p>\n";
							}
							if ($field->name == "EquNum") {
								if (!(!($rowzz))) if ($rowzz['ParEquCode'] > 0) $valeur = $rowzz['ParEquCode'];else $valeur = $_POST['ParEquCode'.$rowz[0]];
								echo "<p style ='margin: 0px;'>";
								listederoulante($field->name.$rowz[0]," ",$ChampsInsert[$j][3][2],$ChampsInsert[$j][3][3],$ChampsInsert[$j][3][4],$ChampsInsert[$j][3][5], $valeur, $ChampsInsert[$j][3][8]);
								echo "</p>\n";
							}
							if ($field->name == "ParPerfQualif") {
								if (!(!($rowzz))) if ($rowzz['ParPerfQualif'] > 0) $valeur = $rowzz['ParPerfQualif'];else $valeur = $_POST['ParPerfQualif'.$rowz[0]];
								echo "<p style ='margin: 0px;'>";
								echo "<INPUT type='text' name='ParPerfQualif".$rowz[0]."' value='".$valeur."' style='width : 75px; border-width : 0px; text-align : right;'>";
								echo "</p>\n";
							}
							for ( $k = 1; $k < 6; $k++ ) {
								if ($field->name == "ParObs".$k) {
									$valeur = "";
									if (!(!($rowzz))) $valeur = $rowzz['ParObs'.$k];else $valeur = $_POST['ParObs'.$k.$rowz[0]];
									echo "<p style ='margin: 0px;'>";
									if ((($ChampsInsert[$j][0]=="ListeD") || ($ChampsInsert[$j][0]=="ListeS")) && ($ChampsInsert[$j][5] == true)) {
										listederoulante($field->name.$rowz[0],$ChampsInsert[$j][3][1],$ChampsInsert[$j][3][2],$ChampsInsert[$j][3][3],$ChampsInsert[$j][3][4],$ChampsInsert[$j][3][5], $valeur, $ChampsInsert[$j][3][8]);
									} else {
										echo "<INPUT type='text' name='ParObs".$k.$rowz[0]."' value='".$valeur."' style='border-width : 0px; text-align:center;'>";
									}
									echo "</p>\n";
								}
							}
							echo "</TD>\n";
						}
					}
					echo "<TD></TD>";
					echo "</TR>";
				}
			}	
		}
		
		}
	
	}
	
	if( $row == 0 ) {
		echo "<TR><TD Align='center' COLSPAN='". (count(array_keys($ChampsAff, true)) + 1) ."'> - La liste est vide - </TD></TR>";
	}	
		
	echo "</table>\n";
	
	echo "</form>\n";
	
	echo $selecteur;

	}
	
}

Function VoirMenu() {
	Global $Adm, $ValideLignesParPage, $ValideSelecteurEtab, $LignesParPage, $menu, $sousmenu, $action, $PHP_SELF, $tablename, $Compet, $Etab, $Lic, $Filtre, $Inscrire, $FiltreSuppr, $orderby, $Nav, $where, $affcompet, $optionexport, $optionsuppr, $aj, $modif, $ListeSport, $ListeSportImport, $plusunan, $moinsunan, $majcat, $importcat, $Bimportcat, $VERSION;
	Global $suppr, $supprtout, $MaKey, $filter, $page, $filtre1, $fi, $TAILLE, $ADMINLOGIN, $Couleurs; 
	Global $BDD, $optionmaintenance, $validemaintenance, $validemodifierbase, $listebases, $upload, $presupprimer, $optionsuppr, $preimporter, $fileName, $tmpName, $fileSize, $fileType;
	Global $accueil, $valideaccueil, $supprimer, $requete, $validerequete;
	Global $Sport;
	Global $basesexternes, $valideBasesExternes;
	Global $Consult;
	Global $BAjouter, $SelecteurEtab;
	Global $exp, $ListeSauvegardes, $fichier, $actionfichier;
	Global $TriFic, $QUOTA;
	Global $stat, $horscat, $ParLicCode, $ugselimp;
	Global $exporter, $clicbouton;
	Global $licence;
	Global $valid;
	Global $valideimpressionlic, $optionimpressionlic, $valideinscriptionlic, $optioninscriptionlic, $optionimpressionlicAss; 
	Global $selectionner;
	Global $LICENCES, $REQUETES, $ADRSITE;
	
	PurgeTables();
		
	$montableau = array(
		"menu" => $menu, "sousmenu" => $sousmenu,"action" => $action,
		"tablename" => $tablename,
		"MaKey" => $MaKey, "orderby" => $orderby, "filter" => $filter,
		"ListeSport" => $ListeSport,
		"modif" => $modif, "suppr" => $suppr,"supprtout" => $supprtout,
		"fi" => $fi, "aj" => $aj,
		"affcompet" => $affcompet,
		"page" => $page,"filtre1" => $filtre1,
		"TriFic" => $TriFic,
		"stat" => $stat,
		"horscat" => $horscat,
		"licence" => $licence
	);
	
	if ((isset($BAjouter)) && ($menu == "competitions") && ($sousmenu == "references")) $Compet = TrouveMax("SELECT MAX(CompetCode) AS Max FROM Compétitions") + 1;
	if (isset($Compet)) $montableau["Compet"] = $Compet;
	if (!(isset($stat))) $stat = 0;
	if (!(isset($horscat))) $horscat = 0;	
	if ($Consult) $licence = 0;
	
	$par = EcritParam(serialize($montableau));
	
	if (isset($ValideSelecteurEtab)) $Etab = $SelecteurEtab;
		
	if (!(isset($menu))) {if (($Adm) && (date("Ymd") < 20111101)) $menu = "apropos"; else $menu = "competitions";} 
	
	if (!(isset($sousmenu))) {
		if ($menu == "competitions") {if ($Adm) $sousmenu = "references"; else $sousmenu = "individuels";}
		if ($menu == "parametres") $sousmenu = "sports";
	}
	
	if (($menu == "competitions") && (!(in_array($sousmenu, array("references","individuels","individuels(2)","equipes","licences"))))) if ($Adm) $sousmenu = "references"; else $sousmenu = "individuels";
	if (($menu == "parametres") && (!(in_array($sousmenu, array("sports","categories","epreuves"))))) $sousmenu = "sports";
	
	if ((!(isset($aj))) && (!(($menu == "competitions") && (($sousmenu == "individuels") || ($sousmenu == "equipes")) && ($Adm)))) $aj = false;
	if (!(isset($modif)))$modif = false;
	if (!(isset($fi)))   $fi    = false;
	$Where = "";
	
	if ($Adm) $s = "s"; else $s="";
	echo "<TABLE CLASS = 'tablemenu'>\n<TR>\n<TD>\n";
    if ($Adm) {echo  "<a "; if ($menu =="parametres") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=$sousmenu&Compet=$Compet'> &nbsp;Paramètres&nbsp; </a>\n";}  
	if (!($Consult)) { if ($Adm) echo "|"; echo "<a "; if ($menu =="etablissements") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=etablissements&sousmenu=$sousmenu&Compet=$Compet' > &nbsp;Etablissement$s&nbsp; </a>\n";}
    if (!($Consult)) {echo "|<a "; if ($menu =="licencies") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=licencies&sousmenu=$sousmenu&Compet=$Compet&licence=0'> &nbsp;Licenciés&nbsp; </a>\n";} 
    if (!($Consult)) echo "|"; echo "<a "; if ($menu =="competitions") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab&licence=0'> &nbsp;Compétitions&nbsp; </a>\n";
	echo "|<a "; if ($menu =="options") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=options&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab'> &nbsp;Options&nbsp; </a>\n";
	if ($Adm) {echo "|<a "; if ($menu =="outils") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=outils&sousmenu=$sousmenu&Compet=$Compet'> &nbsp;Outils&nbsp; </a>\n";}
	if ($Adm) {echo "|<a "; if ($menu =="connexions") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=connexions&sousmenu=$sousmenu&Compet=$Compet'> &nbsp;En ligne&nbsp; </a>\n";}
	echo "|<a "; if ($menu =="apropos") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=apropos&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab'> &nbsp;A propos&nbsp; </a>\n";
	if (!($Consult)) {echo "|<a href='$PHP_SELF?action=logout'> &nbsp;Déconnexion&nbsp; </a>\n";}
	
	echo "</TD>\n<TD align = 'right'>";
	echo "<a href='$PHP_SELF?".$par."action=VoirMenu&modiftaille=".($TAILLE - 1)."&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab' CLASS = 'tailleur'>&nbsp;-&nbsp;</a>";
	echo "<a href='$PHP_SELF?".$par."action=VoirMenu&modiftaille=3&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab'                 CLASS = 'tailleur'> Taille </a>";
	echo "<a href='$PHP_SELF?".$par."action=VoirMenu&modiftaille=".($TAILLE + 1)."&sousmenu=$sousmenu&Compet=$Compet&Etab=$Etab' CLASS = 'tailleur'>&nbsp;+&nbsp;</a>&nbsp";
	echo "</TD>\n</TR>\n</TABLE>\n";
	
	echo "</DIV>";
	echo "<DIV id = 'contenu'>";
	
	echo "<SPAN CLASS='filmenu'> &nbsp;&nbsp;&nbsp;".date("d/m/y  H:i:s ")."&nbsp;&nbsp;(".$menu;
	if ( ($menu == "parametres") || ($menu == "competitions") ) echo " / ".$sousmenu;
	echo ") </SPAN>\n";
	
	if (($menu == "parametres")  && ($Adm)){
		echo "<TABLE CLASS = 'tablesousmenu'><TR><TD>";
		echo" &nbsp;";
		echo "<a "; if ($sousmenu == "sports") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=sports&Compet=$Compet&ListeSport=$ListeSport'  > &nbsp; Sports  &nbsp; </a>"; echo"|";
		echo "<a "; if ($sousmenu == "categories") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=categories&Compet=$Compet&ListeSport=$ListeSport'  > &nbsp; Catégories &nbsp; </a>"; echo"|";
		echo "<a "; if ($sousmenu == "epreuves") echo "CLASS = 'inv'"; echo "href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=epreuves&Compet=$Compet&ListeSport=$ListeSport'  > &nbsp; Epreuves &nbsp; </a>"; 
		echo "</TD></TR></TABLE>";
		
		if ($sousmenu == "sports") {		
		$tablename     = "Sports";
		$queryStr      = "SELECT `SpoCode`,`SpoLibelCourt`, `SpoLibellé`, `Ordre`, `SpoGestionPerf` 
						  FROM `Sports`"; 
		if(!isset($orderby)) $orderby = "Ordre";
		$MaKey         = "SpoCode";
		$NomsColonnes  = array('Code','Code','Libellé','Ordre','Gestion');
		$ChampsTri     = array('/', '/', '/', '/','/');
		$ChampsAli     = array('center','center','','center','center');
		$ChampsFor     = array('','','','','');
		$ChampsAff     = array(false,true,true,false,false);
		$Choix 		   = array("importer","exporter","ajout","modifier","supprimer","monter","descendre");
		$ChampsType    = array("Texte","Texte","Texte","Texte","Texte");
		$ChampsTypeExt = array("","","","","");
		$ChampsFiltre  = array(true,true,true,true,true);
		$ChampsNomFil  = array("","","","","");
		$ChampsValide  = array('','','','','');
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,false ,"","",true),
						   array("Texte" ,true ,"","",true)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,true ,"","",Array("Max","SpoCode"),true,false,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","", Array("Max","Ordre"), true,false,false),
						   array("Texte" ,true ,"","","-99",true,true,false)
						   );
		$ChampsRacFiltre = array(false,false,false,false,false);		
		$ChampsRacParam  = array(array(2,'parametres','categories','ListeSport',0,'->'));
		$sousqueryStr    = "";
		$messagedel      = "Attention ! la suppression d'un sport entraîne la suppression des catégories, des épreuves et des compétitions dans ce sport.";
		$MajChpOrdre     = array(array("Catégories","CatSpoCode"),array("Epreuves","EprSpoCode"));
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr, $messagedel, $MajChpOrdre);
		}
		
		if (($sousmenu == "categories")) {		
		if (!(isset($ListeSport)) || empty($ListeSport) )  {
			$req = bf_mysql_query("SELECT * FROM Sports ORDER BY Ordre"); 
			if (!(!$req)) {
				$res = mysql_fetch_array($req);
				if (!(!$res)) $ListeSport = $res["SpoCode"];
			}
		}
		$req = bf_mysql_query("SELECT * FROM Sports WHERE SpoCode = $ListeSport"); 
		if (!(!$req)) {
			echo "<SPAN CLASS='pasimprimer'>";
			$res = mysql_fetch_array($req);
			if ($res) $SpoGestion = $res["SpoGestionPerf"];
			echo "<form action='$PHP_SELF' method=post>\n";
			echo "<TABLE>";
			echo "<TR>";
			echo "<TD>";
			echo "&nbsp;Sport &nbsp;";
			listederoulante("ListeSport", "", "SELECT SpoCode, SpoLibelCourt, SpoLibellé FROM Sports ORDER BY Ordre ", array("SpoLibelCourt","-","SpoLibellé"), array("","",""), "SpoCode" ,$ListeSport, 350);
			echo "&nbsp;";
			echo "<input type='submit' name='action' value='Ok' class ='bouton'>";
			echo "</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD>";
			if (($majcat != true) && ($importcat != true)) {
				echo "<a href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=categories&majcat=true&importcat=&ListeSport=$ListeSport'>&nbsp;Mettre à jour les années des catégories de tous les sports&nbsp;</a>";
			} 
			if ($majcat == true) {
				echo "Mettre à jour les catégories de tous les sports ";
				echo "<input type='submit' name='moinsunan' value='-1 an' class ='bouton'>";
				echo "&nbsp;";
				echo "<input type='submit' name='plusunan'  value='+1 an' class ='bouton'>";
				echo "&nbsp;";
				echo "<a href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=categories&majcat=&importcat=&ListeSport=$ListeSport'>&nbsp;Terminer&nbsp;</a>";
			}
			if ( ($SpoGestion == -99) && ($importcat != true) && ($majcat != true)) {
				echo " - <a href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=categories&majcat=&importcat=true&ListeSport=$ListeSport'>&nbsp;Importer les catégories&nbsp;</a>";
			}
			if ($importcat == true) {
				echo "Importer les catégories du sport ";
				listederoulante("ListeSportImport", "", "SELECT SpoCode, SpoLibelCourt, SpoLibellé FROM Sports WHERE SpoCode <> $ListeSport ORDER BY Ordre ", array("SpoLibelCourt","-","SpoLibellé"), array("","",""), "SpoCode" ,$ListeSportImport, 300);
				echo "&nbsp;";
				echo "<input type='submit' name='Bimportcat' value='Ok' class ='bouton'>"; 
				echo "&nbsp;";
				echo "<a href='$PHP_SELF?action=VoirMenu&menu=parametres&sousmenu=categories&majcat=&importcat=&ListeSport=$ListeSport'>&nbsp;Terminer&nbsp;</a>";
			}
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			ConstruitZone(array(array("menu",$menu),array("sousmenu",$sousmenu),array("action",$action)));
			ConstruitZone(array(array("Compet",$Compet),));
			ConstruitZone(array(array("affcompet",$affcompet)));				
			ConstruitZone(array(array("majcat",$majcat)));				
			echo "</FORM>";
			echo "</SPAN>";	
			
			if (isset($plusunan)) {
				bf_mysql_query("UPDATE Catégories SET CatDateDéb = DATE_ADD( CatDateDéb, INTERVAL 1 YEAR ), CatDateFin = DATE_ADD( CatDateFin, INTERVAL 1 YEAR ) WHERE YEAR( CatDateDéb ) <> 1970",0,"`Catégories`"); 
				bf_mysql_query("UPDATE Catégories SET CatDateFin = DATE_ADD( CatDateFin, INTERVAL 1 YEAR ) WHERE YEAR( CatDateDéb ) = 1970",0,"`Catégories`"); 
				unset($plusunan);
				bf_mysql_query("UNLOCK TABLES");
			}
			if (isset($moinsunan)) {
				bf_mysql_query("UPDATE Catégories SET CatDateDéb = DATE_ADD( CatDateDéb, INTERVAL -1 YEAR ), CatDateFin = DATE_ADD( CatDateFin, INTERVAL -1 YEAR ) WHERE YEAR( CatDateDéb ) <> 1970",0,"`Catégories`");
				bf_mysql_query("UPDATE Catégories SET CatDateFin = DATE_ADD( CatDateFin, INTERVAL -1 YEAR ) WHERE YEAR( CatDateDéb ) = 1970",0,"`Catégories`");
				unset($moinsunan);
				bf_mysql_query("UNLOCK TABLES");
			}
			
			if (isset($Bimportcat)) {
				$reqImport = bf_mysql_query("SELECT Catégories.* FROM `Catégories` WHERE Catégories.CatSpoCode = $ListeSportImport");
				if (!(!($reqImport))) {
					while ($resImport = mysql_fetch_array($reqImport)) {
						Maj(1,"Catégories", $resImport, Array("CatSpoCode" => "SELECT SpoCode FROM Sports WHERE SpoCode = $ListeSport"));
					}
				}
				unset($Bimportcat);
				$Res = bf_mysql_query("SELECT * FROM Sports INNER JOIN Catégories ON Sports.SpoCode = Catégories.CatSpoCode ORDER BY Sports.Ordre, Catégories.Ordre");
				$cpte = 0;
				while ($res = mysql_fetch_array($Res)) {
					$cpte = $cpte + 1;
					bf_mysql_query("UPDATE Catégories SET Ordre = $cpte WHERE CatCode = ".$res['CatCode'],0,"`Catégories`");
				}
				bf_mysql_query("UNLOCK TABLES");
			}
		} else $SpoGestion = -99;
		
		$tablename     = "Catégories";
		$queryStr      = "SELECT `CatCode`, `SpoLibelCourt`, `CatLibelCourt`, `CatLibellé`, `CatDateDéb`, `CatDateFin`, `CatSexCode`, `CatSpoCode`, `CatPrim`, Catégories.`Ordre`, `SpoGestionPerf` 
						  FROM `Catégories` INNER JOIN `Sports` ON Catégories.CatSpoCode = Sports.SpoCode"; 
		if (!(!$req)) $where = "(CatSpoCode = $ListeSport)";
		if(!isset($orderby)) $orderby = "Catégories.Ordre";
		$MaKey         = "CatCode";
		$NomsColonnes  = array('Code','Sport', 'Code', 'Libellé','Début','Fin', 'Sexe', 'Sport', 'Prim', 'Ordre', 'Gestion');
		$ChampsTri     = array('/', '/', '/', '/', '/', '/', '/', '/', '/', '/', '/');
		$ChampsAli     = array('center', 'center', 'center','','center','center','center','center','center','center','center');
		$ChampsFor     = array('','','','','','','','','','','');
		$ChampsAff     = array(false,true,true,true,true,true,true,false,false,false,false);
		if ($SpoGestion == -99 ) $Choix = array("ajout","modifier","supprimer","monter","descendre"); else $Choix = "";
		$ChampsType    = array("Texte","Texte","Texte","Texte","Texte","Texte","ListeS","Texte","Texte","Texte","Texte");
		$ChampsTypeExt = array("","","","","","",array("1"=>'G',"2"=>'F',"3"=>'M'),"","","","");
		$ChampsFiltre  = array(true,true,true,true,true,true,true,true,true,true,true);
		$ChampsNomFil  = array("","","", "","","","","","","","");
		$ChampsValide  = array('','','','','','','','','','','');
		$ChampsRacFiltre = array(false,false,false,false,false,false,false,false,false,false,false);
		$ChampsRacParam  = "";
		$sousqueryStr    = "";
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,false,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("ListeS",true ,"",array("LicSexCode", "Sexe..."       ,array('1'=>'G','2'=>'F')    , "", "", "", "", "", "35"),true),			  
						   array("Texte" ,false,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,true,"","",Array("Max","CatCode"),true,true,false),
						   array("Texte" ,false,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("ListeS",true ,"",array("LicSexCode", "Sexe..."       ,array('1'=>'G','2'=>'F')    , "", "", "", "", "", "35"),"",true,true,false),
						   array("Texte" ,true ,"","",$ListeSport,true,true,false),
						   array("Texte" ,true ,"","",'1',true,true,false),						   
						   array("Texte" ,true ,"","", Array("Max","Ordre"), true,true,false),
						   array("Texte" ,false ,"","","",true,true,false)						   
						   );
	    $messagedel      = "Attention ! la suppression d'une catégorie entraîne la suppression des épreuves et des participations dans cette catégorie.";
		$MajChpOrdre     = array(array("Catégories","CatSpoCode"),array("Epreuves","EprSpoCode"));
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr, $messagedel, $MajChpOrdre);
		}
	
		if (($sousmenu == "epreuves")) {		
		
		if (!(isset($ListeSport)) || empty($ListeSport))  {
			$req = bf_mysql_query("SELECT * FROM Sports WHERE SpoGestionPerf <= 0 ORDER BY Ordre"); 
			if (!(!$req)) {
				$res = mysql_fetch_array($req);
				if (!(!$res)) $ListeSport = $res["SpoCode"];
			}
		}

		$req = bf_mysql_query("SELECT * FROM Sports WHERE SpoCode = $ListeSport"); 
		if (!(!$req)) {
			echo "<SPAN CLASS='pasimprimer'>";
			echo "<form action='$PHP_SELF' method=post>\n";
			echo "<TABLE>";
			echo "<TR>";
			echo "<TD>";
			$res = mysql_fetch_array($req);
			if ($res) $SpoGestion = $res["SpoGestionPerf"];
			if ($SpoGestion > 0) $ListeSport = 2; 
			echo "&nbsp;Sport &nbsp;";
			listederoulante("ListeSport", "", "SELECT SpoCode, SpoLibelCourt, SpoLibellé FROM Sports WHERE SpoGestionPerf <= 0 ORDER BY Ordre", array("SpoLibelCourt","-","SpoLibellé"), array("","",""), "SpoCode" ,$ListeSport, 350);
			echo "&nbsp;";
			echo "<input type='submit' name='action' value='Ok' class ='bouton'>";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			ConstruitZone(array(array("menu",$menu),array("sousmenu",$sousmenu),array("action",$action)));
			ConstruitZone(array(array("Compet",$Compet),));
			ConstruitZone(array(array("affcompet",$affcompet)));				
			echo "</FORM>";
			echo "</SPAN>";
		} else $SpoGestion = -99;
		
		$tablename     = "Epreuves";
		$queryStr      = "SELECT `EprCode`, `SpoLibelCourt`, `EprLibelCourt`, `EprLibellé`, `CatLibelCourt`, `EprSpoCode`, Epreuves.`Ordre`, `SpoGestionPerf`
						  FROM (Sports INNER JOIN Epreuves ON Sports.SpoCode = Epreuves.EprSpoCode) INNER JOIN Catégories ON Epreuves.EprCatCode = Catégories.CatCode" ; 
		if (!(!$req)) $where = "(EprSpoCode = $ListeSport)";
		if(!isset($orderby)) $orderby = "Epreuves.Ordre";
		$MaKey         = "EprCode";
		$NomsColonnes  = array('Code','Sport','Code','Libellé','Cat', 'Sport', 'Ordre', 'Gestion');
		$ChampsTri     = array('/', '/', '/', '/', '/', '/', '/', '/');
		$ChampsAli     = array('center','center', 'center','','center','center','center','center');
		$ChampsFor     = array('','','','','','','','');
		$ChampsAff     = array(false,true,true,true,true,false,false,false);
		if ($SpoGestion == -99 ) $Choix = array("ajout","modifier","supprimer","monter","descendre"); else $Choix = "";
		$ChampsType    = array("Texte","Texte","Texte","Texte","ListeD","Texte","Texte","Texte");
		$ChampsTypeExt = array("","","","","","","","");
		$ChampsFiltre  = array(true,true,true,true,true,true,true,true);
		$ChampsNomFil  = array("","","","","","","","");
		$ChampsValide  = array('','','','','','','','');
		$ChampsRacFiltre = array(false,false,false,false,false,false,false,false);
		$ChampsRacParam  = "";
		$sousqueryStr    = "";
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,false,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("ListeD",true ,"EprCatCode",array("selcat", "Cat...","SELECT CatLibelCourt, CatLibellé FROM Catégories WHERE CatSpoCode = $ListeSport ORDER BY Ordre", Array("CatLibelCourt", "-", "CatLibellé"), Array("", "", ""), "CatLibelCourt","","SELECT CatCode FROM Catégories WHERE CatSpoCode = $ListeSport AND CatLibelCourt = ","150"),true),
						   array("Texte" ,false,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,false,"","",true)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,true,"","",Array("Max","EprCode"),true,true,false),
						   array("Texte" ,false,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("ListeD",true,"EprCatCode",array("selcat", "Cat...","SELECT CatLibelCourt, CatLibellé FROM Catégories WHERE CatSpoCode = $ListeSport ORDER BY Ordre", Array("CatLibelCourt", "-", "CatLibellé"), Array("", "", ""), "CatLibelCourt","","SELECT CatCode FROM Catégories WHERE CatSpoCode = $ListeSport AND CatLibelCourt = ","150"),"", true,true,false),
						   array("Texte" ,true ,"","",$ListeSport,true,true,false),
						   array("Texte" ,true ,"","", Array("Max","Ordre"), true,true,false),
						   array("Texte" ,false,"","","",true,true,false)
						   );
	     $messagedel      = "Attention ! la suppression d'une épreuve entraîne la suppression des participations dans cette épreuve.";
		 $MajChpOrdre     = array(array("Epreuves","EprSpoCode"));
		 GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr, $messagedel, $MajChpOrdre);
		}
	}
	
	if ($menu == "etablissements"){
		If ($stat == 0) {

		bf_mysql_query("UPDATE Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode SET EtabMemo3 = IF(RAND() > 0.33, IF(RAND() > 0.66, CONCAT(SecLibel, LOWER(EtabNomCourt), FLOOR(RAND()*100)), CONCAT(LOWER(EtabNomCourt), SecLibel, FLOOR(RAND()*100))), CONCAT(FLOOR(RAND()*100), LOWER(EtabNomCourt), SecLibel)) WHERE EtabMemo3 = '' OR EtabMemo3 IS NULL");
		
		$tablename     = "Etablissements";
		$queryStr      = "SELECT `EtabCode`,`EtabNum`, `EtabNomCourt`, `EtabNom`, `EtabAS`, `EtabAdresse1`, `EtabAdresse2`, `EtabCP`, `EtabVille`, `EtabTél`, `EtabFax`, `EtabMail`, `EtabTélEps`, `EtabMemo3`, `SecLibel`,
						  (SELECT COUNT(LicEtabCode) FROM `Licenciés` where LicEtabCode = EtabCode) AS Lic
						  FROM `Etablissements` INNER JOIN `Secteurs` ON Etablissements.EtabSecCode = Secteurs.SecCode"; 
		if (!$Adm) $where = "(EtabNum = $Etab)".RetAS($Etab);
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "Secteurs.Ordre, EtabNomCourt";
		$MaKey         = "EtabCode";
		$NomsColonnes  = array('Code','Numéro','Code','Nom','A.S.','Adresse1','Adresse2','CP','Ville','Tél','Fax','Mail','Tél Eps','Passe','Secteur','Nbre');
		$ChampsTri     = array('','Secteurs.Ordre, EtabNum', 'Secteurs.Ordre, EtabNomCourt', 'EtabNom','','','','EtabCP, Secteurs.Ordre, EtabNomCourt','EtabVille, Secteurs.Ordre, EtabNomCourt','','','','','','','');
		$ChampsAli     = array('','center','center','','','','','center','','center','center','','','center','center','center');
		$ChampsFor     = array('','%06d','','','','','','','','','','','','','','');
		$ChampsAff     = array(false,true,true,true,true,true,((($aj) || ($modif)) && ($Adm)),true,true,true,true,false,false,$Adm,((($aj) || ($modif)) && ($Adm)),true);
		if ($Adm) $Choix = array("importer","exporter","ajout","modifier","filtrage","supprimer","stat");else $Choix = "";
		$ChampsType    = array("Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte");
		$ChampsTypeExt = array("","","","","","","","","","","","","","","","");
		$ChampsFiltre  = array(true,true,true,true,true,true,true,true,true,true,true,true,true,true,true,false);
		$ChampsNomFil  = array("","","","","","","","","","","","","","","","");
		$ChampsRacFiltre = array(false,false,false,false,false,false,false,false,false,false,false,false,false,false,true,false);
		if ($Adm) $ChampsRacParam = array(array(1,'licencies','','EtabNomCourt',2,'->')); else $ChampsRacParam = "";
		$sousqueryStr    = "";
		$ChampsValide  = array('','','','','','','','','','','','','','','','','','','','');
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,True ,"","",false),
						   array("Texte" ,$Adm ,"","",false),
						   array("Texte" ,$Adm ,"","",false),
						   array("ListeD",$Adm,"EtabSecCode",array("selsect", "Secteur...","SELECT SecLibel, SecLibellé FROM Secteurs WHERE SecLibel <> '0' ORDER BY Ordre", Array("SecLibel", "SecLibellé"), Array("", ""), "SecLibel","","SELECT SecCode FROM Secteurs WHERE SecLibel = ","75"),true),
						   array("Texte" ,False ,"","",false)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,false,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,true ,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("ListeD",true,"EtabSecCode",array("selsect", "Secteur...","SELECT SecLibel, SecLibellé FROM Secteurs WHERE SecLibel <> '0' ORDER BY Ordre", Array("SecLibel", "SecLibellé"), Array("", ""), "SecLibel","","SELECT SecCode FROM Secteurs WHERE SecLibel = ","75"),"",true,true,false),
						   array("Texte" ,false,"","","",false,false,false),
						   );
		$messagedel      = "Attention ! la suppression d'un établissement entraîne la suppression des licenciés et des participations de cet établissement.";
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam,$sousqueryStr,$messagedel );
	} else {
		ConstruitStat(-2, 1, $queryStr, $NomsColonnes, $ChampsAli, $ChampsFor, $ChampsAff, $ChampsType, $Choix);
		GereData("", $queryStr, "", $NomsColonnes, "", $ChampsAli, $ChampsFor, $ChampsAff, $action, "", $Choix, "", "", $ChampsType, "", "", "", "", "", "", "", "", "", 1);	
	}
	}
	
	if ($menu == "licencies"){
		
		if ($stat == 0) {
		
		$tablename     = "Licenciés";
		if ($horscat == 0) {
			
			if ($licence != 1)
				$queryStr      = "SELECT EtabNomCourt, Licenciés.LicCode, Licenciés.LicInscrit, Licenciés.LicNumLicence, Licenciés.LicNom, Licenciés.LicPrénom, Licenciés.LicNaissance, Licenciés.LicSexCode, CatLibelCourt, EtabNomCourt, EtabNom, EtabVille
							  FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode";
			else
				$queryStr      = "SELECT EtabNomCourt, Licenciés.LicCode, Licenciés.LicInscrit, Licenciés.LicNumLicence, Licenciés.LicNom, Licenciés.LicPrénom, Licenciés.LicNaissance, Licenciés.LicSexCode, CatLibelCourt, EtabNomCourt, EtabNom, EtabVille, LicAss, LicNomAss, LicDateAss, LicDateDem, LicDateValid
							  FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode";
		
			$where = "( CatSpoCode = 1 AND LicSexCode = CatSexCode AND (Licenciés.LicNaissance >= CatDateDéb And Licenciés.LicNaissance <= CatDateFin) ";
			if (!$Adm) $where .= " And (EtabNum = $Etab".RetAS($Etab)."))"; else $where.=")";
			if ($Adm) $Choix = array("importer","exporter","ajout","modifier","filtrage","supprimer","stat","selectionner"); else $Choix = array("exporter","filtrage","selectionner");
			if ( ( ((!($Adm)) && (TrouveParamweb("ImpressionLic") == 1)) || (($Adm) && (TrouveParamweb("ImpressionLic") > 0)) ) || (TrouveParamweb("InscriptionLic") > 0)) array_push($Choix,"licence");
		} else {
			$queryStr      = "SELECT EtabNomCourt, Licenciés.LicCode, Licenciés.LicInscrit, Licenciés.LicNumLicence, Licenciés.LicNom, Licenciés.LicPrénom, Licenciés.LicNaissance, Licenciés.LicSexCode, '' AS Cat, EtabNomCourt, EtabNom, EtabVille, LicAss, LicNomAss, LicDateAss, LicDateDem, LicDateValid
						      FROM Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode";
			$where = " ( LicNaissance < (SELECT CatDateDéb FROM Catégories WHERE CatSpoCode = 1 ORDER BY CatDateDéb LIMIT 1) OR LicNaissance > (SELECT CatDateFin FROM Catégories WHERE CatSpoCode = 1 ORDER BY CatDateFin DESC LIMIT 1)";
			if (!$Adm) $where .= " And EtabNum = $Etab)"; else $where.=")";
			if ($Adm) $Choix = array("modifier","filtrage","supprimer","liste"); else $Choix = array("filtrage");
		}
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "LicNom, LicPrénom";
		$MaKey         = "LicCode";
		$NomsColonnes  = array('Etab','Code','Inscrit','Numéro','Nom','Prénom','Naissance','Sexe','Cat','Etab','Etab Nom','Etab Ville','Ass','Ass Nom','Ass Date','Demande','Validation');
		$ChampsTri     = array('EtabNomCourt, LicNom, LicPrénom','', 'LicInscrit DESC, LicNom, LicPrénom', 'LicNumLicence', 'LicNom, LicPrénom', 'LicPrénom, LicNom', 'LicNaissance, LicNom, LicPrénom', 'LicSexCode DESC, LicNom, LicPrénom', 'LicSexCode DESC, LicNaissance DESC, LicNom, LicPrénom', 'EtabNomCourt, LicNom, LicPrénom', 'EtabNom, LicNom, LicPrénom','EtabVille, EtabNomCourt, LicNom, LicPrénom','LicAss Desc, LicNom, LicPrénom','LicNomAss, LicNom, LicPrénom', 'LicDateAss DESC, LicNom, LicPrénom','LicDateDem DESC, LicNom, LicPrénom','LicDateValid DESC, LicNom, LicPrénom');
		$ChampsAli     = array('center','','center','center','','','center','center','center','center','','','center','center','center','center','center');
		$ChampsFor     = array('','','','%010s','','','','','','','','','','','','','');
		$ChampsAff     = array(!$Adm,false,true,true,true,true,true,true,true,$Adm,$Adm && (!($licence)),$Adm && (!($licence)),$licence,false,$licence,$licence,$licence);
		$ChampsType    = array("Texte","Texte","ListeS","ListeD","Texte","Texte","Date","ListeS","Texte","Texte","Texte","Texte","ListeS","Texte","Date","Date","Date");
		$ChampsTypeExt = array("","",array("0"=>"Non","1"=>"Oui"),"","","","",array("1"=>'G',"2"=>'F'),"","","","",array("0"=>"Non","1"=>"Oui"),"","","","");
		$ChampsFiltre  = array(true,true,true,true,true,true,true,true,true,true,true,true,true,true,true,true,true);
		$ChampsNomFil  = array("","","","","","","","","","","","","","","","","");
		$ChampsRacFiltre = array(true,false,true,false,false,false,false,true,true,true,false,false,true,true,true,true,true);
		$ChampsRacParam  = "";
		$sousqueryStr    = "";
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,false,"","",true),
						   array("ListeS",$Adm ,"",array("LicInscrit", "Inscrit..."    ,array("0"=>"Non","1"=>"Oui"), "", "", "", "", "","45"),true),
						   array("Texte" ,$Adm,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Date"  ,$Adm ,"","",true),
						   array("ListeS",$Adm ,"",array("LicSexCode", "Sexe..."       ,array('1'=>'G','2'=>'F')    , "", "", "", "", "", "35"),true),
						   array("Texte" ,false,"","",true),
						   array("ListeD",$Adm,"LicEtabCode",array("seletab", "Etab...","SELECT EtabNum, EtabNomCourt, EtabNom, EtabVille FROM Etablissements ORDER BY EtabNum", Array("EtabNum", "EtabNomCourt", "EtabNom", "EtabVille"), Array("%06d", "", "", ""), "EtabNum","SELECT EtabNum FROM Etablissements WHERE EtabNomCourt = ","SELECT EtabCode FROM Etablissements WHERE EtabNum = ","200"),true),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("ListeS",$licence,"",array("LicAss", "Ass..."    ,array("0"=>"Non","1"=>"Oui"), "", "", "", "", "","45"),false),
						   array("Texte" ,$licence,"","",false),
						   array("Date"  ,$Adm && $licence,"","",false),
						   array("Date"  ,$Adm && $licence,"","",false),
						   array("Date"  ,$Adm && $licence,"","",false)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,true,false),
						   array("ListeS",true ,"",array("LicInscrit", "Inscrit..."    ,array('0'=>'Non','1'=>'Oui'), "", "", "", "","","45"),"",true,true,false),
						   array("Texte" ,$Adm ,""             ,"","",true,true,false),
						   array("Texte" ,true ,""             ,"","",true,true,false),
						   array("Texte" ,true ,""             ,"","",true,true,false),
						   array("Date"  ,true ,""             ,"","",true,true,false),
						   array("ListeS",true ,"",array("LicSexCode", "Sexe..."       ,array('1'=>'G','2'=>'F')    , "", "", "", "", "", "35"),"",true,true,false),
						   array("Texte" ,false,""             ,"","",true,true,false),
						   array("ListeD",true,"LicEtabCode",array("seletab", "Etab...","SELECT EtabNum, EtabNomCourt, EtabNom, EtabVille FROM Etablissements ORDER BY EtabNum", Array("EtabNum", "EtabNomCourt", "EtabNom", "EtabVille"), Array("%06d", "", "", ""), "EtabNum","SELECT EtabNum FROM Etablissements WHERE EtabNomCourt = ","SELECT EtabCode FROM Etablissements WHERE EtabNum = ","200"),"$Etab",$Adm,true,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("ListeS",true ,"",array("LicAss", "Ass..."            ,array('0'=>'Non','1'=>'Oui'), "", "", "", "", "","45"),"",true,true,false),
						   array("Texte" ,true ,""             ,"","",true,false,false),
						   array("Date"  ,true ,""             ,"","NULL",true,false,false),
						   array("Date"  ,true ,""             ,"","NULL",true,false,false),
						   array("Date"  ,true ,""             ,"","NULL",true,false,false)
						   );
		if ($licence == 1) $messagedel = ""; else $messagedel      = "Attention ! la suppression d'un licencié entraîne la suppression des participations de ce licencié.";
		if ( (!($licence == 1)) && (isset($filter)) ) {
			$tabfilter = explode(" AND ", $filter);
			$counttab = count($tabfilter);
			for ($i = 0; $i < $counttab; $i++) {if ( is_int(strpos($tabfilter[$i],"LicAss")) || is_int(strpos($tabfilter[$i],"LicNomAss")) || is_int(strpos($tabfilter[$i],"LicDateAss")) || is_int(strpos($tabfilter[$i],"LicDateDem")) || is_int(strpos($tabfilter[$i],"LicDateValid")) ) unset($tabfilter[$i]);}
			$filter = implode(" AND ", $tabfilter);
		}
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam,$sousqueryStr,$messagedel);
		
		} else {
			ConstruitStat(-2, 1, $queryStr, $NomsColonnes, $ChampsAli, $ChampsFor, $ChampsAff, $ChampsType, $Choix);
			GereData("", $queryStr, "", $NomsColonnes, "", $ChampsAli, $ChampsFor, $ChampsAff, $action, "", $Choix, "", "", $ChampsType, "", "", "", "", "", "", "", "", "", 1);
		}
	}
	
	if ($menu == "competitions") {
		
		if((!isset($Compet)) || (empty($Compet))) $Compet = 0;
		if((!isset($CompetEqu)) || (empty($CompetEqu))) $CompetEqu = 0;
		if(!isset($Lic)) $Lic = 0;	
		if ($Compet == 0) $Where = "WHERE CompetEtat = '1'" ; else $Where = "WHERE CompetEtat = '1' And CompetCode = $Compet ";
		if (($licence == 0) && ($sousmenu == "licences")) {if ($Adm) $sousmenu = "references"; else $sousmenu = "individuels";}
		if ($licence == 1) $sousmenu = "licences";
		if ( (!isset($affcompet)) || (empty($affcompet)) ) $affcompet = "oui";
		if ($affcompet == "oui") $affcompettexte = " &nbsp; Masquer les autres compétitions disponibles&nbsp;"; else $affcompettexte = " &nbsp; Afficher les autres compétitions disponibles &nbsp;";
		if ($sousmenu == "equipes") $WhereComp = "AND CompetEqu = 1"; else $WhereComp = "";
		if ($Adm) { if ($affcompet == "nonfermees") $WhereEtat = " CompetEtat = 1 "; else $WhereEtat = " CompetEtat >= 0 ";} else $WhereEtat = " CompetEtat = 1 ";
		$CompetStatut ="Inscriptions fermées";			
		$affgrillecompet = "non";
		$queryCompet = bf_mysql_query("SELECT CompetCode, CompetLibellé, DATE_FORMAT(CompetDateDéb,'%d/%m/%Y') AS CompetDateDébut, CompetLieu, CompetCalculAutoEqu, SpoLibellé, SpoCode, CompetEqu, CompetStatut, CompetEtat, CompetObs, Compétitions.Ordre FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode WHERE $WhereEtat $WhereComp ORDER BY Compétitions.Ordre, CompetDateDéb DESC");
		if (!(!($queryCompet))) {
			$res = mysql_fetch_array($queryCompet);
			if ($res) {
				$affgrillecompet = "oui";
				$req = bf_mysql_query("SELECT CompetCode, CompetLibellé, DATE_FORMAT(CompetDateDéb,'%d/%m/%Y') AS CompetDateDébut, CompetLieu, CompetCalculAutoEqu, SpoLibellé, SpoCode, CompetEqu, CompetStatut, CompetEtat, CompetObs, Compétitions.Ordre FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode WHERE CompetCode = $Compet AND $WhereEtat $WhereComp ORDER BY Compétitions.Ordre, CompetDateDéb DESC");
				$res1 = mysql_fetch_array($req);	
				if ($res1) $res = $res1;
				if (!( (isset($BAjouter)) && ($menu == "competitions") && ($sousmenu == "references") )) $Compet = $res["CompetCode"];
				if (mysql_num_rows($queryCompet) > 1) $monnum = "1 -"; else $monnum = "";
				$TexteCompet = "<B>".$monnum."<BLINK>&nbsp;".$res["SpoLibellé"]." ->&nbsp; </BLINK> ".$res["CompetLibellé"]." - ".$res["CompetDateDébut"]." - ".$res["CompetLieu"]
				."<span class='pasimprimer'>";
				if (!($Consult)) $TexteCompet.= " - ".$res["CompetStatut"];
				if (!($res["CompetObs"]) == "") $TexteCompet.= " - ".$res["CompetObs"];
				$TexteCompet.= "</span></B>";
				$Req = bf_mysql_query("SELECT CompetCode, CompetLibellé, DATE_FORMAT(CompetDateDéb,'%d/%m/%Y') AS CompetDateDébut, CompetLieu, SpoLibellé, SpoCode, CompetEqu, CompetStatut, CompetEtat, CompetObs, Compétitions.Ordre FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode WHERE CompetCode <> $Compet AND $WhereEtat $WhereComp ORDER BY Compétitions.Ordre, CompetDateDéb DESC");
				if (mysql_num_rows($Req) > 0) { 
					$TexteCompet .= "<span class='pasimprimer'><BR>&nbsp;&nbsp;&nbsp;<FONT Size=1> <a href=$PHP_SELF?action=VoirMenu&sousmenu=$sousmenu&affcompet=";
					if ($affcompet == "oui") $TexteCompet .="non"; else $TexteCompet .="oui";
					$TexteCompet .= "&Compet=$Compet&licence=$licence> $affcompettexte </a></FONT></span><HR CLASS = 'hr2'>";
					if ( ($Adm) && ($affcompet == "oui") ) {
						$TexteCompet .= "<span class='pasimprimer'>&nbsp;<FONT Size=1> <a href=$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=$sousmenu&affcompet=";
						if ($affcompet == "oui") $TexteCompet .="nonfermees"; else $TexteCompet .="oui";
						$TexteCompet .= "&Compet=$Compet&licence=$licence> &nbsp; Masquer les compétitions non visibles par les utilisateurs &nbsp; </a></FONT></span><HR CLASS = 'hr2'>";
					}
				} else $affcompet = "non";
				$Sport               = $res["SpoCode"];
				$CompetEqu			 = $res["CompetEqu"];
				$CompetCalculAutoEqu = $res["CompetCalculAutoEqu"];
				$CompetStatut        = $res["CompetStatut"];
			} else {
				$TexteCompet = "&nbsp; Aucune compétition disponible pour l'instant !";
				$affcompet = "non";
			}
		}
		
		$SpoGestion = TrouveSport($Compet, "SpoGestionPerf");
		$TxtSM = "";
		
		if (((!$Adm) && ($CompetEqu != 0)) || ($Adm) ) {
			$TxtSM.="<TABLE CLASS = 'tablesousmenu'><TR><TD>";
			$TxtSM.=" &nbsp;";
			if ($Adm) {$TxtSM.= "<a "; if ($sousmenu =="references") $TxtSM.= "CLASS = 'inv'"; $TxtSM.= "href='$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=references&Compet=$Compet&affcompet=$affcompet'  > &nbsp; Références  &nbsp; </a>"; $TxtSM.="|";}
			$TxtSM.= "<a "; if (($sousmenu == "individuels") || ($sousmenu =="individuels(2)")) $TxtSM.="CLASS = 'inv'"; $TxtSM.="href='$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=individuels&Compet=$Compet&affcompet=$affcompet&Etab=$Etab' > &nbsp; Individuels &nbsp; </a>"; 
			$montexteequipe = "";
			if (($SpoGestion ==  0) || ($SpoGestion == -2) || ($SpoGestion == -3) || ($SpoGestion == -4) || ($SpoGestion == -99)) $montexteequipe = "Equipes";
			if (($SpoGestion == -5)) $montexteequipe = "Relais";
			if (($SpoGestion == -1) || ($SpoGestion == -7)) $montexteequipe = "Relais - Equipes";
			if (($montexteequipe != "") && ($CompetEqu != 0) ){ $TxtSM.="|<a "; if ($sousmenu =="equipes") $TxtSM.="CLASS = 'inv'"; $TxtSM.="href='$PHP_SELF?action=VoirMenu&menu=competitions&sousmenu=equipes&Compet=$Compet&affcompet=$affcompet&Etab=$Etab'     > &nbsp; ".$montexteequipe." &nbsp; </a>";}
			$TxtSM.="</TD></TR></TABLE>";
		}
		
		if ($Consult) {
			if (isset($Etab)) $compEtab = "AND EtabNum = ".$Etab." "; else $compEtab = "";			
			$selectEt1 = "SELECT DISTINCT Etablissements.* FROM Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode
					     WHERE Participations.ParCompetCode = $Compet $compEtab
						 UNION SELECT Etablissements.* FROM Etablissements INNER JOIN Equipes ON Etablissements.EtabCode = Equipes.EquEtabCode
						 WHERE Equipes.EquCompetCode = $Compet $compEtab
						 ORDER BY EtabNum";
			$selectEt2 = "SELECT DISTINCT Etablissements.* FROM Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode
					     WHERE Participations.ParCompetCode = $Compet
						 UNION SELECT Etablissements.* FROM Etablissements INNER JOIN Equipes ON Etablissements.EtabCode = Equipes.EquEtabCode
						 WHERE Equipes.EquCompetCode = $Compet
						 ORDER BY EtabNum";
			$querySelEtab = bf_mysql_query($selectEt1);
			if ((!($querySelEtab)) || (mysql_num_rows($querySelEtab) == 0) ) $querySelEtab = bf_mysql_query($selectEt2);
			if (!(!($querySelEtab))) {
				$resSelEtab = mysql_fetch_array($querySelEtab);
				if ($resSelEtab) {
					$Etab = $resSelEtab['EtabNum'];
					$sousR = "(".$selectEt2.") AS TempEtab";
					$prem = mysql_fetch_array(bf_mysql_query("SELECT DISTINCT MIN(EtabNum) FROM ". $sousR ));$prem = $prem[0];
					$prec = mysql_fetch_array(bf_mysql_query("SELECT DISTINCT MAX(EtabNum) FROM ". $sousR ." WHERE EtabNum <".$Etab));$prec = $prec[0];
					$suiv = mysql_fetch_array(bf_mysql_query("SELECT DISTINCT MIN(EtabNum) FROM ". $sousR ." WHERE EtabNum >".$Etab));$suiv = $suiv[0];
					$dern = mysql_fetch_array(bf_mysql_query("SELECT DISTINCT MAX(EtabNum) FROM ". $sousR .""));$dern = $dern[0];
					echo "<span class='pasimprimer' ><BR><i>&nbsp;&nbsp;Choisissez un établissement dans la liste et cliquez sur <B>Sélectionner</B> :";
					echo "</i></SPAN>"; 
					echo "<form action='$PHP_SELF' method=post>\n";
					echo "<TABLE CLASS ='tableselecteurEtab'>";	
					echo "<TR><TD>";
					if( $Etab > $prem ) {
						echo "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&Consult=true&sousmenu=$sousmenu&Compet=$Compet&Etab=$prem'><< </a>\n";
						echo "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&Consult=true&sousmenu=$sousmenu&Compet=$Compet&Etab=$prec'>< </a>\n";
					} else echo "<span class='pasimprimer'> << < </span>\n";
					echo "&nbsp;&nbsp;";
					listederoulante("SelecteurEtab", "", $selectEt2, array("EtabNum","-","EtabNomCourt","-","EtabNom","-","EtabVille"), array("%06s","","","","","",""), "EtabNum", $Etab, 400);
					echo "&nbsp;&nbsp;<INPUT TYPE='submit' NAME='ValideSelecteurEtab' VALUE='Sélectionner' class='bouton'>&nbsp;&nbsp;";
					if( $Etab < $dern) {
						echo "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&Consult=true&sousmenu=$sousmenu&&Compet=$Compet&Etab=$suiv'>> </a>\n";
						echo "<a Class='navig' href='$PHP_SELF?".$par."action=VoirMenu&Consult=true&sousmenu=$sousmenu&&Compet=$Compet&Etab=$dern'>>> </a>\n";
					} else echo "<span class='pasimprimer' >> >> </span>\n";
					echo "</TD></TR>";
					echo "</TABLE></FORM>\n";
				}
			}	
		}
		
		if ($Adm) echo $TxtSM;
		
		if (($sousmenu == "references") && ($Adm)) {
		If ($stat == 0) {
		$tablename     = "Compétitions";
		$queryStr      = "SELECT CompetCode, CompetLibellé, CompetDateDéb, CompetLieu, SpoLibelCourt, CompetEqu, CompetCalculAutoEqu, CompetChpSup, CompetEtat, CompetStatut, CompetObs,
						  (SELECT COUNT(ParCode) FROM `Participations` where ParCompetCode = CompetCode) AS Ind, 
						  (SELECT COUNT(EquCode) FROM `Equipes` where EquCompetCode = CompetCode) AS Equ, 
						  Compétitions.Ordre
						  FROM Sports INNER JOIN Compétitions ON Sports.SpoCode = Compétitions.CompetSpoCode";
		$where		   = "";
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "Compétitions.Ordre, CompetDateDéb DESC";
		$MaKey         = "CompetCode";
		$NomsColonnes  = array('Code','Libellé','Date','Lieu','Sport','Equ','Equ Auto','Options Colonnes','Etat','Statut','Obs','Ind','Equ','Num');
		$ChampsTri     = array('/','/','/','/','/','/','/','/','/','/','/','/','/','/');
		$ChampsAli     = array('center','','center','','','center','center','','center','center','center','center', 'center', 'center');
		$ChampsFor     = array('','', '', '','' , '', '', '', '','','','','','');
		$ChampsAff     = array(false,true,true,true,true,true,false,true,true,true,true,true,true,false);
		$Choix         = array("importer","exporter","ajout","modifier","filtrage","supprimer","monter","descendre","stat");
		$ChampsType    = array("Texte","Texte","Texte","Texte","Texte", "ListeS", "ListeS","Texte","ListeS","ListeS","Texte","Texte","Texte","Texte");
		$ChampsTypeExt = array('', '', '', '' , '', array("0"=>"Non","1"=>"Oui"), array("0"=>"Non","1"=>"Oui"),'',array("0"=>"Masquer","1"=>"Afficher"), array('Inscriptions fermées'=>'Inscriptions fermées','Inscriptions ouvertes'=>'Inscriptions ouvertes'),'','','','');
		$ChampsFiltre  = array(true,true,true,true,true,true,true,false,true,true,true,false,false,false,false);
		$ChampsNomFil  = array('', '', '', '', '' , '', '', '', '','','','','','');
		$ChampsRacFiltre = array(false,false,false,false,true,true,true,false,true,true,false,false,false,false);		
		$ChampsRacParam  = array(array(2,'competitions','individuels','Compet',0,'->'));
		$sousqueryStr    = "";
		$ChampsEdit      = array(
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("Texte" ,true ,"","",true),
						   array("ListeD",true ,"CompetSpoCode",array("selsport", "Sport...", "SELECT SpoLibelCourt FROM Sports WHERE SpoGestionPerf <= 0 ORDER BY Ordre", Array("SpoLibelCourt"), Array(""), "SpoLibelCourt", "","Select SpoCode From Sports WHERE SpoLibelCourt =","100"),true),
						   array("ListeS",true ,"",array("CompetEqu", "Equ ..."       ,array('0'=>'Non','1'=>'Oui')    , "", "", "", "", "", "65"),true),
						   array("ListeS",false ,"",array("CompetCalculAutoEqu", "Composition Auto ...",array('0'=>'Non','1'=>'Oui')    , "", "", "", "", "", "160"),true),
						   array("Texte" ,true,"","",false),
						   array("ListeS",true ,"",array("CompetEtat"  , "Etat..."       ,array('0'=>'Masquer','1'=>'Afficher')    , "", "", "", "", "", "65"),true),
						   array("ListeS",true ,"",array("CompetStatut", "Statut..."     ,array('Inscriptions fermées'=>'Inscriptions fermées','Inscriptions ouvertes'=>'Inscriptions ouvertes')    , "", "", "", "", "", "120"),true),
						   array("Texte" ,true ,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,true,"","",true)
						   );
		$ChampsInsert    = array(
						   array("Texte" ,true ,"","", array("Max","CompetCode"), true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("Texte" ,true ,"","","",true,true,false),
						   array("ListeD",true ,"CompetSpoCode",array("selsport", "Sport...", "SELECT SpoLibelCourt FROM Sports WHERE SpoGestionPerf <= 0 ORDER BY Ordre", Array("SpoLibelCourt"), Array(""), "SpoLibelCourt", "","Select SpoCode From Sports WHERE SpoLibelCourt =","100"),"",true,true,false),
						   array("ListeS",true ,"",array("CompetEqu", "Equ ..."       ,array('0'=>'Non','1'=>'Oui'), "", "", "", "", "", "65"),"",true,true,false),
						   array("ListeS",false ,"",array("CompetCalculAutoEqu", "Composition Auto ..."       ,array('0'=>'Non','1'=>'Oui')    , "", "", "", "", "", "160"),"",true,true,false),
						   array("Texte" ,true,"","","",true,false,false),
						   array("ListeS",true ,"",array("CompetEtat"  , "Etat..."       ,array('0'=>'Masquer','1'=>'Afficher')    , "", "", "", "", "", "65"),"",true,true,false),
						   array("ListeS",true ,"",array("CompetStatut", "Statut..."     ,array('Inscriptions fermées'=>'Inscriptions fermées','Inscriptions ouvertes'=>'Inscriptions ouvertes')    , "", "", "", "", "", "120"),"",true,true,false),
						   array("Texte" ,true ,"","","",true,false,false),
						   array("Texte" ,false,"","","",false,false,false),
						   array("Texte" ,false,"","","",false,false,false),
						   array("Texte" ,true ,"","","0",true,true,false)
						   );
		$messagedel      = "Attention ! la suppression d'une compétition entraîne la suppression des participations de cette compétition.";
		$MajChpOrdre     = array(array("Compétitions","Ordre"));
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam,$sousqueryStr,$messagedel,$MajChpOrdre);
		} else {
			ConstruitStat(-1, 1, $queryStr, $NomsColonnes, $ChampsAli, $ChampsFor, $ChampsAff, $ChampsType, $Choix);
			GereData("", $queryStr, "", $NomsColonnes, "", $ChampsAli, $ChampsFor, $ChampsAff, $action, "", $Choix, "", "", $ChampsType, "", "", "", "", "", "", "", "", "", 1);
		}
		}
		
		if ((!(!($queryCompet))) && (($sousmenu == "individuels") || ($sousmenu == "equipes") || ($sousmenu == "individuels(2)") || ($sousmenu == "licences"))) {
			echo "<TABLE CLASS = 'tablecompet'>";
			echo"<TR CLASS = 'trcompet'><TD>";
			echo $TexteCompet;
			echo"</TD></TR>";
			echo"</TABLE>";
			if ($affgrillecompet == "non") echo "<BR><BR>";
			if (($affcompet == "oui") || ($affcompet == "nonfermees")){
				echo "<TABLE CLASS = 'tablecompet2'>";
				if (!($Adm)) echo "<TR CLASS = 'trcompet2'><TD><I>&nbsp; Autres compétitions disponibles (cliquez sur la compétition de votre choix) : </I></TD></TR><TR>";
				if ($res = mysql_fetch_array($Req)) {
					$monnum = 1;
					do { 
						$monnum = $monnum + 1;
						echo "<TR CLASS = 'trcompet2'>";
						echo "<TD>"; 
						echo "<a href=$PHP_SELF?action=VoirMenu&tablename=$tablename&selection=0&sousmenu=individuels&Compet=".$res["CompetCode"]."&Etab=$Etab&Lic=$Lic> &nbsp; ".$monnum." - ".$res["SpoLibellé"]." - ".$res["CompetLibellé"]." - ".$res["CompetDateDébut"]." - ".$res["CompetLieu"];
						if (!($Consult)) echo " - ".$res["CompetStatut"];
						if (!($res["CompetObs"]) == "") echo " - ".$res["CompetObs"];
						echo "&nbsp;&nbsp;&nbsp; </a></TD>"; 
						echo "</TR>";
					} while ($res = mysql_fetch_array($Req));
				}	
				echo "</TABLE>";
			} 
		}
		
		if (!($Adm)) echo $TxtSM;
		
		$tabChp = array_merge(explode("//", TrouveSport($Compet, "CompetChpSup")), array('',''));
		
		if ( ($sousmenu == "individuels(2)") && (in_array("-Epr", array_merge(explode("/", $tabChp[0]), array('','','','','')))) ) $sousmenu = "individuels";
		
		if (($sousmenu == "individuels") && ($affgrillecompet == "oui")) {	
		If ($stat == 0) {
		$tabChpSup = array_merge(explode("/", $tabChp[0]), array('','','','','','','','',''));
		for ($i = 0; $i < count($tabChpSup); $i++) $tabChpSup[$i] = trim($tabChpSup[$i]);
		$affQualif = false;
		if (in_array("Qualif", $tabChpSup)) { 
			$affQualif = true; 
			unset($tabChpSup[array_search("Qualif", $tabChpSup)]);
			$tabChpSup = array_values($tabChpSup);
		}
		$affEpr = true;
		if (in_array("-Epr", $tabChpSup)) { 
			$affEpr = false; 
			unset($tabChpSup[array_search("-Epr", $tabChpSup)]);
			$tabChpSup = array_values($tabChpSup);
		}
		$affQuad = false; 
		if (in_array("+Quad", $tabChpSup)) { 
			$affQuad = true; 
			unset($tabChpSup[array_search("-Quad", $tabChpSup)]);
			$tabChpSup = array_values($tabChpSup);
		}
		
		$strCatEpr = "";
		for ($i = 0; $i < count($tabChpSup); $i++) {
			$monChpSup = explode("#", $tabChpSup[$i]);
			if (in_array("CatEpr", $monChpSup)) {
				unset($tabChpSup[$i]);
				$tabChpSup = array_values($tabChpSup);
				if (array_key_exists('1', $monChpSup)) {
					$Valeurs = explode(";", $monChpSup[1]);
					if (count($Valeurs) > 0) {
						for ($j = 0; $j < count($Valeurs); $j++) { 
							if (!($strCatEpr == "")) $strCatEpr = $strCatEpr." OR ";
							switch($Valeurs[$j]) {
								case "Open": $strCatEpr = $strCatEpr."EprLibelCourt LIKE '%Open%'"; Break;
								case "Pré" : $strCatEpr = $strCatEpr."EprLibelCourt LIKE '%Pré inscrit%'"; Break;
								default    : $strCatEpr = $strCatEpr."CatLibelCourt = '".$Valeurs[$j] ."'";
							}
						}
						if (!($strCatEpr == "")) $strCatEpr = " AND (".$strCatEpr.")";
					}
				}
			}
		}
		
		$maxInsc = 9999;
		for ($i = 0; $i < count($tabChpSup); $i++) {
			$monChpSup = explode("#", $tabChpSup[$i]);
			if (in_array("Max", $monChpSup)) {
				unset($tabChpSup[$i]);
				$tabChpSup = array_values($tabChpSup);
				if (array_key_exists('1', $monChpSup)) $maxInsc = $monChpSup[1];
				if ($monChpSup[1] == 0) $maxInsc = 9999; 
			}
		}
		
		$CEprU = "";
		if ($affEpr == false) {
			$reqU = bf_mysql_query("SELECT CatLibelCourt, COUNT(EprLibelCourt) AS Nbre FROM Catégories LEFT JOIN Epreuves ON CatCode = EprCatCode Where CatSpoCode = ".TrouveSport($Compet, "SpoCode")." GROUP BY CatLibelCourt HAVING Nbre > 1 "); 
			if (!(!$reqU)) {
				$resU = mysql_fetch_array($reqU);
				if (!(!$resU)) { 
					$reqeprU = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode WHERE `Epreuves Compétitions`.EprCompetCompetCode = $Compet
										       AND EprLibelCourt LIKE 'Pré Inscrit%' AND IF(CatSpoCode = 10, TRUE, Catégories.CatSexCode = 
										     ( SELECT LicSexCode FROM Licenciés WHERE LicNumLicence = $ParLicCode LIMIT 1))  
										       ORDER BY Epreuves.Ordre");
				} else {
					$reqeprU = bf_mysql_query("SELECT EprCompetCode, EprLibelCourt FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode WHERE `Epreuves Compétitions`.EprCompetCompetCode = $Compet
											   AND Catégories.CatLibelCourt =
											 ( SELECT CatLibelCourt FROM Catégories, Licenciés WHERE ( (Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = $Sport) AND LicNumLicence = $ParLicCode ORDER BY Catégories.Ordre LIMIT 1) 
										       ORDER BY Epreuves.Ordre");
				}	
				if (!(!$reqeprU)) {
					$reseprU = mysql_fetch_array($reqeprU);
					if (!(!$reseprU)) $CEprU = $reseprU['EprCompetCode'];
				}
			}
		}
		
		for ($i = 1; $i <= 5; $i++) {$NomChp{$i} = ""; $Type{$i} = "Texte"; $List{$i} = ""; $PasVide{$i} = false;}
		for ($i = 0; $i < count($tabChpSup); $i++) {
			$Sup = explode("#", $tabChpSup[$i]);
			$num = $i + 1;
			$NomChpSup{$num} = trim($Sup[0]);
			if (strpos($NomChpSup{$num},"!") === 0) {
				$PasVide{$num} = true;
				$NomChpSup{$num} = substr($NomChpSup{$num},1) ;
			}
			if (array_key_exists('2', $Sup)) { $Sup[2] = trim($Sup[2]); if (!(is_numeric($Sup[2]))) $Sup[2] = ""; else if ($Sup[2] < 100) $Sup[2] = "";} else $Sup[2] = "";
			if (array_key_exists('1', $Sup)) {
				$Valeurs = explode(";", $Sup[1]);
				if (count($Valeurs) > 0) {
					$ListeValeurs = "";
					for ($j = 0; $j < count($Valeurs); $j++) { 
						if (strpos($Valeurs[$j],"@") !== false) {
							$ValeursTO = explode("@", $Valeurs[$j]);
							
							$prefixe = '';
							$deb = strpos($ValeursTO[0],"[");
							if ($deb !== false) {
								$fin = strpos($ValeursTO[0],"]",$deb);
								if ($fin !== false) {
									$prefixe = substr($ValeursTO[0],$deb+1,$fin-1);
									$ValeursTO[0] = substr($ValeursTO[0],$fin+1,strlen($ValeursTO[0]));
								}
							}
							$suffixe = '';
							$deb = strpos($ValeursTO[1],"[");
							if ($deb !== false) {
								$fin = strpos($ValeursTO[1],"]",$deb);
								if ($fin !== false) {
									$suffixe = substr($ValeursTO[1],$deb+1,$fin-3);
									$ValeursTO[1] = substr($ValeursTO[1],0,$deb-1);
								}
							}
						
							$ValeursTO[0] = trim($ValeursTO[0]); $ValeursTO[1] = trim($ValeursTO[1]);
							if ( (is_numeric($ValeursTO[0])) && (is_numeric($ValeursTO[1])) ) {
								for ($k = $ValeursTO[0]; $k <= $ValeursTO[1]; $k++) { 
									$ListeValeurs[$prefixe.$k.$suffixe] = $prefixe.$k.$suffixe;
									if (array_key_exists('2', $ValeursTO)) { $ValeursTO[2] = trim($ValeursTO[2]); if (is_numeric($ValeursTO[2])) $k = $k + $ValeursTO[2]-1; }
								}
							}
						} else if ($Valeurs[$j] != "") $ListeValeurs[$Valeurs[$j]] = $Valeurs[$j];
					}
					if ($ListeValeurs != "") { $List{$num} = array("", $NomChpSup{$num}."...", $ListeValeurs, "", "", "", "", "", $Sup[2]); $Type{$num} = "ListeD";}
				} 
			}
		}
		
		if ( (!($Consult)) && isset($_GET['ParLicCode']) && ($aj == True) && ($selectionner == true) && (strlen($_POST['ParLicCode'])==0) ) $_POST['ParLicCode'] = $_GET['ParLicCode'];
		
		$tablename  = "Participations";
		$queryStr        ="SELECT EtabNomCourt, ParCode, ParLicCode, Licenciés.LicNom, Licenciés.LicPrénom, Licenciés.LicNaissance, Licenciés.LicSexCode, CatLibelCourt, EprLibelCourt, ParCompetCode, ParEprCode, ParQuadra, EquNum, ParPerfQualif, ParObs1, ParObs2, ParObs3, ParObs4, ParObs5 
						   FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode INNER JOIN `Epreuves Compétitions` ON `Epreuves Compétitions`.EprCompetCode = Participations.ParEprCode INNER JOIN Epreuves ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Equipes ON Equipes.EquCode = Participations.ParEquCode "; 
		$where = "(((Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = 1)";
		if (!$Adm) $where .= " AND ParCompetCode = $Compet AND (EtabNum = $Etab".RetAS($Etab)."))"; else $where .= " AND ParCompetCode = $Compet)";
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "LicNom, LicPrénom, Epreuves.Ordre";
		$MaKey           = "ParCode";
		$NomsColonnes    = array('Etab','Code','Numéro','Nom','Prénom','Naissance','Sexe','Cat','Epreuve','Compet','Epr','Quad','Equ','Qualif', $NomChpSup{1},$NomChpSup{2},$NomChpSup{3}, $NomChpSup{4}, $NomChpSup{5} );
		$ChampsTri       = array('EtabNomCourt, LicNom, LicPrénom, Epreuves.Ordre','ParCode','LicNumLicence, Epreuves.Ordre','LicNom, LicPrénom, Epreuves.Ordre','LicPrénom, LicNom, Epreuves.Ordre','LicNaissance, LicNom, LicPrénom, Epreuves.Ordre','LicSexCode, LicNom, LicPrénom, Epreuves.Ordre','LicSexCode DESC, LicNaissance DESC, LicNom, LicPrénom, Epreuves.Ordre', 'Epreuves.Ordre, LicNom, LicPrénom','','','ParQuadra, Epreuves.Ordre, LicNom, LicPrénom' ,'EquNum, LicNom, LicPrénom','ParPerfQualif, LicNumLicence, LicNom, LicPrénom', 'ParObs1, LicNumLicence, LicNom, LicPrénom','ParObs2, LicNumLicence, LicNom, LicPrénom','ParObs3, LicNumLicence, LicNom, LicPrénom','ParObs4, LicNumLicence, LicNom, LicPrénom','ParObs5, LicNumLicence, LicNom, LicPrénom');
		$ChampsAli       = array('center','','center','','','center','center','center','center','','','center','center','right','center','center','center','center','center');
		$ChampsFor       = array('','','%010s','','','','','','','','','','','','','','','','');
		$ChampsAff       = array(true,false,true,true,true,true,true,true,$affEpr,false,false,(($SpoGestion == -1) && ($affQuad) ),(($SpoGestion != -5) && ($CompetEqu)),$affQualif,($tabChpSup[0] != ""),($tabChpSup[1] != ""),($tabChpSup[2] != ""),($tabChpSup[3] != ""),($tabChpSup[4] != ""));
		$ChampsType      = array("Texte","Texte","ListeD","Texte","Texte","Date","ListeS","Texte","ListeD","Texte",'Texte',"ListeS",'Texte','Perf','Texte','Texte','Texte','Texte','Texte');
		$ChampsTypeExt   = array("","","","","","",array('1'=>'G','2'=>'F'),"","","","",array('0'=>'Non','1'=>'Oui'),"","","","","","","");				   
		$ChampsFiltre    = array(true,true,true,true,true,true,true,true,true,false,false,($SpoGestion == -1),(($SpoGestion != -5) && ($CompetEqu)),true,true,true,true,true,true);
		$ChampsNomFil    = array("","","","","","","","","","","","","","","","","","","");
		$ChampsRacFiltre = array(true,false,true,false,false,false,true,true,true,false,false,true,true,false,false,false,false,false,false);
		$ChampsRacParam  = "";
		$sousqueryStr    = "";
		if (($Adm) || ($CompetStatut == "Inscriptions ouvertes")) {
			$Choix = array("ajout","supprimer","filtrage","exporter");
			if ( ( ((!($Adm)) && (TrouveParamweb("ImpressionLic") == 1)) || (($Adm) && (TrouveParamweb("ImpressionLic") > 0)) ) || (TrouveParamweb("InscriptionLic") > 0)) array_push($Choix,"licence");
			if ($ChampsAff[8] || $ChampsAff[11] || $ChampsAff[12] || $ChampsAff[13] || $ChampsAff[14] || $ChampsAff[15] || $ChampsAff[16]) array_push($Choix, "modifier");
		} else $Choix = array("filtrage","exporter");
		if ($Adm) { array_push($Choix, "suppressiontout"); array_push($Choix, "stat");}
		if ($Consult) $Choix = array("consultation");
		if ($Adm) { $Clause1 = ""; $Clause2 = ""; } else { $Clause1 = "WHERE EtabNum = $Etab"; $Clause2 = "AND EtabNum = $Etab";}	
		if ($SpoGestion == -4) $chpcat = "Chall"; else $chpcat = "CatLibelCourt";
		$ChampsEdit      = array(
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Date"  ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("ListeD", $affEpr, "ParEprCode",
								array("selepr",
									  "Epreuve...",
									  "SELECT EprCompetCode, EprLibelCourt, EprLibellé 
									  FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
									  WHERE (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet.$strCatEpr." ORDER BY Epreuves.Ordre", 
									  Array("EprLibelCourt", "-", "EprLibellé"),
									  Array("", "", ""),
									  "EprCompetCode",
									  "SELECT EprCompetCode FROM Epreuves INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCompetCompetCode = $Compet AND EprLibelCourt=",
									  "",
									  "150"),
									  true),
						   array("Texte" ,true ,"ParCompetCode","",true),
						   array("Texte" ,false,"","",false),
						   array("ListeS", ($SpoGestion == -1), "",array("ParQuadra", "Quad...",array('0'=>'Non','1'=>'Oui'), "", "", "", "","","45"),false),						   
						   array("ListeD",(($SpoGestion != -5) && ($CompetEqu)) ,"ParEquCode",
								array("selequ",
									  "Equipe...",
									  "SELECT EquCode, EquNum, EquComplément, EtabNum, EtabNomCourt, EtabNom, EtabVille, CatLibelCourt, IF(EquChall=1, 'Courses', IF(EquChall = 2, 'Sauts', IF(EquChall = 3, 'Lancers',''))) AS Chall 
									  FROM Etablissements, Catégories INNER JOIN Equipes ON Catégories.CatCode = Equipes.EquCatCode LEFT JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode LEFT JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode 
									  WHERE Etablissements.EtabCode = Equipes.EquEtabCode AND EquCompetCode = $Compet $Clause2 ORDER BY EquNum",
									  Array("EquNum", "-", $chpcat, "-", "EtabNomCourt", "EquComplément"),
									  Array("", "", "", "", "", ""),
									  "EquCode",
									  "SELECT EquCode FROM Equipes WHERE EquCompetCode = $Compet AND EquNum = ",
									  "",
									  "100"),
									  false),
						   array("Texte" ,true,"","",false),
						   array($Type{1}, true, "", $List{1}, $PasVide{1}),
						   array($Type{2}, true, "", $List{2}, $PasVide{2}),
						   array($Type{3}, true, "", $List{3}, $PasVide{3}),
						   array($Type{4}, true, "", $List{4}, $PasVide{4}),
						   array($Type{5}, true, "", $List{5}, $PasVide{5})
						   );
		$ChampsInsert    = array(
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,true, ""             ,"","",true,false,true),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Date"  ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("Texte" ,false,""             ,"","",true,false,false),
						   array("ListeD",true ,"ParEprCode"   , 
								array(
										"selepr",
										"Epreuve...",
										"SELECT EprCompetCode, EprLibelCourt, EprLibellé 
										FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
										WHERE (Groupes.GrLibelCourt < 9 OR Groupes.GrLibelCourt IS NULL) AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet.$strCatEpr." ORDER BY Epreuves.Ordre", 
										Array("EprLibelCourt", "-", "EprLibellé"),
										Array("", "", ""),
										"EprCompetCode",
										"",
										"",
										"150"
								),
								$CEprU, $affEpr, true, false), 
						   array("Texte" ,true ,"ParCompetCode","","$Compet",true, false,false),
						   array("Texte" ,false,""             ,"","",true, false,false),
   						   array("ListeS",($SpoGestion == -1) ,"",array("ParQuadra", "Quad..."    ,array('0'=>'Non','1'=>'Oui'), "", "", "", "","","45"),"",($SpoGestion == -1), false,false),
						   array("ListeD",(($SpoGestion != -5) && ($CompetEqu)) ,"ParEquCode",
								array("selequ",
									  "Equipe...",
 									  "SELECT EquCode, EquNum, EquComplément, EquChall, EtabNum, EtabNomCourt, EtabNom, EtabVille, CatLibelCourt, IF(EquChall = 1, 'Courses', IF(EquChall = 2, 'Sauts', IF(EquChall = 3, 'Lancers',''))) AS Chall 
									  FROM Etablissements, Catégories INNER JOIN Equipes ON Catégories.CatCode = Equipes.EquCatCode LEFT JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode LEFT JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode 
									  WHERE Etablissements.EtabCode = Equipes.EquEtabCode AND EquCompetCode = $Compet $Clause2 ORDER BY EquNum",
									  Array("EquNum", "-", $chpcat, "-", "EtabNomCourt", "EquComplément"),
									  Array("", "", "", "", "", ""),
									  "EquCode",
									  "SELECT EquCode FROM Equipes WHERE EquCompetCode = $Compet AND EquNum = ",
									  "",
									  "100"),
								"",true,false,false),
							array("Texte" ,true,"","","",true,false,false),
							array($Type{1},true,"",$List{1},"",true,$PasVide{1},false),
							array($Type{2},true,"",$List{2},"",true,$PasVide{2},false),
							array($Type{3},true,"",$List{3},"",true,$PasVide{3},false),
							array($Type{4},true,"",$List{4},"",true,$PasVide{4},false),
							array($Type{5},true,"",$List{5},"",true,$PasVide{5},false)
						   );
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr,"","","",$strCatEpr, $maxInsc);
		} else {
			if ($stat == 1) $SportCat = 1; else $SportCat = $Sport;
			ConstruitStat($Compet, $SportCat, $queryStr, $NomsColonnes, $ChampsAli, $ChampsFor, $ChampsAff, $ChampsType, $Choix);
			GereData("", $queryStr, "", $NomsColonnes, "", $ChampsAli, $ChampsFor, $ChampsAff, $action, "", $Choix, "", "", $ChampsType, "", "", "", "", "", "", "", "", "", $stat);
		}
		};
					
		if (($sousmenu == "individuels(2)")) {	
		$tablename  = "Participations";
		$queryStr        ="SELECT DISTINCT EtabNomCourt, ParLicCode, LicNom, LicPrénom, LicNaissance, LicSexCode, CatLibelCourt, ParCompetCode
						   FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode ";
		$where = "(((Licenciés.LicNaissance Between CatDateDéb And CatDateFin) And LicSexCode = CatSexCode And CatSpoCode = 1) ";
		if (!$Adm) $where .= " And ParCompetCode = $Compet AND (EtabNum = $Etab".RetAS($Etab)."))"; else $where .= " And ParCompetCode = $Compet)";
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "LicNom, LicPrénom";
		$MaKey           = "ParCode";
		$NomsColonnes    = array('Etab','Numéro','Nom','Prénom','Naissance','Sexe','Cat','Compet');
		$ChampsTri       = array('EtabNomCourt, LicNom, LicPrénom','LicNumLicence','LicNom, LicPrénom','LicPrénom, LicNom','LicNaissance, LicNom, LicPrénom','LicSexCode, LicNom, LicPrénom','LicSexCode DESC, LicNaissance DESC, LicNom, LicPrénom','','ParObs1, LicNumLicence, LicNom, LicPrénom','ParObs2, LicNumLicence, LicNom, LicPrénom','ParObs3, LicNumLicence, LicNom, LicPrénom');
		$ChampsAli       = array('center','center','','','center','center','center','center');
		$ChampsFor       = array('','%010s','','','','','','');
		$ChampsAff       = array((($Adm) || ($Consult)),true,true,true,true,true,true,false);
		$ChampsType      = array("Texte","ListeD","Texte","Texte","Date","ListeS","Texte","Texte");
		$ChampsTypeExt   = array("","","","","",array('1'=>'G','2'=>'F'),"","");				   
		$ChampsFiltre    = array(true,true,true,true,true,true,true,true);
		$ChampsNomFil    = array("","","","","","","","");
		$ChampsRacFiltre = array(true,true,false,false,false,true,true,true);
		$ChampsRacParam  = array(array(1,'competitions','individuels','ParLicCode',1,'->'));
		if ($Consult) $Choix = array("consultation"); else $Choix = array("filtrage");
		if ($Adm) { $Clause1 = ""; $Clause2 = ""; } else { $Clause1 = "WHERE EtabNum = $Etab"; $Clause2 = "AND EtabNum = $Etab";}	
		$ChampsEdit      = "";
		$ChampsInsert    = "";
		$sousqueryStr    = array("SELECT EprLibelCourt, ParQuadra, EquNum, IF(EquNum > 0,CONCAT('Eq',INSERT(EquNum, 1, LENGTH(EtabNum), '')),NULL) AS EquNumero
						          FROM Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode INNER JOIN `Epreuves Compétitions` ON `Epreuves Compétitions`.EprCompetCode = Participations.ParEprCode INNER JOIN Epreuves ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode LEFT JOIN Equipes ON Equipes.EquCode = Participations.ParEquCode
								  WHERE ParCompetCode = $Compet And ParLicCode = ",
								  "Epreuves", "ParLicCode", Array(Array("EprLibelCourt","","","",False),array("ParQuadra","==",1,"Qd","False"),array("EquNum",">",0,"EquNumero",True)));   
		if (isset($filter)) { 
			$tabfilter = explode(" AND ", $filter);
			$counttab = count($tabfilter);
			for ($i = 0; $i < $counttab; $i++) {if ( is_int(strpos($tabfilter[$i],"EprLibelCourt")) || is_int(strpos($tabfilter[$i],"EquNum"))) unset($tabfilter[$i]);}
			$filter = implode(" AND ", $tabfilter);
		}
		if (isset($orderby)) { 
			$taborderby = explode(",", $orderby);
			$counttab = count($taborderby);
			for ($i = 0; $i < $counttab; $i++) { if ( is_int(strpos($taborderby[$i],"Epreuves")) || is_int(strpos($taborderby[$i],"EquNum"))) unset($taborderby[$i]);}
			$orderby = implode(",", $taborderby);
		}
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr);
		}
		
		
		if (($sousmenu == "licences")) {	
			
			if ($action == "deleteData") bf_mysql_query("UPDATE Compétitions SET CompetDemLic = REPLACE(CompetDemLic,',".$_GET['Lic']."','') WHERE CompetCode = ".$Compet);
			
			$tablename     = "Licenciés";
			$queryStr      = "SELECT EtabNomCourt, LicCode, LicInscrit, LicNumLicence, LicNom, LicPrénom, LicNaissance, LicSexCode, CatLibelCourt, LicAss, LicNomAss, LicDateAss, LicDateDem, LicDateValid
							  FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode 
							  WHERE LicNumLicence IN(0".TrouveSport($Compet, "CompetDemLic").") AND ( CatSpoCode = 1 AND LicSexCode = CatSexCode AND (Licenciés.LicNaissance >= CatDateDéb And Licenciés.LicNaissance <= CatDateFin)) ";
			if (!$Adm) $queryStr .= " AND (EtabNum = $Etab ".RetAS($Etab).") ";
			$queryStr     .= "UNION 
							  SELECT DISTINCT EtabNomCourt, LicCode, LicInscrit, LicNumLicence, LicNom, LicPrénom, LicNaissance, LicSexCode, CatLibelCourt, LicAss, LicNomAss, LicDateAss, LicDateDem, LicDateValid
							  FROM Catégories, Etablissements INNER JOIN Licenciés ON Etablissements.EtabCode = Licenciés.LicEtabCode INNER JOIN Participations ON Licenciés.LicNumLicence = Participations.ParLicCode";
			$where = "(ParCompetCode = $Compet) AND ( CatSpoCode = 1 AND LicSexCode = CatSexCode AND (Licenciés.LicNaissance >= CatDateDéb And Licenciés.LicNaissance <= CatDateFin) ";
			if (!$Adm) $where .= " And (EtabNum = $Etab ".RetAS($Etab)."))"; else $where.=")";
			$Choix = array("modifier","filtrage","licence");
			if ((!isset($orderby)) || ($orderby == "")) $orderby = "LicNom, LicPrénom";
			$MaKey         = "LicCode";
			$NomsColonnes  = array('Etab','Code','Inscrit','Numéro','Nom','Prénom','Naissance','Sexe','Cat','Ass','Ass Nom','Ass Date','Demande','Validation','Compet',);
			$ChampsTri     = array('EtabNomCourt, LicNom, LicPrénom', '', 'LicInscrit DESC, LicNom, LicPrénom', 'LicNumLicence', 'LicNom, LicPrénom', 'LicPrénom, LicNom', 'LicNaissance, LicNom, LicPrénom', 'LicSexCode DESC, LicNom, LicPrénom', 'LicSexCode DESC, LicNaissance DESC, LicNom, LicPrénom', 'LicAss DESC, LicNom, LicPrénom', 'LicNomAss, LicNom, LicPrénom','LicDateAss DESC, LicNom, LicPrénom', 'LicDateDem DESC, LicNom, LicPrénom', 'LicDateValid DESC, LicNom, LicPrénom');
			$ChampsAli     = array('center','','center','center','','','center','center','center','center','','center','center','center');
			$ChampsFor     = array('','','','%010s','','','','','','','','','','','');
			$ChampsAff     = array((($Adm) || ($Consult)),false,true,true,true,true,true,true,true,true,false,true,(TrouveParamweb("InscriptionLic") > 0),(TrouveParamweb("InscriptionLic") > 0),false);
			$ChampsType    = array("Texte","Texte","ListeS","Texte","Texte","Texte","Date","ListeS","Texte","ListeS","Texte","Date","Date","Date","Texte");
			$ChampsTypeExt = array("","",array("0"=>"Non","1"=>"Oui"),"","","","",array("1"=>'G',"2"=>'F'),"",array("0"=>"Non","1"=>"Oui"),"","","","","");
			$ChampsFiltre  = array(true,false,true,true,true,true,true,true,true,true,true,true,true,true,false);
			$ChampsNomFil  = array("","","","","","","","","","","","","","","");
			$ChampsRacFiltre = array(true,false,true,true,false,false,false,true,true,true,true,true,true,true,false);
			$ChampsRacParam  = "";
			$sousqueryStr    = "";
			$ChampsEdit      = array(
						   array("Texte" ,false,"","",true),
						   array("Texte" ,false ,"","",true),
						   array("ListeS",($Adm && (!(isset($valid)))),"",array("LicInscrit", "Inscrit..."    ,array("0"=>"Non","1"=>"Oui"), "", "", "", "", "","45"),true),
						   array("Texte" ,false,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Texte" ,$Adm ,"","",true),
						   array("Date"  ,$Adm ,"","",true),
						   array("ListeS",$Adm ,"",array("LicSexCode", "Sexe..."       ,array('1'=>'G','2'=>'F')    , "", "", "", "", "", "35"),true),
						   array("Texte" ,false,"","",true),
						   array("ListeS",true,"",array("LicAss", "Ass..."    ,array("0"=>"Non","1"=>"Oui"), "", "", "", "", "","45"),false),
						   array("Texte" ,true,"","",false),
						   array("Date"  ,$Adm,"","",false),
						   array("Date"  ,$Adm,"","",false),
						   array("Date"  ,($Adm && (!(isset($valid)))),"","",false),
						   array("Texte" ,false,"","",false)
						   );
			$messagedel = "";
			GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam,$sousqueryStr,$messagedel);
		}
	
		if (($sousmenu == "equipes") && ($affgrillecompet == "oui")) {	
		$tabChpSup = array_merge(explode("/", $tabChp[1]), array('','','','','','',''));
		for ($i = 0; $i < count($tabChpSup); $i++) $tabChpSup[$i] = trim($tabChpSup[$i]);
		
		$affQualif = false;
		if (in_array("Qualif", $tabChpSup)) { 
			$affQualif = true; 
			unset($tabChpSup[array_search("Qualif", $tabChpSup)]);
			$tabChpSup = array_values($tabChpSup);
		}
		
		$relOblige = false;
		if (in_array("Relayeurs", $tabChpSup)) { 
			$relOblige = true; 
			unset($tabChpSup[array_search("Relayeurs", $tabChpSup)]);
			$tabChpSup = array_values($tabChpSup);
		}
		
		$strCatEpr = "";
		for ($i = 0; $i < count($tabChpSup); $i++) {
			$monChpSup = explode("#", $tabChpSup[$i]);
			if (in_array("CatEpr", $monChpSup)) {
				unset($tabChpSup[$i]);
				$tabChpSup = array_values($tabChpSup);
				$Valeurs = explode(";", $monChpSup[1]);
				if (count($Valeurs) > 0) {
					for ($j = 0; $j < count($Valeurs); $j++) { 
						if (!($strCatEpr == "")) $strCatEpr = $strCatEpr." OR ";
						$strCatEpr = $strCatEpr."CatLibelCourt = '".$Valeurs[$j] ."'"; 	
					}
					if (!($strCatEpr == "")) $strCatEpr = " AND (".$strCatEpr." OR EprLibelCourt LIKE '%Open%')";
				}
			}
		}
		
		for ($i = 1; $i <= 5; $i++) {$NomChp{$i} = ""; $Type{$i} = "Texte"; $List{$i} = ""; $PasVide{$i} = false;}
		for ($i = 0; $i < count($tabChpSup); $i++) {
			$Sup = explode("#", $tabChpSup[$i]);
			$num = $i + 1;
			$NomChpSup{$num} = trim($Sup[0]);
			if (strpos($NomChpSup{$num},"!") === 0) {
				$PasVide{$num} = true;
				$NomChpSup{$num} = substr($NomChpSup{$num},1) ;
			}
			if (array_key_exists('2', $Sup)) { $Sup[2] = trim($Sup[2]); if (!(is_numeric($Sup[2]))) $Sup[2] = ""; else if ($Sup[2] < 100) $Sup[2] = "";} else $Sup[2] = "";
			if (array_key_exists('1', $Sup)) {
				$Valeurs = explode(";", $Sup[1]);
				if (count($Valeurs) > 0) {
					$ListeValeurs = "";
					for ($j = 0; $j < count($Valeurs); $j++) { 
						if (strpos($Valeurs[$j],"@") !== false) {
							$ValeursTO = explode("@", $Valeurs[$j]);
							$ValeursTO[0] = trim($ValeursTO[0]); $ValeursTO[1] = trim($ValeursTO[1]);
							if ( (is_numeric($ValeursTO[0])) && (is_numeric($ValeursTO[1])) ) {
								for ($k = $ValeursTO[0]; $k <= $ValeursTO[1]; $k++) { 
									$ListeValeurs[$k] = $k;
									if (array_key_exists('2', $ValeursTO)) { $ValeursTO[2] = trim($ValeursTO[2]); if (is_numeric($ValeursTO[2])) $k = $k + $ValeursTO[2]-1; }
								}
							}
						} else if ($Valeurs[$j] != "") $ListeValeurs[$Valeurs[$j]] = $Valeurs[$j];
					}
					if ($ListeValeurs != "") { $List{$num} = array("", $NomChpSup{$num}."...", $ListeValeurs, "", "", "", "", "", $Sup[2]); $Type{$num} = "ListeD";}
				} 
			}
		}
			
		$tablename  = "Equipes";
		$queryStr        ="SELECT EtabNomCourt, EtabNom, EtabVille, EquCode, EquNum, EquComplément, CatLibelCourt, EprLibelCourt, EquCompetCode, EquPromo, EquChall, EquRelayeurs, EquPerfRelaisQualif, EquObs1, EquObs2, EquObs3, EquObs4, EquObs5
						   FROM Etablissements, Catégories INNER JOIN Equipes ON Catégories.CatCode = Equipes.EquCatCode LEFT JOIN `Epreuves Compétitions` ON Equipes.EquEprCompetCode = `Epreuves Compétitions`.EprCompetCode LEFT JOIN Epreuves ON `Epreuves Compétitions`.EprCompetEprCode = Epreuves.EprCode"; 
		if (!$Adm) $where =" Etablissements.EtabCode = Equipes.EquEtabCode AND EquCompetCode = $Compet AND (EtabNum = $Etab ".RetAS($Etab).")"; else $where ="Etablissements.EtabCode = Equipes.EquEtabCode AND EquCompetCode = $Compet";
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "EtabNomCourt, EquNum, EquComplément, Epreuves.Ordre";
		$ArrayChall = array();
		if ($SpoGestion == -4) $ArrayChall = array("1"=>'Courses', "2"=>'Sauts', "3"=>'Lancers');
		if ($SpoGestion == -7) $ArrayChall = array("1"=>'Col F', "2"=>'Col G', "3"=>'Lyc Promo', "4"=>'Lyc Elite');

		$MaKey           = "EquCode";
		$NomsColonnes    = array('Etab','Nom','Ville','Code','Numéro','Complément','Cat','Epreuve','Compet','Crit-Chal','Chall','Relayeurs','Qualif', $NomChpSup{1},$NomChpSup{2},$NomChpSup{3}, $NomChpSup{4}, $NomChpSup{5});
		$ChampsTri       = array("EtabNomCourt, EquNum","EtabNom, EquNum","EtabVille, EquNum","EquCode","EtabNomCourt, EquNum","","","","","","","","EquPerfQualif, EtabNomCourt, EquNum","","","","","");
		$ChampsAli       = array('center','','','center','center','','center','center','center','center','center','',"right",'center','center','center','center','center');
		$ChampsFor       = array('','','','','','','','','','','','','','','','','','');
		$ChampsAff       = array((($Adm) || ($Consult)),$Adm, $Adm, false,true,true,($SpoGestion != -4 && $SpoGestion != -5 ),(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),false,($SpoGestion == -1),(($SpoGestion == -4) || ($SpoGestion == -7)),(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),$affQualif ,($tabChpSup[0] != ""), ($tabChpSup[1] != ""), ($tabChpSup[2] != ""), ($tabChpSup[3] != ""), ($tabChpSup[4] != "") );
		$ChampsType      = array("Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","Texte","ListeS","ListeS","Texte","Perf","Texte","Texte","Texte","Texte","texte");
		$ChampsTypeExt   = array("","","","","","","","","",array('0'=>'Non','1'=>'Oui'),$ArrayChall,"","","","","","","");
		$ChampsFiltre    = array(true,true, true,true,true,true,true,true,true,($SpoGestion == -1),(($SpoGestion == -4) || ($SpoGestion == -7)),true,true,true,true,true,true,true);
		$ChampsNomFil    = array("","","","","","","","","","","","","","","","","","");
		$ChampsRacFiltre = array(true,false,false,false,false,false,true,true,false,true,true,false,false,false,false,false,false,false);
		$ChampsRacParam  = array(array(1,'competitions','individuels','EquNum',4,'->'));
		$sousqueryStr    = "";
		if (($Adm) || ($CompetStatut == "Inscriptions ouvertes")) $Choix = array("ajout","supprimer","filtrage","modifier","exporter"); else $Choix = array("filtrage","exporter");
		if ($Adm) {array_push($Choix, "suppressiontout");}
		if ($Consult) $Choix = array("");
		if ($SpoGestion != -7) {
			$CatPrim    = "SELECT EprCatCode, CatLibelCourt, CatLibellé, CatPrim, CatType, EprCompetCompetCode FROM Catégories INNER JOIN Epreuves ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode GROUP BY Epreuves.EprCatCode, Catégories.CatLibelCourt, Catégories.CatLibellé, Catégories.Ordre, `Epreuves Compétitions`.EprCompetCompetCode, Catégories.CatPrim HAVING `Epreuves Compétitions`.EprCompetCompetCode = $Compet And Catégories.CatPrim = 1 ORDER BY Catégories.Ordre";
			$CatPrimTrue = " AND CatPrim = TRUE";
			$CatChp = "EprCatCode";
			$CatPrimBis = "SELECT CatCode FROM Catégories INNER JOIN Epreuves ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCompetCompetCode = $Compet AND CatLibelCourt=";
			$CatInit = "";
			$EpreInit = "";
			if ($SpoGestion == -4) {
				$resCatInit = mysql_fetch_array( bf_mysql_query("SELECT * FROM Compétitions INNER JOIN Catégories ON Compétitions.CompetSpoCode = Catégories.CatSpoCode WHERE CatLibelCourt = 'JF' AND CompetCode = ".$Compet));
				if ($resCatInit) $CatInit = $resCatInit["CatCode"];
			
				$resEpreInit = mysql_fetch_array( bf_mysql_query("SELECT * FROM Groupes INNER JOIN ((Catégories INNER JOIN Epreuves ON Catégories.CatCode = Epreuves.EprCatCode) INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode) ON Groupes.GrCode = Epreuves.EprGrCode WHERE GrLibelCourt = 9 AND CatLibelCourt = 'JF' AND EprCompetCompetCode = ".$Compet));
				$EpreInit = $resEpreInit["EprCompetCode"];
						
			}
		} else { 
			$CatPrim    = "SELECT CatCode, CatLibelCourt, CatLibellé, CatPrim, CatType FROM Catégories INNER JOIN Sports On Catégories.CatSpoCode = Sports.SpoCode WHERE Sports.SpoGestionPerf = -7 AND Catégories.CatType = 99 ORDER BY Catégories.Ordre";
			$CatPrimTrue = "";
			$CatChp = "CatCode";
			$CatPrimBis = "SELECT CatCode FROM Catégories INNER JOIN Sports On Catégories.CatSpoCode = Sports.SpoCode WHERE Sports.SpoGestionPerf = -7 AND Catégories.CatType = 99 AND Catégories.CatLibelCourt=";
			$CatInit = ""; 
			$EpreInit = "";
		}
		$ChampsEdit      = array(
						   array("ListeD",$Adm,"EquEtabCode",array("seletabequ", "Etab...","SELECT EtabNum, EtabNomCourt, EtabNom, EtabVille FROM Etablissements ORDER BY EtabNum", Array("EtabNum", "EtabNomCourt", "EtabNom", "EtabVille"), Array("%06d", "", "", ""), "EtabNum","SELECT EtabNum FROM Etablissements WHERE EtabNomCourt = ","SELECT EtabCode FROM Etablissements WHERE EtabNum = ","100"),true),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("Texte" ,false,"","",false),
						   array("ListeD",true ,"EquCatCode",array("selcatequ", "Catégorie...",$CatPrim, Array("CatLibelCourt", "-", "CatLibellé"), Array("", "", ""), $CatChp, $CatPrimBis,"","130"),true),
						   array("ListeD",(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),"EquEprCompetCode",array(
						   "seleprequ",
						   "Epreuve...",
						   "SELECT EprCompetCode, EprLibelCourt, EprLibellé 
						   FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode RIGHT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
						   WHERE Groupes.GrLibelCourt >= 9 $CatPrimTrue AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet.$strCatEpr." ORDER BY Epreuves.Ordre",
						   Array("EprLibelCourt", "-","EprLibellé"),
						   Array("", "", ""),
						   "EprCompetCode",
						   "SELECT EprCompetCode FROM Epreuves INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode WHERE EprCompetCompetCode = ".$Compet." AND EprLibelCourt = ",
						   "",
						   "230"),
						   (($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)) ),
						   array("Texte" ,false,"","",false),
						   array("ListeS",($SpoGestion == -1) ,"",array("EquPromo", "Crit-Chal...",array('0'=>'Non','1'=>'Oui'), "", "", "", "","","60"),"",true,false),
   						   array("ListeS",(($SpoGestion == -4)||($SpoGestion == -7)),"",array("EquChall", "Chall...",$ArrayChall, "", "", "", "","","70"),"",true,false),
						   array("Texte" ,(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),"","",$relOblige),
						   array("Texte" ,true,"","",false),
						   array($Type{1}, true, "", $List{1}, $PasVide{1}),
						   array($Type{2}, true, "", $List{2}, $PasVide{2}),
						   array($Type{3}, true, "", $List{3}, $PasVide{3}),
						   array($Type{4}, true, "", $List{4}, $PasVide{4}),
						   array($Type{5}, true, "", $List{5}, $PasVide{5})
						   );
		$ChampsInsert    = array(
						   array("ListeD",true ,"EquEtabCode",array("seletabequ", "Etab...","SELECT EtabNum, EtabNomCourt, EtabNom, EtabVille FROM Etablissements ORDER BY EtabNum", Array("EtabNum", "-", "EtabNomCourt", "-", "EtabNom", "EtabVille"), Array("%06d", "", "", "","",""), "EtabNum","SELECT EtabNum FROM Etablissements WHERE EtabNomCourt = ","SELECT EtabCode FROM Etablissements WHERE EtabNum = ","200"),"$Etab",$Adm,true,false),
						   array("Texte" ,false,""             ,"","",false,false,false),
						   array("Texte" ,false,""             ,"","",false,false,false),
						   array("Texte" ,false,""             ,"","",true, false,false),
						   array("Texte" ,false,""             ,"","",false,false,false),
						   array("Texte" ,false,""             ,"","",false,false,false),
						   array("ListeD",$SpoGestion != -5,"EquCatCode",array("selcatequ", "Catégorie...",$CatPrim, Array("CatLibelCourt", "-", "CatLibellé"), Array("", "", ""), $CatChp, $CatPrimBis, "","130"), $CatInit, $SpoGestion != -4,true,false),
						   array("ListeD",(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7) || ($SpoGestion == -4)),"EquEprCompetCode",array(
							"seleprequ",
							"Epreuve...",
						   "SELECT EprCompetCode, EprLibelCourt, EprLibellé 
						    FROM Epreuves INNER JOIN Catégories ON Catégories.CatCode = Epreuves.EprCatCode INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode RIGHT JOIN Groupes ON Groupes.GrCode = Epreuves.EprGrCode
							WHERE Groupes.GrLibelCourt >= 9 $CatPrimTrue AND `Epreuves Compétitions`.EprCompetCompetCode = ".$Compet.$strCatEpr." ORDER BY Epreuves.Ordre",
							Array("EprLibelCourt", "-", "EprLibellé"),
							Array("", "", ""),
							"EprCompetCode",
							"",
							"",
							"230"),
							$EpreInit, (($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),False),
							array("Texte"  ,true ,"EquCompetCode","","$Compet",true,true,false),
							array("ListeS",($SpoGestion == -1) ,"",array("EquPromo", "Crit-Chal..."    ,array('0'=>'Non','1'=>'Oui'), "", "", "", "","","60"),"",($SpoGestion == -1),false,false),
							array("ListeS",(($SpoGestion == -4)||($SpoGestion == -7)) ,"",array("EquChall", "Chall...", $ArrayChall, "", "", "", "","","70"),"",(($SpoGestion == -4)||($SpoGestion == -7)),true,false),
							array("Texte" ,(($SpoGestion == -1) || ($SpoGestion == -5) || ($SpoGestion == -7)),"","","",true,$relOblige,false),
							array("Texte" ,true,"","","",true,false,false),
							array($Type{1},true,"",$List{1},"",true,$PasVide{1},false),
							array($Type{2},true,"",$List{2},"",true,$PasVide{2},false),
							array($Type{3},true,"",$List{3},"",true,$PasVide{3},false),
							array($Type{4},true,"",$List{4},"",true,$PasVide{4},false),
							array($Type{5},true,"",$List{5},"",true,$PasVide{5},false)
						);
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr);
		if ($SpoGestion == -3) { bf_mysql_query("UPDATE (Catégories INNER JOIN (Groupes INNER JOIN (Sports INNER JOIN (Equipes INNER JOIN Epreuves ON Equipes.EquCatCode = Epreuves.EprCatCode) ON Sports.SpoCode = Epreuves.EprSpoCode) ON Groupes.GrCode = Epreuves.EprGrCode) ON Catégories.CatCode = Epreuves.EprCatCode) INNER JOIN `Epreuves Compétitions` ON Epreuves.EprCode = `Epreuves Compétitions`.EprCompetEprCode SET Equipes.EquEprCompetCode = EprCompetCode WHERE Groupes.GrLibelCourt = 9 AND Sports.SpoGestionPerf = -3 AND Equipes.EquCompetCode = ".$Compet); }
		}
	}
	
	if (($menu == "connexions") && ($Adm)) {	
		$tablename  = "Connexions";
		$queryStr  = "SELECT DATE_FORMAT(Depart,'%d/%m/%Y %T') As Debut, Ip, IF(EtabNomCourt IS NULL,Id,EtabNomCourt) AS Utilisateur, TIMEDIFF(Now(),Depart) AS Duree, Session 
					  FROM `Connexions` LEFT JOIN `Etablissements` ON Connexions.Id = Etablissements.EtabNum";
		$where = "";
		if ((!isset($orderby)) || ($orderby == "")) $orderby = "Debut";
		$MaKey           = "Session";
		$NomsColonnes    = array('Début','Adresse IP','Utilisateur','Durée','Session');
		$ChampsTri       = array('Debut','','(IF(EtabNomCourt IS NULL,"Admin",EtabNomCourt))','','');
		$ChampsAli       = array('center','center','center','center','center');
		$ChampsFor       = array('','','','','');
		$ChampsAff       = array(true,true,true,true,false);
		$ChampsType      = array("Texte","Texte","Texte","Texte","Texte");
		$ChampsTypeExt   = array("","","","","");				   
		$ChampsFiltre    = array(false,true,false,false,false);
		$ChampsNomFil    = array("","","","","");
		$ChampsRacFiltre = array(false,true,false,false,false);
		$ChampsRacParam  = "";
		$Choix = array("filtrage");
		$Clause1 = ""; $Clause2 = "";
		$ChampsEdit      = "";
		$ChampsInsert    = "";
		$sousqueryStr    = "";
		GereData($tablename, $queryStr, $MaKey, $NomsColonnes, $ChampsTri, $ChampsAli, $ChampsFor, $ChampsAff, $action, $orderby, $Choix, $ChampsEdit, $ChampsInsert, $ChampsType, $ChampsTypeExt, $ChampsFiltre, $where, $ChampsNomFil, $ChampsRacFiltre, $ChampsRacParam, $sousqueryStr);
	}
	
	if ($menu == "options"){
	
		echo "<BR>";
		echo "<FORM ACTION='$PHP_SELF' METHOD='POST'>";
		
		if ($Adm) {
			echo "<TABLE CLASS = 'tableconopt'>";
			echo "<TR>";
			echo "<TD>";
			echo "&nbsp;&nbsp;&nbsp;Nombre de lignes par page : ";
			echo "<select size=1 name='LignesParPage' CLASS='listederoulante'>";
			for( $i = 1; $i < 1000; $i++ ) {
				if ($i > 50) $i = $i + 10-1;
				if ($i > 100) $i = $i + 100-10;
				echo "<option value='$i'";
				if ($_SESSION['LignesParPage'] == $i) echo " selected"; 
				echo "> $i </option>\n";
			}
			echo "<option value='999999'";
			if ($_SESSION['LignesParPage'] == 999999) echo " selected"; 
			echo "> Tout (...peut ralentir) </option>\n";
			echo "</select>";
			echo "&nbsp;<INPUT TYPE='submit' NAME='ValideLignesParPage' VALUE='Valider' class='bouton'>";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			echo "<BR>";
		}
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>";
		echo "<TD>";
		echo "&nbsp;&nbsp;&nbsp;Modèle de couleurs : ";
		echo "<select size=1 name='Couleur' CLASS='listederoulante'>";
		for( $i = 0; $i < count($Couleurs); $i++ ) {
			echo "<option value = '$i'"; 
			if ($_SESSION['Couleur'] == $i) echo " selected";
			echo "> ".$Couleurs[$i][0]."&nbsp; </option>\n";
		}
		echo "</select>";
		echo "&nbsp;<INPUT TYPE='submit' NAME='ValideCouleur' VALUE='Valider' class='bouton'>";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "<BR>";
		
	}	
	
	if (($menu == "outils")  && ($Adm)) {
		
		$clicbouton = false; if ( ($valideaccueil == "Valider") || ($validemaintenance == "Valider") || ($presupprimer == "Supprimer") || ($supprimer == "Supprimer") || ($valideBasesExternes == "Valider") || ($upload == "Restaurer") || ($exporter == "Sauvegarder") || ($validerequete == "Valider") || ($valideinscriptionlic == "Valider") || ($valideimpressionlic == "Valider")) $clicbouton = true;
	
		if (isset($valideaccueil)) {
			bf_mysql_query("UPDATE `Paramweb` SET `Accueil` = '".urlencode($accueil)."'"); 
		}
		$req = bf_mysql_query("SELECT `Accueil` FROM `Paramweb`"); 
		if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = urldecode($data["Accueil"]);} else $data = "";
		echo "<BR>";
		echo "<FORM method='post'>";
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> CONNEXION &nbsp;&nbsp;";
		echo "Message d'accueil &nbsp;&nbsp;";
		echo "<input name='valideaccueil' type='submit' id='valideaccueil' value='Valider' class='bouton'>";
		echo "<TEXTAREA name='accueil' rows='2'>".$data."</textarea><BR>";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
		
		echo "<HR CLASS = 'hr1'>\n";
		
		if (isset($validemaintenance)) {
			if ($optionmaintenance == "oui") $optionmain = 1; else $optionmain = 0;
			bf_mysql_query("UPDATE `Paramweb` SET `Maintenance` = '$optionmain'"); 
		}
		$req = bf_mysql_query("SELECT `Maintenance` FROM `Paramweb`"); 
		if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = $data["Maintenance"];} else $data = 0;
		
		echo "<FORM method='post'>";
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> MAINTENANCE &nbsp;&nbsp;";
		echo "Fermer le site&nbsp;&nbsp;";
		echo "<input type='radio' name='optionmaintenance' value='oui'";
		if (($data['Maintenance']) == 1) echo " checked='checked'";
		echo ">Oui&nbsp";
		echo "<input type='radio' name='optionmaintenance' value='non'";
		if (($data['Maintenance']) != 1) echo " checked='checked'";
		echo ">Non &nbsp;&nbsp;";
		echo "<input name='validemaintenance' type='submit' id='validemaintenance' value='Valider' class='bouton'>";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
		
		echo "<HR CLASS = 'hr1'>\n";
			
		echo "<FORM method='post'>";
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> EXPORTATION &nbsp;&nbsp;";
		echo "Exporter au format (pour Excel, testez csv1 et csv2) &nbsp;&nbsp;";
		echo "<input type='hidden' name='optionexport' value='exptout'>";
		echo "<input type='radio' name='optionexporttype' value='expcsv' >csv1 (Excel avec ,) &nbsp;";
		echo "<input type='radio' name='optionexporttype' value='expcs2' >csv2 (Excel avec ;) &nbsp;";
		echo "<input type='radio' name='optionexporttype' value='expsql' checked='checked'>ugw (Ug20xx)&nbsp;&nbsp;";
		echo "<input type='radio' name='optionexporttype' value='exptex' >txt (Texte) &nbsp;";
		echo "<input name='' type='submit' id='exporter' value='Exporter' class='bouton'>";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
		
		echo "<HR CLASS = 'hr1'>\n";
		
		if ($actionfichier == "confirmesupprimer") {
			if (strrchr($fichier,".") == '.ugw') @unlink($fichier);
			$actionfichier = "";
			$fichier = "";
		}
		
		echo "<FORM method='post'>";
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> SAUVEGARDE &nbsp;&nbsp;";
		echo "Sauvegarder sur le serveur &nbsp;&nbsp;";
		echo "<input type='hidden' name='optionexporttype' value='expser' checked='checked'>";
		echo "<input type='hidden' name='optionexport' value='exptout'>";
		if (TailleRep(".") > $QUOTA) { 
			echo "(Pour sauvegarder, supprimez les sauvegardes inutiles pour libérer de l'espace sur le serveur) <BR>";
		} else {
			echo "<input name='exporter' type='submit' id='exporter' value='Sauvegarder' class='bouton'>";
		}
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
		
		if ($actionfichier == "modifier") {
			if (strrchr($fichier,".") == '.ugw') {
				$journow = date('w', @filemtime($fichier));
				$fichiertab = explode(".",$fichier);
				if (strstr($fichier, 'Auto') == True) @rename($fichier, str_replace(" Auto".$journow." ", "", $fichier)); else @rename($fichier, $fichiertab[0]." Auto".$journow." .".$fichiertab[1]);
			}
			$actionfichier = "";
			$fichier = "";
		}
		
		$tabfile = RetourneFic (".", "Tout", "Auto", $TriFic);
		if (!($tabfile == 0)) {
			echo "<TABLE CLASS = 'tablecompets' style = 'Margin-top:0px;Margin-left:40px;Margin-bottom:10px;'>";
			for($i=0; $i < count($tabfile); $i++) {
					if ($i == 0) {
						echo "<CAPTION style='text-align:left; padding-bottom:4px;'>Sauvegardes trouvées sur le serveur : </CAPTION>";
						echo "<TR><TH>N°</TH>";
						echo "<TH>Type</TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Date>Date</a></TH>";
						echo "<TH>Heure</TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Taille>Taille</a></TH>";
						echo "<TH>Age</TH>";
						echo "<TH></TH></TR>";
					}
					echo "<TR CLASS='"; 
					if (($actionfichier == "supprimer") && ($tabfile[$i]["Nom"] == $fichier)) echo "trsuppr"; else if ( (round($i / 2) - ($i / 2)) == "0" ) echo "tr1"; else echo "tr2";
					echo "'>";
					echo "<TD ALIGN = 'center'>".($i+1)."</TD>";
					echo "<TD ALIGN = 'center'>".$tabfile[$i]["Type"]."</TD>";
					echo "<TD ALIGN = 'right'>".$tabfile[$i]["Date"]."</TD>";
					echo "<TD>".$tabfile[$i]["Heure"]."</TD>";
					echo "<TD>".$tabfile[$i]["Taille"]."</TD>";
					echo "<TD ALIGN = 'center'>".$tabfile[$i]["Age"]." Jr</TD>";
					echo "<TD>";
					if (($actionfichier == "supprimer") && ($tabfile[$i]["Nom"] == $fichier)) {
						echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&fichier=".stripslashes(rawurlencode($fichier))."&actionfichier=confirmesupprimer&TriFic=$TriFic>&nbsp;Confirmer la suppression&nbsp;</a>";
						echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=$TriFic>&nbsp;Annuler&nbsp;</a><BR>";
					} else {
						echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&fichier=".stripslashes(rawurlencode($tabfile[$i]["Nom"]))."&actionfichier=supprimer&TriFic=$TriFic>&nbsp;Supprimer&nbsp;</a>";
						echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils&fichier=".stripslashes(rawurlencode($tabfile[$i]["Nom"]))."&actionfichier=modifier&TriFic=$TriFic>&nbsp;Modifier&nbsp;</a>";
						echo "<a href=".stripslashes(rawurlencode($tabfile[$i]["Nom"])).">&nbsp;Télécharger&nbsp;</a><BR>";
					}
					echo "</TD>";
					echo "</TR>";
					$filenoms[$i] = $tabfile[$i]["Nom"];
					$filedes[$i]  = ($i+1)." - ".$tabfile[$i]["Date"]." à ".$tabfile[$i]["Heure"]." (".$tabfile[$i]["Taille"].")";
			}
			echo "</TABLE>";
		}			
		
		echo "<HR CLASS = 'hr1'>\n";
						
		echo "<form method='post' enctype='multipart/form-data'>";
		echo "<table CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> RESTAURATION &nbsp;&nbsp;";
		if (!($tabfile == 0)) {
			echo " Restaurer du serveur &nbsp;";
			listederoulante("ListeSauvegardes", "Sauvegardes...", $filedes, "", "", $filenoms, "", 230);
			echo " &nbsp; Ou &nbsp;";
		}
		echo " Restaurer le fichier &nbsp;";
		echo "<input type='hidden' name='MAX_FILE_SIZE' value='50000000'>";
		echo "<input name='userfile' type='file' id='userfile' size ='30'>"; 
		echo " &nbsp; &nbsp;";
		echo "<input name='upload' type='submit' id='upload' value='Restaurer' class='bouton'>";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
			
		$tmpName = "";
		if(isset($_POST['upload']) && ($ListeSauvegardes != "")) {
			$tmpName  = "./$ListeSauvegardes";
			$fileSize = filesize("./$ListeSauvegardes");
		}
		if(isset($_POST['upload']) && $_FILES['userfile']['size'] > 0) {
			$tmpName  = $_FILES['userfile']['tmp_name'];
			$fileSize = $_FILES['userfile']['size'];
		}	
				
		echo "<HR CLASS = 'hr1'>\n";
		
		echo "<FORM method='POST'>";
		echo "<TABLE CLASS = 'tableconopt'>";
		echo "<TR>"; 
		echo "<TD>";
		echo "> SUPPRESSION &nbsp;&nbsp; Supprimer &nbsp;&nbsp;";
		
		if ((($action == "supprime") || (isset($supprimer)))  ) {
			if ($optionsuppr == "supprtout"   ) $listetable = Array("Sports", "Etablissements", "Secteurs", "Licenciés", "Catégories", "Compétitions", "Epreuves", "Epreuves Compétitions", "Groupes", "Participations", "Equipes", "Tours Epreuves Compétitions", "Paramètres"); 
			if ($optionsuppr == "suppretab"   ) $listetable = array("Etablissements", "Secteurs", "Licenciés", "Participations", "Equipes");
			if ($optionsuppr == "supprlic"    ) $listetable = array("Licenciés", "Participations", "Equipes");
			if ($optionsuppr == "supprcompet" ) $listetable = Array("Compétitions", "Epreuves Compétitions", "Participations", "Equipes", "Tours Epreuves Compétitions"); 
			if ($optionsuppr == "supprpartic" ) $listetable = Array("Participations", "Equipes"); 
			$maconnec = bf_mysql_connect();
			$pTable = mysql_list_tables($BDD,$maconnec);
			if (is_array($listetable)) {
				$num    = count($listetable);
			} else {
				$pTable  = mysql_list_tables($BDD);	
				$num     = mysql_num_rows($pTable);
			}
			for($t = 0; $t < $num; $t++ ) {
				if (is_array($listetable)) {
					$tablename = $listetable[$t];
					if (is_array($tablename)) {$tablename =	$listetable[$t][0];}
				} else $tablename = mysql_tablename($pTable, $t);	
				if (is_array($listetable)) {
					$req = $listetable[$t];
					if (is_array($req)) $req = $listetable[$t][1]; else $req = "DELETE FROM `$tablename`";
				} else $req = "DELETE FROM `$tablename`";
				bf_mysql_query($req, 0 ,"`$tablename`");
				bf_mysql_query("ALTER TABLE `$tablename` AUTO_INCREMENT = 0", 0 ,"`$tablename`");
			}		
		}
		
		if ( (isset($presupprimer)) && (isset($optionsuppr)) ){ 
			$options = array('de tout'=>'supprtout','des établissements'=>'suppretab','des licenciés'=>'supprlic','des compétitions'=>'supprcompet');
			echo "Confirmer la suppression ".array_search($optionsuppr, $options)."&nbsp;&nbsp;";
			ConstruitZone(array(array("optionsuppr",$optionsuppr)));
			echo "<input name='supprimer' type='submit' id='supprimer' value='Supprimer' class='bouton'>";
		} else {
			echo "<input type='radio' name='optionsuppr' value='supprtout'   >Tout&nbsp;&nbsp;&nbsp;";
			echo "<input type='radio' name='optionsuppr' value='suppretab'   >Etablissements&nbsp;&nbsp;&nbsp;";
			echo "<input type='radio' name='optionsuppr' value='supprlic'    >Licenciés&nbsp;&nbsp;&nbsp;";
			echo "<input type='radio' name='optionsuppr' value='supprcompet' >Compétitions&nbsp;&nbsp;&nbsp;";
			echo "<input type='radio' name='optionsuppr' value='supprpartic' >Participations&nbsp;&nbsp;&nbsp;";
			echo "<input name='presupprimer' type='submit' id='presupprimer' value='Supprimer' class='bouton'>";
		}
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
		echo "</FORM>";
		
		echo "<HR CLASS = 'hr1'>\n";
		
		$tabfile = RetourneFic (".", "Compétition", "Comp", $TriFic);	
		if (!($tabfile == 0)) {
		
			echo "<TABLE CLASS = 'tableconopt' ID = 'enattente'>";
			echo "<TR>"; 
			echo "<TD>";
			echo "> COMPETITIONS EN ATTENTE &nbsp;&nbsp;";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
		
			echo "<TABLE CLASS = 'tablecompets' style = 'Margin-top:0px;Margin-left:40px;Margin-bottom:10px;'>";
				for($i=0; $i < count($tabfile); $i++) {
					if ($i == 0) {
						echo "<CAPTION style='text-align:left; padding-bottom:4px;'>Compétitions en attente trouvées sur le serveur : </CAPTION>";
						echo "<TR><TH>N°</TH>";
						echo "<TH>Type</TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Date#enattente>Date</a></TH>";
						echo "<TH>Heure</TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Taille#enattente>Taille</a></TH>";
						echo "<TH>Age</TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Ugsel#enattente>Ugsel</a></TH>";
						echo "<TH><a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=Sport#enattente>Sport</a></TH>";
						echo "<TH>Description</TH>";
						echo "<TH>Obs</TH>";
						echo "<TH></TH></TR>";
					}
					echo "<TR CLASS='"; 
					if (($actionfichier == "supprimer") && ($tabfile[$i]["Nom"] == $fichier)) echo "trsuppr"; else if ( (round($i / 2) - ($i / 2)) == "0" ) echo "tr1"; else echo "tr2";
					echo "'>";
					echo "<TD ALIGN = 'center'>".($i+1)."</TD>";
					echo "<TD ALIGN = 'center'>".$tabfile[$i]["Type"]."</TD>";
					echo "<TD ALIGN = 'right'>".$tabfile[$i]["Date"]."</TD>";
					echo "<TD>".$tabfile[$i]["Heure"]."</TD>";
					echo "<TD>".$tabfile[$i]["Taille"]."</TD>";
					echo "<TD ALIGN = 'center'>".$tabfile[$i]["Age"]." Jr</TD>";
					echo "<TD ALIGN = 'center'>".$tabfile[$i]["Ugsel"]."</TD>";
					echo "<TD>".$tabfile[$i]["Sport"]."</TD>";
					echo "<TD>".$tabfile[$i]["Description"]."</TD>";
					echo "<TD>".$tabfile[$i]["Obs"]."</TD>";
					echo "<TD>";
					if (($actionfichier == "supprimer") && ($tabfile[$i]["Nom"] == $fichier)) {
						echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&fichier=".stripslashes(rawurlencode($fichier))."&actionfichier=confirmesupprimer&TriFic=$TriFic#enattente>&nbsp;Confirmer la suppression&nbsp;</a>";
						echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils&TriFic=$TriFic#enattente>&nbsp;Annuler&nbsp;</a><BR>";
					} else {
						echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&fichier=".stripslashes(rawurlencode($tabfile[$i]["Nom"]))."&actionfichier=supprimer&TriFic=$TriFic#enattente>&nbsp;Supprimer&nbsp;</a>";
						echo "<a href=".stripslashes(rawurlencode($tabfile[$i]["Nom"])).">&nbsp;Télécharger&nbsp;</a><BR>";
					}
					echo "</TD>";
					echo "</TR>";
					$filenoms[$i] = $tabfile[$i]["Nom"];
					$filedes[$i]  = $tabfile[$i]["Date"]." à ".$tabfile[$i]["Heure"]." (".$tabfile[$i]["Taille"].")";
				}
			echo "</TABLE>";
		
			echo "<HR CLASS = 'hr1'>\n";
		
		}
		if (isset($valideBasesExternes)) {
			bf_mysql_query("UPDATE `Paramweb` SET `BasesExternes` = '$basesexternes'"); 
		}
		
		$tabrep = RetourneRep ("../", "ud");
		if (!($tabrep == 0)) {
			$req = bf_mysql_query("SELECT `BasesExternes` FROM `Paramweb`"); 
			if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = $data["BasesExternes"];} else $data = "";
			echo "<FORM method='post'>";
			echo "<TABLE CLASS = 'tableconopt'>";
			echo "<TR>"; 
			echo "<TD>";
			echo "> INSCRIPTION &nbsp;&nbsp;";
			echo "Rechercher les licenciés introuvables dans les bases externes (noms séparés par des points virgules) &nbsp;&nbsp;";
			echo "<input name='valideBasesExternes' type='submit' id='valideBasesExternes' value='Valider' class='bouton'>";
			echo "<TEXTAREA name='basesexternes' rows='1'>".$data."</textarea><BR>";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			echo "</FORM>";
		
			echo "<HR CLASS = 'hr1'>\n";
		
			echo "<TABLE CLASS = 'tableconopt' ID = 'miseajour'>";
			echo "<TR>"; 
			echo "<TD>";
			echo "> MISE A JOUR &nbsp;&nbsp;";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			echo "<TABLE CLASS = 'tablecompets' style = 'Margin-top:0px;Margin-left:40px;Margin-bottom:10px;'>";
				for($i=0; $i < count($tabrep); $i++) {
					if ($i == 0) {
						echo "<CAPTION style='text-align:left; padding-bottom:4px;'>Ugsel affiliées trouvées sur le serveur : </CAPTION>";
						echo "<TR><TH>N°</TH>";
						echo "<TH>Ugsel</TH>";
						echo "<TH>Licenciés</TH>";
						echo "<TH>En interne</TH>";
						echo "<TH>";
						if (($actionfichier == "importerugsel") && ($ugselimp == 'tout') ) {
							echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&ugselimp=tout&actionfichier=confirmeimporterugsel#miseajour>&nbsp;Confirmer l'importation&nbsp;</a>";
							echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils#miseajour>&nbsp;Annuler&nbsp;</a><BR>";
						} else echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils&ugselimp=tout&actionfichier=importerugsel#miseajour>&nbsp;Importer tout&nbsp;</a>";
						echo "</TH></TR>";
					}
					echo "<TR CLASS='"; 
					if (($actionfichier == "importerugsel") && (($tabrep[$i]["Nom"] == $ugselimp) || ($ugselimp == 'tout'))) echo "trsuppr"; else if ( (round($i / 2) - ($i / 2)) == "0" ) echo "tr1"; else echo "tr2";
					echo "'>";
					if ($tabrep[$i]["Bdd"] != "*") {
						echo "<TD ALIGN = 'center'>".($i+1)."</TD>";
						echo "<TD ALIGN = 'center'>".$tabrep[$i]["Nom"]."</TD>";
						echo "<TD ALIGN = 'center'>".$tabrep[$i]["Lic Externe"]."</TD>";
						echo "<TD ALIGN = 'center'>".$tabrep[$i]["Lic Interne"]."</TD>";
						echo "<TD ALIGN = 'center'>";
						if (($actionfichier == "importerugsel") && (($tabrep[$i]["Nom"] == $ugselimp)) ) {
							echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&ugselimp=".stripslashes(rawurlencode($tabrep[$i]["Nom"]))."&actionfichier=confirmeimporterugsel#miseajour>&nbsp;Confirmer l'importation&nbsp;</a>";
							echo "<a href=$PHP_SELF?action=VoirMenu&menu=outils#miseajour>&nbsp;Annuler&nbsp;</a><BR>";
						} else {
							echo " <a href=$PHP_SELF?action=VoirMenu&menu=outils&ugselimp=".stripslashes(rawurlencode($tabrep[$i]["Nom"]))."&actionfichier=importerugsel#miseajour>&nbsp;Importer&nbsp;</a>";
						}
						echo "</TD>";
					}
					
					echo "</TR>";
				}
			echo "</TABLE>";
		
			echo "<HR CLASS = 'hr1'>\n";
		}
		
		if ($LICENCES == "Oui") {
			if (isset($valideimpressionlic)) {
				bf_mysql_query("UPDATE `Paramweb` SET `ImpressionLic` = '$optionimpressionlic', `AssUgsel` = '$optionimpressionlicAss'"); 
			}
			$req = bf_mysql_query("SELECT `ImpressionLic`, `AssUgsel` FROM `Paramweb`"); 
			if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data1 = $data["ImpressionLic"];$data2 = $data["AssUgsel"];} else { $data1 = 0; $data2 = "";}
			echo "<FORM method='post'>";	
			echo "<TABLE CLASS = 'tableconopt'>"; 
			echo "<TR><TD>";
			echo "> IMPRESSION EN LIGNE DES LICENCES &nbsp;&nbsp;";
			echo "<input type='radio' name='optionimpressionlic' value='0'";
			if (($data['ImpressionLic']) == 0) echo " checked='checked'";
			echo ">Ne pas autoriser &nbsp&nbsp;";
			echo "<input type='radio' name='optionimpressionlic' value='1'";
			if (($data['ImpressionLic']) == 1) echo " checked='checked'";
			echo ">Autoriser tout le monde &nbsp&nbsp;";
			echo "<input type='radio' name='optionimpressionlic' value='2'";
			if (($data['ImpressionLic']) == 2) echo " checked='checked'";
			echo ">Autoriser seulement les administrateurs &nbsp&nbsp;";
			echo "Assurance Ugsel <input type='text' size='8' name='optionimpressionlicAss' value='".$data2."'> &nbsp; ";
			echo "<input name='valideimpressionlic' type='submit' id='valideimpressionlic' value='Valider' class='bouton'>";
			echo "</TD></TR></TABLE></FORM>";
		
			echo "<HR CLASS = 'hr1'>\n";
		}
		
			if (isset($valideinscriptionlic)) {
				bf_mysql_query("UPDATE `Paramweb` SET `InscriptionLic` = '$optioninscriptionlic'"); 
			}
			$req = bf_mysql_query("SELECT `InscriptionLic` FROM `Paramweb`"); 
			if ((!(!$req)) && (mysql_num_rows($req) > 0)) {$data = mysql_fetch_assoc($req); $data = $data["InscriptionLic"];} else $data = 0;
			echo "<FORM method='post'>";
			echo "<TABLE CLASS = 'tableconopt'>"; 
			echo "<TR><TD>";
			echo "> INSCRIPTION EN LIGNE DES 'Non Inscrits' &nbsp;&nbsp;";
			echo "<input type='radio' name='optioninscriptionlic' value='0'";
			if (($data['InscriptionLic']) == 0) echo " checked='checked'";
			echo ">Ne pas autoriser &nbsp&nbsp;";
			echo "<input type='radio' name='optioninscriptionlic' value='2'";
			if (($data['InscriptionLic']) == 2) echo " checked='checked'";
			echo ">Autoriser avec validation automatique &nbsp;&nbsp;";
			echo "<input type='radio' name='optioninscriptionlic' value='1'";
			if (($data['InscriptionLic']) == 1) echo " checked='checked'";
			echo ">Autoriser avec validation des administrateurs &nbsp;&nbsp;";
			echo "<input name='valideinscriptionlic' type='submit' id='valideinscriptionlic' value='Valider' class='bouton'>";
			echo "</TD></TR></TABLE></FORM>";
		
			echo "<HR CLASS = 'hr1'>\n";
		
		
		echo "<TABLE CLASS = 'tableconopt'>"; 
		echo "<TR><TD>";
		echo "> INFO SITE &nbsp;&nbsp;";
		echo $BDD." : &nbsp;";
		
		PurgeTables();
		
		$mesinfo = array("Secteurs","Etablissements","Licenciés","Sports","Catégories","Epreuves","Compétitions","Participations","Equipes");
		for ($i = 0; $i < count($mesinfo); $i++) {	
			if (is_array($mesinfo[$i])) $manomtable = $mesinfo[$i][0]; else $manomtable = $mesinfo[$i];
			if (is_array($mesinfo[$i])) $matable = $mesinfo[$i][1]; else $matable = $mesinfo[$i];
			echo CompteEnr($matable)." ".$manomtable;
			if ($i < count($mesinfo)-1) echo " / ";
		}
		echo "</TD></TR></TABLE>";
	
		$taillerep = TailleRep(".");
		if ($taillerep > 0) {
			echo "<HR CLASS = 'hr1'>\n";
			echo "<TABLE CLASS = 'tableconopt'>"; 
			echo "<TR><TD>";
			echo "> ESPACE SUR LE SERVEUR &nbsp;&nbsp;";
			if ($taillerep > $QUOTA) echo " <BLINK> Supprimez des fichiers ! </BLINK>";
			echo ConvertTaille($taillerep)." d'espace occupé sur un total disponible de ".ConvertTaille($QUOTA);
			echo " (". sprintf('%.2f ',$taillerep / $QUOTA * 100) ."%).";
			echo "</TD></TR></TABLE>";
		}
		
		if ( ($REQUETES == "Oui") ) {
			if (isset($validerequete)) bf_mysql_query($requete); 
			echo "<HR CLASS = 'hr1'>\n";
			echo "<FORM method='post'>";
			echo "<TABLE CLASS = 'tableconopt'>";
			echo "<TR>"; 
			echo "<TD>";
			echo "> REQUETE &nbsp;&nbsp;";
			echo "Requête &nbsp;&nbsp;";
			echo "<input name='validerequete' type='submit' id='validerequete' value='Valider' class='bouton'>";
			echo "<TEXTAREA name='requete' rows='3'>";
			if (!(isset($requete))) echo "UPDATE Etablissements INNER JOIN Secteurs ON Etablissements.EtabSecCode = Secteurs.SecCode SET EtabMemo3 = IF(RAND() > 0.33, IF(RAND() > 0.66, CONCAT(SecLibel, LOWER(EtabNomCourt), FLOOR(RAND()*100)), CONCAT(LOWER(EtabNomCourt), SecLibel, FLOOR(RAND()*100))), CONCAT(FLOOR(RAND()*100), LOWER(EtabNomCourt), SecLibel)) WHERE EtabMemo3 = '' OR EtabMemo3 IS NULL"; else echo $requete;
			echo "</textarea><BR>";
			echo "</TD>";
			echo "</TR>";
			echo "</TABLE>";
			echo "</FORM>";
		} 
		
		echo "<BR>";
	}
	
	if ($menu == "apropos"){
		echo "<BR>";
		echo "<TABLE CLASS = 'tableconopt'>"; 
		echo "<TR><TD><B> &nbsp; &nbsp UGSEL Web </B></TD></TR></TABLE>";
		echo "<TABLE CLASS = 'tableconopt'>"; 
		echo "<TR><TD> &nbsp; &nbsp; &nbsp;  Version  : $VERSION </TD></TR>";
		echo "<TR><TD> &nbsp; &nbsp; &nbsp;  Propulsion : <a TARGET='_blank' href=http://www.ugsel.org> Ugsel Nationale</a></TD></TR>";
		echo "<TR><TD> &nbsp; &nbsp; &nbsp;  Optimisation de la navigation : <a TARGET='_blank' href=http://www.mozilla-europe.org/fr/firefox> FireFox</a></TD></TR>";
		if (!($Consult)) echo "<TR><TD> &nbsp; &nbsp; &nbsp;  Documentation : <a TARGET='_blank' href='".$ADRSITE."/UgselWeb-Documentation.pdf#pagemode=bookmarks&zoom=100'> Cliquez ici </a></TD></TR>";
		if ($Adm) echo "<TR><TD> &nbsp; &nbsp; &nbsp;  Documentation Administrateurs : <a TARGET='_blank' href='".$ADRSITE."/UgselWeb-Documentation-Admin.pdf#pagemode=bookmarks&zoom=100'> Cliquez ici </a></TD></TR>";
		echo "<TR><TD></TD></TR>";
		echo "</TABLE>";
		echo "<BR>";
		
		if ($Adm) {
			echo "<TABLE CLASS = 'tableconopt'><TR><TD><B><BLINK> &nbsp; &nbsp INFORMATION </BLINK></B></TD></TR></TABLE>";
			echo "<HR CLASS = 'hr1'>\n";
			echo "<TABLE CLASS = 'tableconopt'>"; 
			echo "<TR><TD ALIGN = 'Center' VALIGN = 'Top' style ='white-space:nowrap;'>1 Sept 2012</TD><TD>L'adresse du serveur a changé : <B>ugselweb.org</B> (et non plus : ugsel-bretagne.org). <BR>Pensez à modifier les liens de vos sites internet.</TD></TR>";
			echo "</TABLE>";
			echo "<HR CLASS = 'hr1'>\n";
		}
	}
}

	global $PHP_SELF;
	global $BDD, $HOSTNAME, $UTILISATEUR, $MDP, $CONSULTATION, $Consult, $ENTRER, $UGSELNOM;
	global $optionexporttype;
	
	session_start();
	getTime();
	
	if(!empty($_GET)) extract($_GET);
	if(!empty($_POST)) extract($_POST);
	if ((isset($_GET['Adm'])) || (isset($_POST['Adm']))) logout();
	
	if (isset($valideEditCouleurs)) {
		for( $i = 1; $i < 17; $i++ ) {
			$Couleurs[3][$i] = $_POST['nomCouleur'.$i];
		}
	}

	if (isset($_COOKIE["ugselweb"])){
		if (isset($_COOKIE["ugselweb"]['LIGNES_PAR_PAGE'])) $LIGNES_PAR_PAGE = $_COOKIE['ugselweb']['LIGNES_PAR_PAGE']; 
		if (isset($_COOKIE["ugselweb"]['TAILLE']))          $TAILLE          = $_COOKIE['ugselweb']['TAILLE'];
		if (isset($_COOKIE["ugselweb"]['COULEUR']))         $COULEUR         = $_COOKIE['ugselweb']['COULEUR'];
		if (isset($_COOKIE["ugselweb"]['SON']))             $SON             = $_COOKIE['ugselweb']['SON'];
	} else {
		setcookie("ugselweb[LIGNES_PAR_PAGE]", "$LIGNES_PAR_PAGE", time() + 3600*24*365); 
		setcookie("ugselweb[TAILLE]",          "$TAILLE"         , time() + 3600*24*365); 
		setcookie("ugselweb[COULEUR]",         "$COULEUR"        , time() + 3600*24*365); 
		setcookie("ugselweb[SON]",             "$SON"            , time() + 3600*24*365); 
	}
	
	if (isset($_SESSION['LignesParPage'])) $LIGNES_PAR_PAGE = $_SESSION['LignesParPage'];
	if (isset($_SESSION['Son'])) $SON = $_SESSION['Son'];
	
	if (isset($ValideLignesParPage)) {
		$LIGNES_PAR_PAGE = $LignesParPage;
		$_SESSION['LignesParPage'] = $LIGNES_PAR_PAGE; 
		if (isset($_COOKIE["ugselweb"]['LIGNES_PAR_PAGE'])) setcookie("ugselweb[LIGNES_PAR_PAGE]", "$LignesParPage", time() + 3600*24*365); 
	}
	if (isset($ValideCouleur)) {
		$COULEUR = $Couleur;
		$_SESSION['Couleur'] = $COULEUR; 
		if (isset($_COOKIE["ugselweb"]['COULEUR'])) setcookie("ugselweb[COULEUR]", "$Couleur", time() + 3600*24*365); 
	}
	
	if (isset($ValideSon)) {
		$SON = $Son;
		$_SESSION['Son'] = $SON; 
		if (isset($_COOKIE["ugselweb"]['SON'])) setcookie("ugselweb[SON]", "$Son", time() + 3600*24*365); 
		JoueSon("sonok.wav");
	}
	
	$tailles = array("6pt","7pt","8pt","9pt","10pt","11pt","12pt","13pt","14pt","15pt","16pt","17pt");
	if (isset($_SESSION['Taille'])) $TAILLE  = $_SESSION['Taille'];
	if (isset($modiftaille)) {
		$TAILLE = $modiftaille;
		if ($TAILLE == -1) $TAILLE = intval(count($tailles)/2);
		if ($TAILLE < 1) $TAILLE = 1;
		if ($TAILLE > (count($tailles)-1)) $TAILLE = count($tailles)-1;
		$_SESSION['Taille'] = $TAILLE;
		if (isset($_COOKIE["ugselweb"]['TAILLE'])) setcookie("ugselweb[TAILLE]", "$TAILLE", time() + 3600*24*365); 
	}
	$taille    = $tailles[$TAILLE];
	$tailleinf = $tailles[($TAILLE - 1)];
	
	if (isset($_SESSION['Couleur'])) $COULEUR  = $_SESSION['Couleur'];
		
	if ($CONSULTATION != "Non") {
		$_SESSION['login']  = "Consultant";
		$_SESSION['log  ']  = $BDD;
		$_SESSION['LignesParPage']  = $LIGNES_PAR_PAGE;
		$_SESSION['Couleur']= $COULEUR;
		$_SESSION['Son']    = $SON;
		if ($ENTRER == " Entrer ") $action = "VoirMenu";
		if (!(isset($action)) || empty($action) || $action == "") $action = "";
	} else {
		if (!(isset($action)) || empty($action) || $action == "") $action = "logon";
	}
	
	if ($action == "logon") logon();
	else if ($action == "logout") {
			bf_mysql_query('DELETE FROM Connexions where Session = "'.Session_id().'"');
			logout();
		}
		else if ( $action == "Connexion" ) logon_submit();
			else if (( isset($_SESSION['login']) && ($_SESSION['log  '] == $BDD)) || ($CONSULTATION != "Non")) {
				if ($CONSULTATION != "Non") { 
					$Adm = false;
					$Consult = true;
					$Etab = 0;
					MajConnexions("Consultant");
				} else {
					$Consult = false;
					if ($_SESSION['login'] == "Admin") {
						$Adm = true;
						$Etab = "";
						MajConnexions("Admin");
					} else {
						$Adm = false;
						$Etab = $_SESSION['login'];
						MajConnexions($Etab);
					}
				}
				
				if (($TRANSFERT_DONNEES == "Url") && (isset($par))) extract(unserialize(urldecode(stripslashes($par))));
				if (($TRANSFERT_DONNEES == "Bdd") && (isset($par))){
					$req = bf_mysql_query('SELECT Param FROM Connexions WHERE Session = "'.session_id().'"');
					if (!(!$req)) {
						$data = mysql_fetch_array($req);
						extract(unserialize(urldecode(stripslashes($data["Param"])))) ; 
					}
				}
				
				if (!empty($_GET)) extract($_GET);
				if (!empty($_POST['ListeSport'])) $ListeSport = $_POST['ListeSport'];
				if ($Consult) $message = "";
				
				if ( ($CONSULTATION == "Non") && (($_GET["action"] == "exporte") || (isset($exporter)))) {
					if (($optionexporttype == "expser") || ($optionexporttype == "expsqlser") ) {
						debut_html(($CONSULTATION == "Non"));
						VoirMenu();
						fin_html(($CONSULTATION == "Non"));
					}
				} else {
					debut_html(($CONSULTATION == "Non"));
					VoirMenu();
					fin_html(($CONSULTATION == "Non"));
				}
			
			} else logout("Veuillez vous reconnecter.");
?>