<?php
	require('./include/core/common.php');
	require(PATHS_INCLUDE . 'libraries/profile.lib.php');
	$ui_options['menu_path'] = array('hamsterpaj');
	require(PATHS_INCLUDE . 'traffa-definitions.php');

	$ui_options['javascripts'][] = 'zip_codes.js';
	$ui_options['javascripts'][] = 'settings.js';
	
	$ui_options['stylesheets'][] = 'user_profile.css';
	$ui_options['stylesheets'][] = 'settings.css';
	$ui_options['stylesheets'][] = 'profile_themes/all_themes.php';
	$ui_options['stylesheets'][] = 'rounded_corners_tabs.css';
	
	$ui_options['title'] = 'Ändra dina inställningar på hamsterpaj.net';
	ui_top($ui_options);
	
	if(login_checklogin() != 1)
	{
		jscript_alert('Du måste vara inloggad för att komma åt denna sidan!');
		jscript_location('/');
		die();
	}
	
	if($_GET['action'] == 'perform_changes')
	{
		switch($_GET['type'])
		{
		case 'forum_preferences':
			$newdata['preferences']['forum_enable_smilies'] = ($_POST['forum_enable_smilies'] == 0) ? 0 : 1;
			$newdata['preferences']['forum_subscribe_on_create'] = ($_POST['forum_subscribe_on_create'] == 0) ? 0 : 1;
			$newdata['preferences']['forum_subscribe_on_post'] = ($_POST['forum_subscribe_on_post'] == 1) ? 1 : 0;
		break;
		case 'hetluft_settings':
			$newdata['preferences']['enable_hetluft'] = ($_POST['enable_hetluft'] == 1) ? 1 : 0;
			$newdata['userinfo']['occupation'] = (is_numeric($_POST['occupation'])) ? $_POST['occupation'] : 0;
			
			/* Find lifestyles */
			unset($_SESSION['lifestyles']);
			foreach(array_keys($lifestyles) AS $lifestyle)
			{
				if($_POST['lifestyle_' . $lifestyle] == 'true' && count($new_lifestyles) <= 3)
				{
					$new_lifestyles[] = $lifestyle;
					$_SESSION['lifestyles'][] = $lifestyle;
				}
				
				$query = 'DELETE FROM user_lifestyles WHERE user = "' . $_SESSION['login']['id'] . '"';
				mysql_query($query) or die(report_sql_error($query, __FILE__, __LINE__));
				
				foreach($new_lifestyles AS $lifestyle)
				{
					$query = 'INSERT INTO user_lifestyles (user, lifestyle) VALUES("' . $_SESSION['login']['id'] . '", "' . $lifestyle . '")';
					mysql_query($query) or die(report_sql_error($query, __FILE__, __LINE__));
				}
			}

		break;
		case 'optional_info':
			$newdata['userinfo']['gender'] = NULL;
			if($_POST['gender'] == 'm' || $_POST['gender'] == 'f')
			{
				$newdata['userinfo']['gender'] = $_POST['gender'];
			}
			$newdata['userinfo']['birthday'] = NULL;			
			if(is_numeric($_POST['birth_year'])  && is_numeric($_POST['birth_month']) && is_numeric($_POST['birth_day']))
			{
				$newdata['userinfo']['birthday'] = (($_SESSION['login']['id'] == 827889) ? '2000-04-15' : $_POST['birth_year']) . '-' . $_POST['birth_month'] . '-' . $_POST['birth_day'];				
			}
			$zip_code = str_replace(' ', '', $_POST['zip_code']);
			$newdata['userinfo']['zip_code'] = is_numeric($zip_code) ? $zip_code : 0;
			if(is_numeric($zip_code))
			{
				$query = 'SELECT x_rt90, y_rt90 FROM zip_codes WHERE zip_code = "' . $zip_code . '" LIMIT 1';
				$result = mysql_query($query) or die(report_sql_error($query, __FILE__, __LINE__));
				$data = mysql_fetch_assoc($result);
				$_SESSION['userinfo']['x_rt90'] = $data['x_rt90'];
				$_SESSION['userinfo']['y_rt90'] = $data['y_rt90'];
			}

			$newdata['userinfo']['cell_phone'] = $_POST['cell_phone'];

			$newdata['userinfo']['contact1'] = null;
			if($_POST['contact1_medium'] != 'null')
			{
				$newdata['userinfo']['contact1'] = htmlspecialchars($_POST['contact1_medium']) . ':' . htmlspecialchars($_POST['contact1_handle']);
			}
			$newdata['userinfo']['contact2'] = null;
			if($_POST['contact2_medium'] != 'null')
			{
				$newdata['userinfo']['contact2'] = htmlspecialchars($_POST['contact2_medium']) . ':' . htmlspecialchars($_POST['contact2_handle']);
			}
			$newdata['traffa']['firstname'] = htmlspecialchars($_POST['firstname']);
		break;
		case 'password':
			if(sha1(utf8_decode($_POST['password_old']) . PASSWORD_SALT) != $_SESSION['login']['password_hash'])
			{
				jscript_alert('Det där går inte, du måste skriva in ditt nuvarande lösenord, annars funkar inte skiten. Seså, gör om gör rätt!');
				jscript_go_back();
				exit;
			}
			if($_POST['password_new'] != $_POST['password_verify'])
			{
				jscript_alert('"Nytt lösenord" och "Upprepa nytt lösenord" måste ju vara samma, annars funkar det ju inte :(');
				jscript_go_back();
				exit;
			}
			$newdata['login']['password_hash'] = sha1(utf8_decode($_POST['password_new']) . PASSWORD_SALT);
		break;
		}
		login_save_user_data($_SESSION['login']['id'], $newdata);
		session_merge($newdata);
		jscript_alert('Ändrat, fixat och donat :)');
		jscript_location($_SERVER['PHP_SELF']);
	}
	
	if($_POST['action'] == 'profile_theme')
	{
		$query = 'UPDATE userinfo SET profile_theme = "' . $_POST['theme'] . '" WHERE userid = "' . $_SESSION['login']['id'] . '" LIMIT 1';
		mysql_query($query) or report_sql_error($query);
		$_SESSION['userinfo']['profile_theme'] = $_POST['theme'];
	}

	echo '<h1>Inställningssidan (OBS! ej i användning längre, gå till <a href="/installningar/generalsettings.php">/installningar/generalsettings.php</a> istället.</h1>';
