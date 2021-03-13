<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_intermediate", "onlineteam_run");

// Die Informationen, die im Pluginmanager angezeigt werden
function onlineteam_info()
{
	return array(
		"name"		=> "Teammitglieder im Header",
		"description"	=> "Zeigt die Online-Teammitglieder mit Avatar & letzter Aktivität im Header an",
		"website"	=> "http://storming-gates.de",
		"author"	=> "sparks fly",
		"authorsite"	=> "http://storming-gates.de",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}


// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function onlineteam_activate()
{
	global $db;

	$insert_array = array(
		'title'		=> 'header_onlineteam',
		'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 33%;">
   <tr class="tcat">
      <td colspan="2">{$teamcount} Teammitglied{$plural} online</td>
   </tr>
      {$team_bit}
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'header_onlineteam_bit',
		'template'	=> $db->escape_string('<tr class="{$bgtrow}">
      <td><center>{$teamavatar}</center></td>
      <td>{$teamname} &raquo; {$pmlink}
      <br />{$lastvisit}</td>
   </tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'header_onlineteam_none',
		'template'	=> $db->escape_string('<tr>
      <td colspan="2">Zurzeit ist kein Teammitglied online!</td>
   </tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('<div id="content">')."#i", '<div id="content">{$teamonline}');

}

// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function onlineteam_deactivate()
{
	global $db;
$db->delete_query("templates", "title IN('header_onlineteam','header_onlineteam_none','header_onlineteam_bit')");

		include MYBB_ROOT."/inc/adminfunctions_templates.php";
		find_replace_templatesets("header", "#".preg_quote('{$teamonline}')."#i", '', 0);

}

// Führt die Abfrage aus
function onlineteam_run()
{

global $db, $mybb, $templates, $theme, $teamonline;

	$timesearch = TIME_NOW - (int)$mybb->settings['wolcutoff'];
	$teamcount = 0;

	$query = $db->query("
	SELECT * FROM ".TABLE_PREFIX."sessions
	LEFT JOIN ".TABLE_PREFIX."users ON ".TABLE_PREFIX."sessions.uid = ".TABLE_PREFIX."users.uid
	LEFT JOIN ".TABLE_PREFIX."userfields ON ".TABLE_PREFIX."userfields.ufid = ".TABLE_PREFIX."users.uid
	WHERE ".TABLE_PREFIX."sessions.time > '".$timesearch."'
	AND ".TABLE_PREFIX."users.usergroup IN (SELECT gid FROM ".TABLE_PREFIX."usergroups WHERE ".TABLE_PREFIX."usergroups.canmodcp = '1' )
	ORDER BY ".TABLE_PREFIX."sessions.time DESC
	");

	while($onlineteam = $db->fetch_array($query)) {
	$lastvisit = my_date('relative', $onlineteam['time']);
	$teamname = build_profile_link($onlineteam['username'], $onlineteam['uid']);
	if(!empty($onlineteam['avatar'])) {
	$teamavatar = "<img src=\"$onlineteam[avatar]\" style=\"width: 40px\" />";
	}
	else {
	$teamavatar = "";
	}
	$pmlink = "<a href=\"private.php?action=send&uid=$onlineteam[uid]\" target=\"blank\">PN senden</a>";

	$teamcount++;

	$bgtrow = alt_trow();

	eval('$team_bit .= "'.$templates->get('header_onlineteam_bit').'";');
	}

	if($teamcount == 0 OR $teamcount > 1) {
	$plural = "er";
	}

	if($teamcount == 0) {
	eval('$team_bit = "'.$templates->get('header_onlineteam_none').'";');
	}

	eval('$teamonline = "'.$templates->get('header_onlineteam').'";');


}
?>
