<?php 
	global $UGSELREG, $UGSELDEP, $NbDep, $i, $LogoReg, $LogoDep; 
	$NbDep = Count($UGSELDEP);
	if(file_exists("../_logos/logo".$UGSELREG[0].".gif")) $LogoReg = $UGSELREG[0]; else	$LogoReg = "un";
	if ($UGSELREG[2] == 0) $UGSELREG[2] = 130; if ($UGSELREG[3] == 0) $UGSELREG[3] = 150;
	if ($UGSELREG[4] == "") $UGSELREG[4] = "www.ugsel.org";
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>
<html>
<head>
<title>UGSEL Web <?php echo $UGSELREG[1]; ?></title>
<Meta http-equiv='content-type' content='text/html;charset=ISO-8859-1'>
<link rel="shortcut icon" type="image/x-icon" href="http://ugselweb.org/favicon.ico" />
<link rel="icon" type="image/png" href="http://ugselweb.org/favicon.png" />
<style type='text/css'>
body {margin: 8%; font-family: verdana, arial; font-size: 11pt; color: #000080;}
table {border-collapse: separate; font-size: 11pt;} 
table th {padding:5px;background-color: #C0DBEC; font-weight:normal;} 
table td {padding:4px;} 
.tabledeb,.tablefin {width: 100%; border-collapse: collapse;} 
.trdeb,.trfin {background-color: #000080; color:white; font-size: 9pt;} 
.hr1,.hr2 {color: #000080; height: 1px; border: 0px; width: 100%} 
.tddetail {height: 40px; width:50px; padding:0px; font-size: 12pt; white-space: nowrap;}
a:link {text-decoration:none;} 
a:visited {text-decoration:none;}
a:hover {text-decoration:none; background-color:#000080; color:#f0f8ff;} 
.retour {color:White;} 
.retour:hover {background-color:#000080; color:White}
a:active {text-decoration:none; background-color:#f0f8ff; color:#f0f8ff;}
a img {border: none;}  
a.img-lien:hover{background-color:#f0f8ff; border:none;}
</style>
</head>
<body>
<TABLE class = 'tabledeb'>
	<TR CLASS = 'trdeb'>
		<TD Width = '90%'>&nbsp; UGSEL Web &nbsp;&nbsp; Base de lancement &nbsp;&nbsp; <?php echo $UGSELREG[1]; ?> </TD>
		<TD Width = '10%' align = 'center'> <A CLASS ="retour" href="../"> Retour </A></TD>
	</TR>
</TABLE>
<table bgcolor='aliceblue' bordercolor='darkblue' bordercolordark = 'darkblue' bordercolorlight = 'darkblue' border='2' rules ='none' cellpadding='0' cellspacing='0' width='100%' height='80%'>
	<tr><td Height = '10px' Colspan = 4></td></tr>		
	<tr><td Width = '35%' Rowspan = '<?php echo ($NbDep+2) ?>' Align = 'Center' Style = 'padding:0px 20px 0px 20px;'><font size = '4'><b>Région <?php echo $UGSELREG[1];?></b></font><br>
	<br>&nbsp; &nbsp;<a href="http://<?php echo $UGSELREG[4];?>" class="img-lien"><img src="../_logos/logo<?php echo $LogoReg;?>.gif" height="<?php echo 130 //$UGSELREG[2];?>" width="<?php echo 130 //$UGSELREG[3];?>" alt =''>&nbsp;</a>
	<br><br><i><b>Bienvenue dans UGSEL Web<br><br>L'espace d'inscription aux compétitions Ugsel</b><Font size = '2'><br><br><br>Accédez à l'Ugsel départementale de votre choix en cliquant sur son nom...</FONT></I>
	</td><td Class = 'tddetail'>&nbsp;</td> <td Class = 'tddetail'>&nbsp;</td> <td Class = 'tddetail'>&nbsp;</td></tr>
	<?php for ($i = 0; $i < $NbDep; $i++){
		//if(file_exists("../_logos/logo".$UGSELDEP[$i][0].".gif")) {$LogoDep = $UGSELDEP[$i][0];if ($UGSELDEP[$i][2] == 0) $UGSELDEP[$i][2] = 50;if ($UGSELDEP[$i][3] == 0) $UGSELDEP[$i][3] = 50;} else {$LogoDep = "un";$UGSELDEP[$i][2] = 45;$UGSELDEP[$i][3] = 50;}
		$LogoDep = "un";$UGSELDEP[$i][2] = 30;$UGSELDEP[$i][3] = 30;
		echo "<tr><td Class = 'tddetail' Width = '5%' Align = 'RIGHT'> <img src = '../_logos/logo".$LogoDep.".gif'  height='".$UGSELDEP[$i][2]."' width='".$UGSELDEP[$i][3]."' alt =''></td>
		<td Class = 'tddetail' Width = '55%'>  "; 
		if(file_exists("../".$UGSELREG[0]."/ud".$UGSELDEP[$i][0])) 
			echo "<a href='../".$UGSELREG[0]."/ud".$UGSELDEP[$i][0]."/inscriptions'>Ugsel ".$UGSELDEP[$i][0]."  ".strtr($UGSELDEP[$i][1]," "," ")." </a>";
			else echo "<i>Ugsel ".$UGSELDEP[$i][0]." - ".strtr($UGSELDEP[$i][1]," "," ")."</i>";
		echo "</td><td Class = 'tddetail' Width = '5%' >&nbsp;</td></tr>\n";
	}?>
	<tr><td Class = 'tddetail'>&nbsp;</td> <td Class = 'tddetail'>&nbsp;</td><td Class = 'tddetail'>&nbsp;</td></tr>
	<tr><td Height = '10px' Colspan = 4></td></tr>	
</table> 
<TABLE class = 'tablefin'>
	<TR CLASS = 'trfin'>
		<TD Width = '90%' >&nbsp; UGSEL Web &nbsp;&nbsp; Base de lancement &nbsp;&nbsp; <?php echo $UGSELREG[1]; ?> </TD>
		<TD Width = '10%' Align ='Center' ><A CLASS ="retour" href="../"> Retour </A></TD>
	</TR>
</TABLE>
</body>
</html>