?>
	<a href="/avatar-settings.php">Byt visningsbild</a>
	<a href="/traffa/profile_presentation_change.php">Ändra presentation</a>
	<a href="/changename.php">Byt namn</a>
	<a href="/unregister.php">Ta bort konto</a>
	<!-- GAMMAL <a href="/traffa/userblocks.php">Blockeringar</a> -->

<?php


/* Frivillig information */
	echo '<a name="optional_info"></a>';
	/* echo '<div class="grey_faded_div">'; */
	
	rounded_corners_tabs_top();
		

	echo '<h2 style="margin-top: 0px;">Frivillig information</h2>' . "\n";
	echo '<form action="' . $_SERVER['PHP_SELF'] . '?action=perform_changes&type=optional_info" method="post" name="optional_info">';
	echo '<strong>Är du flicka eller pojke?</strong><br />' . "\n";
	echo '<input type="radio" name="gender" value="m" ';
	if($_SESSION['userinfo']['gender'] == 'm')
	{
		echo 'checked="1" ';
	}
	echo '/> Pojke&nbsp;&nbsp;';
	echo '<input type="radio" name="gender" value="f" ';
	if($_SESSION['userinfo']['gender'] == 'f')
	{
		echo 'checked="1" ';
	}
	echo '/> Flicka&nbsp;&nbsp;';
	echo '<input type="radio" name="gender" value="unknown" ';
	if($_SESSION['userinfo']['gender'] != 'm' && $_SESSION['userinfo']['gender'] != 'f')
	{
		echo 'checked="1" ';
	}
	echo '/> Hemligt<br /><br />';
	echo '<strong>När fyller du år?</strong><br />';
	$userbirthday = explode('-', $_SESSION['userinfo']['birthday']);
	echo '<select name="birth_year">';
	echo '<option value="unknown">-Årtal-</option>' ."\n";
	for($i = 2000; $i > 1930; $i--)
	{
		echo '<option value="' . $i . '"';
		echo ($i == $userbirthday[0]) ? ' selected="selected"' : null;
		echo '>' . $i . '</option>' . "\n";
	}
	echo '</select>' . "\n\n";
	echo '<select name="birth_month">';
	echo '<option value="01">-Månad-</option>';
	$months = array(1 => 'Januari', 2 => 'Februari', 3 => 'Mars', 4 => 'April', 5 => 'Maj', 6 => 'Juni', 7 => 'Juli', 8 => 'Augusti', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December');
	foreach($months AS $key => $label)
	{
		if ($key < 10)
		{
			$key = "0" . $key;
		}
		echo '<option value="' . $key . '"';
		echo ($key == $userbirthday[1]) ? ' selected="selected"' : null;
		echo '>' . $label . '</option>' . "\n";
	}
	echo '</select>' . "\n\n";
	echo '<select name="birth_day">';
	echo '<option value="01">-Dag-</option>';
	for($i = 1;$i <= 31; $i++)
	{
		echo '<option value="';
		echo ($i < 10) ? '0' : null;
		echo $i;
		echo '"';
		echo ($i == $userbirthday[2]) ? ' selected="selected"' : null;
		echo '>';
		echo $i . '</option>' . "\n";		
	}
	echo '</select>' . "\n";
	echo '<br /><br />' . "\n\n";
?>
	<script>
		function settings_zip_code_keyup()
		{
			user_zip_code = document.getElementById('settings_zip_code').value;
			if(user_zip_code == '' || user_zip_code == '0')
			{
				var code_label = 'Inget nummer angivet';
				document.getElementById('optional_info_submit').disabled = false;
			}
			else if(zip_codes[user_zip_code])
			{
				var code_label = zip_codes[user_zip_code];
				document.getElementById('optional_info_submit').disabled = false;
			}
			else
			{
				var code_label = '<strong style="color: red;">Ogitligt postnummer</strong>';
				document.getElementById('optional_info_submit').disabled = true;
			}
			document.getElementById('zip_code_label').innerHTML = code_label;
		}
	</script>
<?php
	echo '<strong>Postnummer</strong><br />' . "\n";
	echo '<input autocomplete="off" type="text" name="zip_code" id="settings_zip_code" value="' . $_SESSION['userinfo']['zip_code'] . '" onkeyup="settings_zip_code_keyup()" maxlength="6" style="width: 50px;" /><br />' . "\n";
	echo '<div id="zip_code_label"> </div>' . "\n";
	echo 'Vet du inte ditt postnummer? Sök på din adress på <a href="http://www.hitta.se" target="_blank">hitta.se</a>, dom har postnummer';
	echo '<br /><br />';


	echo '<strong>Kontaktinformation</strong><br />';
	$contact1 = parseContact($_SESSION['userinfo']['contact1']);
	echo listContactMediums('contact1_medium', $contact1['medium']);
	echo '&nbsp;Användarnamn/adress: ';
	echo '<input name="contact1_handle" type="text" class="textbox" size="50" value="'. $contact1['handle'] . '"><br/>';

	$contact2 = parseContact($_SESSION['userinfo']['contact2']);
	echo listContactMediums('contact2_medium', $contact2['medium']);
	echo '&nbsp;Användarnamn/adress: ';
	echo '<input name="contact2_handle" type="text" class="textbox" size="50" value="'. $contact2['handle'] . '"><br/>';
	echo '<br /><br />';
	echo '<strong>Förnamn</strong><br />' . "\n";
	echo '<input type="text" name="firstname" class="textbox" value="' . $_SESSION['traffa']['firstname'] . '" /><br /><br />' . "\n";
	echo '<br />';
	echo '<input type="submit" value="Spara frivillig information &raquo;" class="button_150" id="optional_info_submit" />';
	echo '</form>' . "\n";
	/*	echo '</div>' . "\n\n"; */
	rounded_corners_tabs_bottom();
	

/* Lösenordsbyte */
	echo '<a name="change_password"></a>';
	/* echo '<div class="grey_faded_div">' . "\n"; */
	rounded_corners_tabs_top();
	echo '<h2 style="margin-top: 0px;">Byt lösenord</h2>' . "\n";
	echo '<form action="' . $_SERVER['PHP_SELF'] . '?action=perform_changes&type=password" method="post">' . "\n";
	echo '<table><tr style="font-weight: bold;"><td>Nuvarande lösenord</td><td>Nytt lösenord</td><td>Upprepa nytt lösenord</td></tr>' . "\n";
	echo '<tr>' . "\n";
	echo '<td><input type="password" name="password_old" class="textbox" /></td>' . "\n";
	echo '<td><input type="password" name="password_new" class="textbox" /></td>' . "\n";
	echo '<td><input type="password" name="password_verify" class="textbox" /></td>' . "\n";
	echo '</tr></table><br />' . "\n";
	echo '<input type="submit" class="button_80" value="Byt lösenord &raquo;" />' . "\n";
	echo '</form>' . "\n";
	/* echo '</div>' . "\n"; */
	rounded_corners_tabs_bottom();
	

	$rounded_corners_config['color'] = 'orange_deluxe';
	$rounded_corners_config['return'] = true;
		
	echo rounded_corners_top($rounded_corners_config);
	echo '<span>Bytet av profiltema har flyttat. Du hittar det <a href="http://www.hamsterpaj.net/traffa/profile_presentation_change.php?action=theme_select">här</a></span>' . "\n";
	echo rounded_corners_bottom($rounded_corners_config);;


	ui_bottom();
?>
