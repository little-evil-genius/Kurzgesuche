<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// HOOKS
$plugins->add_hook("misc_start", "shortsearch_misc");
$plugins->add_hook('global_start', 'shortsearch_global');
$plugins->add_hook ("admin_user_users_delete_commit_end", "shortsearch_user_delete");
$plugins->add_hook("member_profile_end", "shortsearch_member_profile_end");
$plugins->add_hook('modcp_nav', 'shortsearch_modcp_nav');
$plugins->add_hook("modcp_start", "shortsearch_modcp");

// Die Informationen, die im Pluginmanager angezeigt werden
function shortsearch_info()
{
	return array(
		"name"			=> "Kurzgesuche",
		"description"	=> "Es handelt sich um ein Kurzgesuch-Plugin, für Gesuche, welche zwar Anschluss bieten, aber kein vollständiges Charakterkonzept besitzen und eher freier sind. Ausgewählte Gruppen können Kurzgesuche hinzufügen. Gäste und User können sich diese Gesuche reservieren und auf Wunsch werden die Gesuche auch im Profil angezeigt. Die Kategorien der Gesuche können manuell in ACP eingestellt werden. Die Gesuche können gefiltert werden auf der Hauptseite.",
		"author"		=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"		=> "1.0",
		"compatibility" => "18*"
	);
}

// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird
function shortsearch_install()
{
    global $db, $cache, $mybb;

    // Datenbank-Tabelle erstellen
	$db->query("CREATE TABLE ".TABLE_PREFIX."shortsearch(
        `sid` int(10) NOT NULL AUTO_INCREMENT,
	`type` VARCHAR(255) NOT NULL,
	`searchtitle` VARCHAR(2500) COLLATE utf8_general_ci NOT NULL,
	`searchgender` VARCHAR(255) NOT NULL,
        `searchage` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
        `searchrelationstatus` VARCHAR(255) NOT NULL,
        `searchjob` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
        `searchrelation` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
        `searchtext` VARCHAR(2500) COLLATE utf8_general_ci NOT NULL,
        `searchavatar` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
	`searchstatus` VARCHAR(255) NOT NULL,
        `wantedby` int(11) NOT NULL,
	`rid` int(11) NOT NULL,
	`reservationname` VARCHAR(255) NOT NULL,
	`reservationtext` VARCHAR(500) NOT NULL,
        PRIMARY KEY(`sid`),
        KEY `sid` (`sid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
        ");

        $db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `shortsearch_new` int(11) NOT NULL DEFAULT '0';");
    
    // EINSTELLUNGEN HINZUFÜGEN
    $setting_group = array(
        'name'          => 'shortsearch',
        'title'         => 'Kurzgesuche',
        'description'   => 'Einstellungen für die Kurzgesuche',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
        $gid = $db->insert_query("settinggroups", $setting_group); 
        
    $setting_array = array(
        'shortsearch_allow_groups' => array(
            'title' => 'Erlaubte Gruppen',
            'description' => 'Welche Gruppen dürfen Kurzgesuche erstellen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 1
        ),

        'shortsearch_category' => array(
            'title' => 'Kategorien',
            'description' => 'Welche Kategorien soll es geben?',
            'optionscode' => 'text',
            'value' => 'Arbeit, Familie, Freunde, Feinde, Liebe, Sonstiges', // Default
            'disporder' => 2
        ),

        'shortsearch_gender' => array(
            'title' => 'Geschlechts-Möglichkeiten',
            'description' => 'Welche Möglichkeiten soll es für das Geschlecht geben? Frei wählbar muss nicht hinzugefügt werden, sondern ist automatisch dabei',
            'optionscode' => 'text',
            'value' => 'Weiblich, Männlich, Divers', // Default
            'disporder' => 3
        ),

        'shortsearch_relation' => array(
            'title' => 'Beziehungsstatus-Möglichkeiten',
            'description' => 'Welche Möglichkeiten soll es für den Beziehungststatus geben? Frei wählbar muss nicht hinzugefügt werden, sondern ist automatisch dabei',
            'optionscode' => 'text',
            'value' => 'Single, Verliebt, Vergeben, Offene Beziehung, Verlobt, Verheiratet, Getrennt, Verwitwet, Es ist kompliziert', // Default
            'disporder' => 4
        ),

        'shortsearch_playerfid' => array(
            'title' => 'Profilfeld des Spielernamens',
            'description' => 'Gib hier die ID des Profilfeld für die Spielernames an.',
            'optionscode' => 'text',
            'value' => '4', // Default
            'disporder' => 5
        ),

        'shortsearch_teamuid' => array(
            'title' => 'Teamaccount',
            'description' => 'Gib hier die ID des Teamaccounts an, damit bei einer Reservierung eines Gastes die PN vom Teamaccount bekommt.',
            'optionscode' => 'text',
            'value' => '1', // Default
            'disporder' => 6
        ),

        'shortsearch_profile' => array(
            'title' => 'Kurzgesuche im Profil',
            'description' => 'Sollen die Kurzgesuche in den Profilen der Charaktere ausgegeben werden?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 7
        ),
    );
        
        foreach($setting_array as $name => $setting)
        {
            $setting['name'] = $name;
            $setting['gid']  = $gid;
            $db->insert_query('settings', $setting);
        }
    
        rebuild_settings();
	
        // TEMPLATES ERSTELLEN
    
        // HAUPTSEITE
        $insert_array = array(
            'title'		=> 'shortsearch',
            'template'	=> $db->escape_string('<html>
            <head>
                <title>{$mybb->settings[\'bbname\']} - {$lang->shortsearch}</title>
                {$headerinclude}
            </head>
            <body>
                {$header}
                <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                    <tr>
                        <td class="thead" colspan="2">
                            {$lang->shortsearch}
                        </td>
                    </tr>
                    {$shortsearch_menu}
                    {$shortsearch_filter}
                    <td class="trow1" align="center">
                        {$shortsearch_category}
                    </td>
                    </tr>
                </table>
            {$footer}
            </body>
        </html>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // FILTER
        $insert_array = array(
            'title'		=> 'shortsearch_filter',
            'template'	=> $db->escape_string('<tr><td align="center" class="trow1">
            <form id="search_filter" method="post">
            <input type="hidden" name="action" value="shortsearch" />
        <table><td class="smalltext">Filtern nach:</td>
        <td><select name="filter_gender">
    <option value="%" selected>Beide Geschlechter</option>
    {$gender_select}
    </select></td>
    <td><select name="filter_relation">
        <option value="%" selected>Zeige alle Beziehungsstatus an</option>
        {$relation_select}
        </select></td>
    <td  align="center">
<input type="submit" name="search_filter" value="Filtern" id="submit" class="button"></td></tr>
    </table>
</form>
        </td></tr>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // MENÜ
        $insert_array = array(
            'title'		=> 'shortsearch_menu',
            'template'	=> $db->escape_string('<tr>
            <td class="trow1" colspan="2">
                <div style="display: flex;flex-wrap: wrap;margin: 5px;">
                    <div class="shortsearch-link"><a href="misc.php?action=shortsearch">Alle Kurzgesuche</a></div>
                    <div class="shortsearch-link" style="margin:0 5px 5px;"><a href="misc.php?action=shortsearch_add">Kurzgesuch hinzufügen</a></div>
                    <div class="shortsearch-link"><a href="misc.php?action=shortsearch_own">Eigene Kurzgesuche</a></div>	
                </div>
            </td>
        <tr>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // WENN ES KEINE KURZGESUCHE GIBT
        $insert_array = array(
            'title'		=> 'shortsearch_none',
            'template'	=> $db->escape_string('<div class="trow2">
            <table border="0" cellpadding="5" cellspacing="5">
                <tr>
                    <td>
                        <div style="text-align:center;margin:10px auto;">Aktuell sind keine Kurzgesuche in der Kategorie <b>{$typ}</b> eingetragen!</div>
                    </td>
                </tr>
            </table>
        </div>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // ÜBERSICHT DER EIGNENEN KURGESUCHE
        $insert_array = array(
            'title'		=> 'shortsearch_own',
            'template'	=> $db->escape_string('<html>
            <head>
                <title>{$mybb->settings[\'bbname\']} - {$lang->shortsearch_own}</title>
                {$headerinclude}
            </head>
            <body>
                {$header}
                <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                    <tr>
                        <td class="thead" colspan="2">
                            <div class="headline">{$lang->shortsearch_own}</div>
                        </td>
                    </tr>
                    {$shortsearch_menu}
                    <td class="trow1" align="center">
                        {$shortsearch_category}
                    </td>
                    </tr>
                </table>
            {$footer}
            </body>
        </html>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // ÜBERSICHT DER EIGNENEN KURGESUCHE - EINZEL 
        $insert_array = array(
            'title'		=> 'shortsearch_own_bit',
            'template'	=> $db->escape_string('<div id="shortsearch-box">        
            <div class="title">{$title}</div>    
            <div class="relation">{$relation}</div>    
            <div class="wantedby">gesucht von {$charaname}</div>  
            <div style="display: flex;">	
                <div class="fact"><i class="fas fa-birthday-cake"></i> {$age}</div>		
                <div class="fact"><i class="fas fa-venus-mars"></i> {$gender}</div>		 	
            </div>	
            <div class="desc">{$text}</div>	
            <div style="display: flex;">	
                <div class="fact"><i class="fas fa-heart"></i> {$relationstatus}</div>		
                <div class="fact"><i class="fas fa-briefcase"></i> {$job}</div>		  	
            </div>    
            <div class="avatar"><i class="fas fa-camera-retro"></i> {$avatar}</div>
            <div class="status">{$statusicon}{$status}</div>
            <div class="wantedby"><i class="fas fa-cogs"></i> {$option}</div>    
        </div>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // DIE KATEGORIEN AUF DER HAUPTSEITE AUS DEM ACP
        $insert_array = array(
            'title'		=> 'shortsearch_category',
            'template'	=> $db->escape_string('<table width="100%" style="margin: auto;"> 
            <tr>    
                <td class="thead">        
                    <h1>{$typ}</h1>
                </td>    
            </tr>
            <tr>
                <td>
                    {$shortsearch}
                    {$shortsearch_none}
                </td>
            </tr>        
        </table>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // DIE EINZELNEN KURZGESUCHE
        $insert_array = array(
            'title'		=> 'shortsearch_bit',
            'template'	=> $db->escape_string('<div id="shortsearch-box">        
            <div class="title">{$title}</div>    
            <div class="relation">{$relation}</div>    
            <div class="wantedby">gesucht von {$charaname} {$spielername}</div>    
            <div style="display: flex;">	
                <div class="fact"><i class="fas fa-birthday-cake"></i> {$age}</div>		
                <div class="fact"><i class="fas fa-venus-mars"></i> {$gender}</div>	  	
            </div>	
            <div class="desc">{$text}</div>	
            <div style="display: flex;">			
                <div class="fact"><i class="fas fa-briefcase"></i> {$job}</div>		
                <div class="fact"><i class="fas fa-heart"></i> {$relationstatus}</div>  	
            </div>    
            <div class="avatar"><i class="fas fa-camera-retro"></i> {$avatar}</div>
            <div class="status">{$statusicon}{$status} {$reservations}</div>
        </div>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // GÄSTE RESERVIERUNG
        $insert_array = array(
            'title'		=> 'shortsearch_guest_reservation',
            'template'	=> $db->escape_string('<a href="#popinfo$sid" original-title="Reservieren"><i class="fas fa-user-shield" style="float:none"></i></a>

            <div id="popinfo$sid" class="searchpop">
                <div class="pop">
                    <form action="misc.php?action=shortsearch&resguest={$sid}" method="post">
                        <input type="hidden" name="sid" id="sid" value="{$sid}"/>
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="tcat">Dein Spitzname</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                    <input type="text" name="reservationname" id="reservationname" placeholder="Spitzname" class="textbox" style="width:90%">
                                    </td>
                                </tr>
				<tr>
                                    <td>
                                        <div class="tcat">Dein Nachricht</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                    <input type="textarea" name="reservationtext" id="reservationtext" placeholder="Hinterlasse doch deine Kontaktdaten wie Discord oder ähnliches" class="textbox" style="width:90%">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <input type="submit" name="sid" value="Kurzgesuch reservieren" id="sid" class="button">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>		
                </div>
                <a href="#closepop" class="closepop"></a>
            </div>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // KURZGESUCH HINZUFÜGEN
        $insert_array = array(
            'title'		=> 'shortsearch_add',
            'template'	=> $db->escape_string('<html>
            <head>
                <title>{$mybb->settings[\'bbname\']} - {$lang->shortsearch_add}</title>
                {$headerinclude}
            </head>
            <body>
                {$header}
                <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                    <tr>
                        <td class="thead" colspan="2">
                            {$lang->shortsearch_add}
                        </td>
                    </tr>
                    {$shortsearch_menu}
                    <td class="trow1" align="center">
                        <form id="shortsearch" method="post" action="misc.php?action=shortsearch_add">	
                            <table width="100%">		
                                <tr>
                                    <td class="trow1" style="width: 46%;" >
                                        <strong>{$lang->shortsearch_add_category}</strong>
                                        <div class="smalltext">{$lang->shortsearch_add_category_desc}</div>
                                    </td>		
                                    <td class="trow2">
                                        <select name=\'type\' id=\'type\' style="width: 100%;" required>
                                            <option value="">Kategorie wählen</option>
                                            {$cat_select}	
                                        </select>			
                                    </td>
                                </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_title}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_title_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchtitle" id="searchtitle" placeholder="Gesuchstitel" class="textbox" style="width: 98.5%;" required /> 
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_relation}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_relation_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchrelation" id="searchrelation" placeholder="beste Freundin oder Exfreund" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_age}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_age_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchage" id="searchage" placeholder="xx Jahre oder xx - xx Jahre" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_gender}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_gender_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <select name=\'searchgender\' id=\'searchgender\'  style="width: 100%;" required>
                                                        <option value="">Geschlecht wählen</option>
                                                        {$gender_select}
                                                        <option value="frei wählbar">frei wählbar</option>
                                                    </select>
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_job}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_job_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchjob" id="searchjob" placeholder="Floristin oder Schülerin" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_relationstatus}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_relationsstatus_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <select name=\'searchrelationstatus\' id=\'searchrelationstatus\' style="width: 100%;" required>
                                                    <option value="">Beziehungststatus wählen</option>
                                                    {$relation_select}
                                                        <option value="frei wählbar">frei wählbar</option>
                                                    </select>
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_avatar}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_avatar_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchavatar" id="searchavatar" placeholder="Avatarperson oder frei wählbar" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                                    
                                            <tr>	
                                                <td class="trow1" colspan="2" align="center">
                                                    <strong>{$lang->shortsearch_add_text}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_text_desc}</div>
                                                </td>
                                            </tr>	
                                            <tr>
                                                <td class="trow2" colspan="2">
                                                    <textarea class="textarea" name="searchtext" id="searchtext" rows="6" cols="30" style="width: 99.5%"></textarea>
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td colspan="2" align="center">
                                                    <input type="submit" name="submit" value="Kurzgesuch hinzufügen" class="button" id="submit">
                                                </td>
                                            </tr>	
                                        </table>
                                    </form>
                            </td>
                            </tr>
                        </table>
            {$footer}
            </body>
        </html>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // KURZGESUCH BEARBEITEN
        $insert_array = array(
            'title'		=> 'shortsearch_edit',
            'template'	=> $db->escape_string('<html>
            <head>
                <title>{$mybb->settings[\'bbname\']} - {$lang->shortsearch_edit}</title>
                {$headerinclude}
            </head>
            <body>
                {$header}
                <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                    <tr>
                        <td class="thead" colspan="2">
                            {$lang->shortsearch_edit}
                        </td>
                    </tr>
                    {$shortsearch_menu}
                    <td class="trow1" align="center">
                        <form method="post" action="misc.php?action=shortsearch_edit&edit={$sid}">	
                            <input type="hidden" name="sid" id="sid" value="{$sid}" class="textbox" />
                            <table width="100%">		
                                <tr>
                                    <td class="trow1" style="width: 46%;" >
                                        <strong>{$lang->shortsearch_add_category}</strong>
                                        <div class="smalltext">{$lang->shortsearch_add_category}</div>
                                    </td>		
                                    <td class="trow2">
                                        <select name=\'type\' id=\'type\' style="width: 100%;" required>
                                            <option value="{$type}">{$type}</option>
                                            {$cat_select}	
                                        </select>			
                                    </td>
                                </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_title}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_title_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchtitle" id="searchtitle" value="{$title}" class="textbox" style="width: 98.5%;" required /> 
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_relation}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_relation_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchrelation" id="searchrelation" value="{$relation}" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_age}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_age_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchage" id="searchage" value="{$age}" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_gender}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_gender_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <select name=\'searchgender\' id=\'searchgender\'  style="width: 100%;" required>
                                                        <option value="{$gender}">{$gender}</option>
                                                        {$gender_select}
                                                        <option value="frei wählbar">frei wählbar</option>
                                                    </select>
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_job}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_job_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchjob" id="searchjob" value="{$job}" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_relationstatus}</strong>
                                                    <div class="smalltext">{$lang->shortsearch_add_relationstatus_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <select name=\'searchrelationstatus\' id=\'searchrelationstatus\' style="width: 100%;" required>
                                                        <option value="{$relationstatus}">{$relationstatus}</option>
                                                        {$relation_select}
                                                        <option value="frei wählbar">frei wählbar</option>
                                                    </select>
                                                </td>
                                            </tr>
                                
                                            <tr>
                                                <td class="trow1">
                                                    <strong>{$lang->shortsearch_add_avatar}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_avatar_desc}</div>
                                                </td>
                                                <td class="trow2">
                                                    <input type="text" name="searchavatar" id="searchavatar" value="{$avatar}" class="textbox" style="width: 98.5%;" required />
                                                </td>
                                            </tr>
                                    
                                            <tr>	
                                                <td class="trow1" colspan="2" align="center">
                                                    <strong>{$lang->shortsearch_add_text}</strong>	
                                                    <div class="smalltext">{$lang->shortsearch_add_text_desc}</div>
                                                </td>
                                            </tr>	
                                            <tr>
                                                <td class="trow2" colspan="2">
                                                    <textarea class="textarea" name="searchtext" id="searchtext" rows="6" cols="30" style="width: 99.5%">{$text}</textarea>
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td colspan="2" align="center">
                                                    <input type="submit" name="edit_shortsearch" value="Kurzgesuch bearbeiten" id="submit" class="button">
                                                </td>
                                            </tr>	
                                        </table>
                                    </form>
                            </td>
                            </tr>
                        </table>
            {$footer}
            </body>
        </html>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // MODCP - MENU
        $insert_array = array(
            'title'		=> 'shortsearch_modcp_nav',
            'template'	=> $db->escape_string('<tr>
            <td class="trow1 smalltext">
                <a href="modcp.php?action=shortsearch" class="modcp_nav_item modcp_jobliste">Kurzgesuche verwalten</a>
            </td>
        </tr>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // MODCP - SEITE
        $insert_array = array(
            'title'		=> 'shortsearch_modcp',
            'template'	=> $db->escape_string('<html>
            <head>
                <title>{$mybb->settings[\'bbname\']} -  Alle Kurzgesuche</title>
                {$headerinclude}
            </head>
            <body>
                {$header}
                <table width="100%" border="0">
                    <tr>
                        {$modcp_nav}
                        <td valign="top">
                            <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                                <tr>
                                    <td class="thead">Alle Kurzgesuche</td>
                                </tr>
                                <tr>
                                    <td valign="top">
                                        <table width="100%">
                                            <tr>
                                                <td width="20%" class="tcat">Titel</td>
                                                <td width="20%" class="tcat">Kategorie</td>
                                                <td width="20%" class="tcat">Gesucht von</td>
                                                <td width="20%" class="tcat">Status</td>
                                                <td width="20%" class="tcat">Optionen</td>
                                            </tr>
                                            {$shortsearch_mod_bit}
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                {$footer}
            </body>
        </html>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);

        // MODCP - EINZELNE GESUCHE
        $insert_array = array(
            'title'		=> 'shortsearch_modcp_bit',
            'template'	=> $db->escape_string('<tr>
            <td>{$title}</td>
            <td>{$type}</td>
            <td>{$charaname} {$spielername}</td>
            <td>{$status}</td>
            <td>{$option}</td>
    </tr>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // PROFIL
        $insert_array = array(
            'title'		=> 'shortsearch_memprofile',
            'template'	=> $db->escape_string('<table border="0" cellspacing="0" cellpadding="5" class="tborder">  
            <tr>
                <td class="thead"><b>Kurzgesuche</b></td>
            </tr>
            <tr>
                <td class="trow1">
					<div style="display: flex; flex-wrap: wrap; margin: auto;">
					{$shortsearch_profile_bit}
					</div>
				</td>
            </tr>
        </table>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);
    
        // EINZELNEN KURZGESUCHE IM PROFIL
        $insert_array = array(
            'title'		=> 'shortsearch_memprofile_bit',
            'template'	=> $db->escape_string('<div id="shortsearch-profile">
            <div class="title">{$title}</div>
            <div class="status">{$status}</div>
            <div>
                <table style="margin: auto; width: 98%;">
                    <tr>
                        <td width="50%">
                            <div class="infos" style="padding: 0 2px 2px 0;"><i class="fas fa-birthday-cake"></i> {$age}</div>
                        </td>
                        <td width="50%">  	
                            <div class="infos" style="padding: 0 0 2px 0;"><i class="fas fa-venus-mars"></i> {$gender}</div>		
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="infos"  style="padding: 0 0 2px 0;"> <i class="fas fa-briefcase"></i> {$job}</div>
                        </td>
                        <td>
                            <div class="infos"  style="padding: 0 2px 2px 0;"><i class="fas fa-heart"></i> {$relation}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><div class="infos"><i class="fas fa-camera-retro"></i> {$avatar}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>'),
            'sid'		=> '-1',
            'dateline'	=> TIME_NOW
        );
        $db->insert_query("templates", $insert_array);

        $insert_array = array(
            'title' => 'shortsearch_alert',
            'template' => $db->escape_string('<div class="pm_alert">
            <strong>{$lang->shortsearch_alert} {$shortsearch_read}</strong>
        </div>
        <br />'),
            'sid' => '-1',
            'version' => '',
            'dateline' => TIME_NOW
        );
    
        $db->insert_query("templates", $insert_array);
}

// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false
function shortsearch_is_installed()
{
    global $db, $cache, $mybb;
  
      if($db->table_exists("shortsearch"))  {
        return true;
      }
        return false;
}

function shortsearch_uninstall()
{
  global $db;

    //DATENBANKEN LÖSCHEN
    if($db->table_exists("shortsearch"))
    {
        $db->drop_table("shortsearch");
    }
    
    // SPALTE IN USER-DATENBANK LÖSCHEN
    if($db->field_exists("shortsearch_new", "users"))
    {
        $db->drop_column("users", "shortsearch_new");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'shortsearch%'");
    $db->delete_query('settinggroups', "name = 'shortsearch'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE '%shortsearch%'");

    // CSS LÖSCHEN
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'shortsearch.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}

// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function shortsearch_activate()
{
    global $db, $cache;
    
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    
    // VARIABLEN EINFÜGEN
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$new_shortsearch} {$bbclosedwarning}');
    find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch} {$nav_shortsearch}');
    find_replace_templatesets('member_profile', '#'.preg_quote('{$contact_details}').'#', '{$contact_details}{$shortsearch_profile}');


    // CSS	
	$css = array(
		'name' => 'shortsearch.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'/* HAUPTSEITE */

        .shortsearch-link {
            font-size: 15px;
            font-weight: 400;
            text-align: left;
            text-transform: uppercase;
            color: #8596A6;
            padding: 0 5px;
            border-bottom: #8596a6 2px solid;
            margin: 0 0px 5px 0px;
            font-family: Playfair Display,serif;
            line-height: 22px;
            padding-top: 2px;
            width: 345px;
    }

            #shortsearch-box {
                margin: 2px;
                width: 535px;
                box-sizing: border-box;
                padding: 5px;
                overflow: hidden;
                display: inline-block;
            }
            
            #shortsearch-box .title {
                box-sizing: border-box;
                padding: 5px 0;
                background: #293340;
                text-align: center;
                font-family: Playfair Display,serif;
                font-size: 20px;
                letter-spacing: 1px;
                color: #8596a6;
            }
            
            #shortsearch-box .relation {
                box-sizing: border-box;
                width: 100%;
                padding: 4px;
                margin: 2px auto;
                color: #293340;
                text-align: center;
                letter-spacing: 1px;
                background: #8596a6;
                font-size: 12px;
                font-weight: 600;
            }
            
            #shortsearch-box .wantedby {
                box-sizing: border-box;
                width: 100%;
                padding: 4px;
                margin: 2px auto;
                color: #293340;
                text-align: center;
                letter-spacing: 1px;
                background: #8596a6;
                font-size: 12px;
                font-weight: 600;
            }
            
            #shortsearch-box .wantedby i {
                float: left;
                margin: 4px 0 0 1px;
                font-size: 14px;
            }
            
            #shortsearch-box .fact {
                font-family: Overpass,sans-serif;
                font-size: 11px;
                padding: 2px 5px;
                text-align: center;
                margin: 2px 1px;
                background: #293340;
                margin-bottom: 3px;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
                color: #8596a6;
                letter-spacing: 1px;
                line-height: 20px;
                font-weight: 600;
                width: 100%;
            }
            
            #shortsearch-box .fact i {
                float: left;
                margin: 4px 0 0 1px;
                font-size: 14px;
            }
            
            #shortsearch-box .desc {
                height: 150px;
                overflow: auto;
                margin-top: 5px;
                padding-right: 5px;
                font-size: 11px;
                line-height: 15px;
                text-align: justify;
                letter-spacing: 1px;
                color: #C7CFD9;
            }
            
            #shortsearch-box .avatar {
                box-sizing: border-box;
                width: 100%;
                padding: 4px;
                margin: 2px auto;
                color: #293340;
                text-align: center;
                letter-spacing: 1px;
                background: #8596a6;
                font-size: 12px;
                font-weight: 600;
            }
            
            #shortsearch-box .avatar i {
                float: left;
                margin: 4px 0 0 1px;
                font-size: 14px;
            }
            
            #shortsearch-box .status {
                box-sizing: border-box;
                width: 100%;
                padding: 4px;
                margin: 2px auto;
                color: #293340;
                text-align: center;
                letter-spacing: 1px;
                background: #8596a6;
                font-size: 12px;
                font-weight: 600;
            }
            
            #shortsearch-box .status i {
                float: left;
                margin: 4px 0 0 1px;
                font-size: 14px;
            }
	    
	    /* POPUP */
.searchpop {
	position:fixed;
	top:0;
	right:0;
	bottom:0;
	left:0;
	background:hsla(0,0%,0%,0.3);
	z-index: 99;
	opacity:0;
	-webkit-transition:.5s ease-in-out;
	-moz-transition:.5s ease-in-out;
	transition:.5s ease-in-out;
	pointer-events:none;
}

.searchpop:target {
	opacity:1;
	pointer-events: auto;
}

/* Hier wird das Popup definiert! */
.searchpop>.pop {
	position:relative;
	margin:10% auto;
	width:600px;
	max-height:450px;
	box-sizing:border-box;
	padding:10px;
	background: #4C6173;
	border: 3px solid #8596A6;
	text-align:justify;
	overflow:auto;
	z-index:999;
	font-size: 10px;
	line-height: 15px;
	text-align: justify;
	letter-spacing: 1px;
	color: #C7CFD9;
	font-family: Overpass,sans-serif;
}

.searchpop>.closepop {
	position:absolute;
	right:-5px;
	top:-5px;
	width:100%;
	height:100%;
	z-index: 1;
}
            
            /* PROFIL */
            #shortsearch-profile {
                width: 32.5%;
                margin: 2px 4px;
                float: left;
                color: #C7CFD9;
            }
            
            #shortsearch-profile .title {
                font-size: 12px;
                font-weight: 400;
                text-align: left;
                text-transform: uppercase;
                color: #8596A6;
                padding: 5px;
                border-bottom: #8596a6 2px solid;
                margin: 0 0px 5px 0px;
                font-family: Playfair Display,serif;
            }
            
            #shortsearch-profile .status {
                text-align: center;
                font-size: 12px;
                font-family: Overpass,sans-serif;
            }
            
            #shortsearch-profile .infos{
            text-align: center;
            font-size: 11px;
            font-family: Oswald, sans-serif;	
            }
            
            .infos i {
                color: #293340;
                float: left
            }',
		'cachefile' => $db->escape_string(str_replace('/', '', 'shortsearch.css')),
		'lastmodified' => time()
	);
    
    $sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}

}

function shortsearch_deactivate()
{
    global $db, $cache;

    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN ENTFERNEN
    find_replace_templatesets("header", "#".preg_quote('{$new_shortsearch}')."#i", '', 0);
	find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_shortsearch}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$shortsearch_profile}')."#i", '', 0);

}

// DIE FUNKTIONEN - THE MAGIC

###########################
##### ONLINE LOCATION #####
###########################
$plugins->add_hook("fetch_wol_activity_end", "shortsearch_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "shortsearch_online_location");

function shortsearch_online_activity($user_activity) {
global $parameters;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'misc':
        if($parameters['action'] == "shortsearch" && empty($parameters['site'])) {
            $user_activity['activity'] = "shortsearch";
        }
        if($parameters['action'] == "add_shortsearch" && empty($parameters['site'])) {
            $user_activity['activity'] = "add_shortsearch";
        }
        if($parameters['action'] == "own_shortsearch" && empty($parameters['site'])) {
            $user_activity['activity'] = "own_shortsearch";
        }
        break;
    }
      
return $user_activity;
}

function shortsearch_online_location($plugin_array) {
global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "shortsearch") {
		$plugin_array['location_name'] = "Sieht sich die <a href=\"misc.php?action=shortsearch\">Kurzgesuche</a> an.";
	}
    if($plugin_array['user_activity']['activity'] == "add_shortsearch") {
		$plugin_array['location_name'] = "Erstellt ein neues Kurzgesuch.";
	}
    if($plugin_array['user_activity']['activity'] == "own_shortsearch") {
		$plugin_array['location_name'] = "Sieht sich seine eigene Kurzgesuche an.";
	}

return $plugin_array;
}

// INDEXHINWEIS ÜBER NEUE KURZGESUCHE
function shortsearch_global(){
    global $db, $mybb, $templates, $new_shortsearch, $shortsearch_read, $lang;
    
    // SPRACHDATEI LADEN
    $lang->load('shortsearch');

    $uid = $mybb->user['uid'];

    $shortsearch_read = "<a href='misc.php?action=shortsearch_read&read={$uid}' original-title='als gelesen markieren'><i class=\"fas fa-trash\" style=\"float: right;font-size: 14px;padding: 1px;\"></i></a>";

    $select = $db->query ("SELECT * FROM " . TABLE_PREFIX ."shortsearch");
    $row_cnt = mysqli_num_rows ($select);
    if ($row_cnt > 0) {
        $select = $db->query ("SELECT shortsearch_new FROM " . TABLE_PREFIX . "users 
        WHERE uid = '" . $mybb->user['uid'] . "' LIMIT 1");


        $data = $db->fetch_array ($select);
        if(isset($data['shortsearch_new']) && $data['shortsearch_new'] == '0'){

            eval("\$new_shortsearch = \"" . $templates->get ("shortsearch_alert") . "\";");

        }

    }
}

// DIE SEITEN
function shortsearch_misc() {
    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $reservations;

    // SPRACHDATEI LADEN
    $lang->load('shortsearch');
    
    // USER-ID
    $uid = $mybb->user['uid'];

    // ACTION-BAUM BAUEN
    $mybb->input['action'] = $mybb->get_input('action');

    // MENÜ - NUR SICHTBAR FÜR DIE, DIE AUCH GESUCHE ERSTELLE KÖNNEN
    if(is_member($mybb->settings['shortsearch_allow_groups'])) {
    
        eval("\$shortsearch_menu .= \"" . $templates->get("shortsearch_menu") . "\";"); 
    }
    else {
        $shortsearch_menu = ""; 
    }

    // EINSTELLUNG FÜR DIE FID FÜR DEN SPITZNAMEN
    $playerfid_setting = $mybb->settings['shortsearch_playerfid'];
    $playerfid = "fid".$playerfid_setting;

    // DAMIT DIE PN SACHE FUNKTIONIERT
    require_once MYBB_ROOT."inc/datahandlers/pm.php";
    $pmhandler = new PMDataHandler();

    // ALLE KURZGESUCHE - ÜBERSICHT
    if($mybb->input['action'] == "shortsearch") {
        
      // NAVIGATION
      add_breadcrumb ($lang->shortsearch, "misc.php?action=shortsearch");

      // AUSWAHLMÖGLICHKEIT DER KATEGORIE AUTOMATISCH AUS DEN EINSTELLUNGEN ERSTELLEN
        
    // GESCHLECHT
     $shortsearch_gender_setting = $mybb->settings['shortsearch_gender'];
        $shortsearch_gender = explode (", ", $shortsearch_gender_setting);
        foreach ($shortsearch_gender as $gender) {
            $gender_select .= "<option value='{$gender}'>{$gender}</option>";
        }
        
    // BEZIEHUNGSSTATUS
     $shortsearch_relation_setting = $mybb->settings['shortsearch_relation'];
        $shortsearch_relation = explode (", ", $shortsearch_relation_setting);
        foreach ($shortsearch_relation as $relation) {
            $relation_select .= "<option value='{$relation}'>{$relation}</option>";
        }

      // FILTERN
      $relation_filter = "%";
      $gender_filter = "%";
      if(isset($_POST['search_filter'])) {
          $relation_filter = $_POST['filter_relation'];
          $gender_filter = $_POST['filter_gender'];
      }

        // KATEGORIEN AUS DEN EINSTELLUNGEN ZIEHEN UND AUFSPALTEN
        $shortsearch_cat_setting = $mybb->settings['shortsearch_category'];
        $type = explode (", ", $shortsearch_cat_setting);

        foreach ($type as $typ) {
            $shortsearch = "";
            eval("\$shortsearch_none = \"".$templates->get("shortsearch_none")."\";");
            // ABFRAGE DER DATENBANKEN - SHORTSEARCH & USER & USERFIELDS
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."shortsearch
            LEFT JOIN ".TABLE_PREFIX."users
            ON ".TABLE_PREFIX."users.uid = ".TABLE_PREFIX."shortsearch.wantedby
            LEFT JOIN ".TABLE_PREFIX."userfields
            ON ".TABLE_PREFIX."userfields.ufid = ".TABLE_PREFIX."shortsearch.wantedby
            WHERE type = '$typ'
            AND searchrelationstatus like '$relation_filter'
            AND searchgender like '$gender_filter' 
            ORDER by searchtitle ASC");

            while ($search = $db->fetch_array ($query)) {
                
            $shortsearch_none = "";

                // LEER LAUFEN LASSEN 
                $sid = "";
                $title = "";
                $gender = "";
                $age = "";
                $relationstatus = "";
                $job = "";
                $relation = "";
                $text = "";
                $avatar = "";
                $status = "";
                $wantedby = "";
                $rid = "";

                // MIT INFORMATIONEN FÜLLEN
                $avatar = $search['searchavatar'];
                $sid = $search['sid'];
                $rid = $search['rid'];
                $title = $search['searchtitle'];
                $gender = $search['searchgender'];
                $age = $search['searchage'];
                $relationstatus = $search['searchrelationstatus'];
                $job = $search['searchjob'];
                $relation = $search['searchrelation'];
                $text = $search['searchtext'];
                $reservationname = $search['reservationname'];
                $wantedby = $search['wantedby'];

                // CHARAKTERNAME
                $username = format_name($search['username'], $search['usergroup'], $search['displaygroup']);
                $charaname = build_profile_link($username, $search['wantedby']);

                // SPIELERNAME
                if ($search[$playerfid] == "") {
                    $spielername = ""; 
                } else {
                $spielername = "($search[$playerfid])";
                }

                // GESUCHSSTATUS - ANZEIGE
                if ($search['searchstatus'] == "0") {
                 $status = "Das Gesuch ist <b>frei</b>";
                 $statusicon = "<i class=\"fas fa-lock-open\"></i>";
                }

                // RESERVIERT VON EINEM USER
                elseif ($search['searchstatus'] == "1" && $search['rid'] != "0") {

                $reservations_user = $db->query("SELECT * FROM ".TABLE_PREFIX."users
                LEFT JOIN ".TABLE_PREFIX."userfields
                ON ".TABLE_PREFIX."userfields.ufid = '$rid'
                WHERE uid = '$rid'
                ");
            
                $user = $db->fetch_array($reservations_user);

                // SPIELERNAME
               if ($user[$playerfid] == "") {
                $resname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                $spitzname = build_profile_link($resname, $user['uid']); 
            } else {
                $spitzname = build_profile_link($user[$playerfid], $user['uid']);
            }

               

                 $status = "Das Gesuch ist <b>reserviert</b> für {$spitzname}";
                 $statusicon = "<i class=\"fas fa-user-shield\"></i>";
                }

                // RESERVIERUNG VON EINEM GAST
                elseif ($search['searchstatus'] == "1" && $search['rid'] == "0") {
                 $status = "Das Gesuch ist <b>reserviert</b> für {$reservationname} (Gast)";
                 $statusicon = "<i class=\"fas fa-user-shield\"></i>";
                }
                else {
                 $status = "Das Gesuch ist vergeben";
                 $statusicon = "<i class=\"fas fa-lock\"></i>";
                }

                // RESERVIERUNG - USER
                if ($search['searchstatus'] == "0" && $mybb->user['uid'] != '0' && $mybb->user['uid'] != $search['wantedby']) {
                    $reservations = "<a href='misc.php?action=shortsearch&resuser={$search['sid']}'><i class=\"fas fa-user-shield\" style=\"float:none\"></i></a>";
                } 
                elseif ($search['searchstatus'] == "0" && $mybb->user['uid'] == '0' && $mybb->user['uid'] != $search['wantedby']) {
                    eval("\$reservations = \"" . $templates->get("shortsearch_guest_reservation") . "\";");
                }
                else {
                    $reservations = "";    
                }

                eval("\$shortsearch .= \"" . $templates->get("shortsearch_bit") . "\";");
            }

            eval("\$shortsearch_category .= \"" . $templates->get ("shortsearch_category") . "\";");
        }

        $rid = $mybb->user['uid'];

        // RESERVIERUNG - USER
	$reservations = $mybb->input['resuser'];
    if($reservations){

        $reservation_user = array(
			"rid" => (int)$mybb->user['uid'],
			"searchstatus" => "1",
        );

        $res_query = $db->query("SELECT wantedby
            from ".TABLE_PREFIX."shortsearch
           WHERE sid = '".$reservations."'
            ");

        $privat = $db->fetch_array($res_query);
        $title = $privat['searchtitle'];
        $uid = $privat['wantedby'];

        $pm_change = array(
            "subject" => "Dein Kurzgesuch wurde von einem User reserviert",
            "message" => "{$lang->shortsearch_pm_user}",
            //to: wer muss die anfrage bestätigen
            "fromid" => $rid,
            //from: wer hat die anfrage gestellt
            "toid" => $uid
        );
        // $pmhandler->admin_override = true;
        $pmhandler->set_data ($pm_change);
        if (!$pmhandler->validate_pm ())
            return false;
        else {
            $pmhandler->insert_pm ();
        }

        $db->update_query("shortsearch", $reservation_user, "sid = '".$reservations."'");
        redirect("misc.php?action=shortsearch");
    }

    // TEAM-ID AUS DEN EINSTELLUNGEN ZIEHEN
    $teamuid = $mybb->settings['shortsearch_teamuid'];

    // RESERVIERUNG - GÄSTE
    $reservation = $mybb->input['resguest'];
    if($reservation) {
        $reservation_guest = array(
            "reservationname" => $db->escape_string($mybb->get_input('reservationname')),
            "reservationtext" => $db->escape_string($mybb->get_input('reservationtext')),
			"searchstatus" => "1",
        );

        $res_guest_query = $db->query("SELECT * FROM ".TABLE_PREFIX."shortsearch
           WHERE sid = '".$reservation."'
            ");

        $privat = $db->fetch_array($res_guest_query);
        $reservationtext =  $db->escape_string($mybb->get_input('reservationtext'));
        $uid = $privat['wantedby'];

        $pm_change = array(
            "subject" => "Dein Kurzgesuch wurde von einem Gast reserviert!",
            "message" => "Der Gast hat dir eine Nachricht hinterlassen: ".$reservationtext." ",
            //to: wer muss die anfrage bestätigen
            "fromid" => $teamuid,
            //from: wer hat die anfrage gestellt
            "toid" => $uid
        );
        // $pmhandler->admin_override = true;
        $pmhandler->set_data ($pm_change);
        if (!$pmhandler->validate_pm ())
            return false;
        else {
            $pmhandler->insert_pm ();
        }

        $db->update_query("shortsearch", $reservation_guest, "sid = '".$reservation."'");
        redirect("misc.php?action=shortsearch");
    }

        eval("\$shortsearch_filter .= \"" . $templates->get("shortsearch_filter") . "\";");
        eval("\$page = \"".$templates->get("shortsearch")."\";");
        output_page($page);
	      die();
}

    // KURZGESUCH HINZUFÜGEN
    if($mybb->input['action'] == "shortsearch_add") {

     // NUR DIE GRUPPEN, DIE HINZUFÜGEN DÜRFEN SEHEN DIESE SEITE   
    if(is_member($mybb->settings['shortsearch_allow_groups'])) {
     
      // NAVIGATION
      add_breadcrumb ($lang->shortsearch, "misc.php?action=shortsearch"); 
     add_breadcrumb($lang->shortsearch_add, "misc.php?action=shortsearch_add");

     // AUSWAHLMÖGLICHKEIT DER KATEGORIE AUTOMATISCH AUS DEN EINSTELLUNGEN ERSTELLEN

     // KATEGORIEN
     $shortsearch_cat_setting = $mybb->settings['shortsearch_category'];
        $shortsearch_cat = explode (", ", $shortsearch_cat_setting);
        foreach ($shortsearch_cat as $cat) {
            $cat_select .= "<option value='{$cat}'>{$cat}</option>";
        }
        
    // GESCHLECHT
     $shortsearch_gender_setting = $mybb->settings['shortsearch_gender'];
        $shortsearch_gender = explode (", ", $shortsearch_gender_setting);
        foreach ($shortsearch_gender as $gender) {
            $gender_select .= "<option value='{$gender}'>{$gender}</option>";
        }
        
    // BEZIEHUNGSSTATUS
     $shortsearch_relation_setting = $mybb->settings['shortsearch_relation'];
        $shortsearch_relation = explode (", ", $shortsearch_relation_setting);
        foreach ($shortsearch_relation as $relation) {
            $relation_select .= "<option value='{$relation}'>{$relation}</option>";
        }

        if ($mybb->input['submit']) {
            $new_shortsearch = array(
                "type" => $db->escape_string($mybb->get_input('type')),
                "searchtitle" => $db->escape_string($mybb->get_input('searchtitle')),
                "searchage" => $db->escape_string($mybb->get_input('searchage')),
                "searchgender" => $db->escape_string($mybb->get_input('searchgender')),
                "searchrelationstatus" => $db->escape_string($mybb->get_input('searchrelationstatus')),
                "searchjob" => $db->escape_string($mybb->get_input('searchjob')),
                "searchrelation" => $db->escape_string($mybb->get_input('searchrelation')),
                "searchtext" => $db->escape_string($mybb->get_input('searchtext')),
                "searchavatar" => $db->escape_string($mybb->get_input('searchavatar')),
                "wantedby" => (int)$mybb->user['uid'],
                "searchstatus" => "0",
                "rid" => (int)$mybb->user['rid'],
                "reservationname" => $db->escape_string($mybb->get_input('reservationname')),
                "reservationtext" => $db->escape_string($mybb->get_input('reservationtext')),
            );
            $db->insert_query("shortsearch", $new_shortsearch);
            $db->query("UPDATE ".TABLE_PREFIX."users SET shortsearch_new ='0'");
            redirect("misc.php?action=shortsearch");
        }
        
    eval("\$page = \"".$templates->get("shortsearch_add")."\";");
    output_page($page);
	      die();
    }
    else {
        error_no_permission();
    }
}

//Trage ein, wenn ein User angegeben hat, dass er die Info, dass es neue interne Gesuche gibt, gelesen hat
if ($mybb->get_input ('action') == 'shortsearch_read') {

    //welcher user ist online
    $this_user = intval ($mybb->user['uid']);

//für den fall nicht mit hauptaccount online
    $as_uid = intval ($mybb->user['as_uid']);
    $read = $mybb->input['read'];
    if ($read) {
        if($as_uid == 0){
            $db->query ("UPDATE " . TABLE_PREFIX . "users SET shortsearch_new = 1  WHERE (as_uid = $this_user) OR (uid = $this_user)");
        }elseif ($as_uid != 0){
            $db->query ("UPDATE " . TABLE_PREFIX . "users SET shortsearch_new = 1  WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid)");
        }
        redirect("index.php");
    }
}


    // EIGENE KURZGESUCHE
    if($mybb->input['action'] == "shortsearch_own") {

        // NUR DIE GRUPPEN, DIE HINZUFÜGEN DÜRFEN SEHEN DIESE SEITE   
       if(is_member($mybb->settings['shortsearch_allow_groups'])) {

        // NAVIGATION
      add_breadcrumb ($lang->shortsearch, "misc.php?action=shortsearch");
     add_breadcrumb($lang->shortsearch_own, "misc.php?action=shortsearch_own");

     // ACCOUNTSWITCHER 
     //welcher user ist online
        $this_user = intval($mybb->user['uid']);

//für den fall nicht mit hauptaccount online
        $as_uid = intval($mybb->user['as_uid']);

      // suche alle angehangenen accounts
  // as uid = 0 wenn hauptaccount oder keiner angehangen
  $charas = array();
  if ($as_uid == 0) {
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username");
  } else if ($as_uid != 0) {
    //id des users holen wo alle angehangen sind 
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username");
  }
  while ($users = $db->fetch_array($get_all_users)) {
    $uid = $users['uid'];
    $charas[$uid] = $users['username'];
  }

     // KATEGORIEN AUS DEN EINSTELLUNGEN ZIEHEN UND AUFSPALTEN
     $shortsearch_cat_setting = $mybb->settings['shortsearch_category'];
     $type = explode (", ", $shortsearch_cat_setting);

     foreach ($type as $typ) {
         $shortsearch = "";
         eval("\$shortsearch_none = \"".$templates->get("shortsearch_none")."\";");

         foreach ($charas as $uid => $charname) {
            $query = $db->query("SELECT * FROM ".TABLE_PREFIX."shortsearch
            LEFT JOIN ".TABLE_PREFIX."users
            ON ".TABLE_PREFIX."users.uid = ".TABLE_PREFIX."shortsearch.wantedby
            LEFT JOIN ".TABLE_PREFIX."userfields
            ON ".TABLE_PREFIX."userfields.ufid = ".TABLE_PREFIX."shortsearch.wantedby
            WHERE wantedby = {$uid}
            AND type = '$typ'
            ORDER by searchtitle ASC");
        
        

         while ($search = $db->fetch_array ($query)) {
         $shortsearch_none = "";

             // LEER LAUFEN LASSEN 
             $sid = "";
             $title = "";
             $gender = "";
             $age = "";
             $relationstatus = "";
             $job = "";
             $relation = "";
             $text = "";
             $avatar = "";
             $status = "";
             $wantedby = "";
             $rid = "";

             // MIT INFORMATIONEN FÜLLEN
             $avatar = $search['searchavatar'];
             $sid = $search['sid'];
             $rid = $search['rid'];
             $title = $search['searchtitle'];
             $gender = $search['searchgender'];
             $age = $search['searchage'];
             $relationstatus = $search['searchrelationstatus'];
             $job = $search['searchjob'];
             $relation = $search['searchrelation'];
             $text = $search['searchtext'];
             $reservationname = $search['reservationname'];

             // CHARAKTERNAME
             $username = format_name($search['username'], $search['usergroup'], $search['displaygroup']);
             $charaname = build_profile_link($username, $search['wantedby']);

             // GESUCHSSTATUS - ANZEIGE
             if ($search['searchstatus'] == "0") {
                $status = "Das Gesuch ist <b>frei</b>";
                $statusicon = "<i class=\"fas fa-lock-open\"></i>";
               }

               // RESERVIERT VON EINEM USER
               elseif ($search['searchstatus'] == "1" && $search['rid'] != "0") {

                // DATEN ZIEHEN VON DEM USER, WELCHER RESERVIERT HAT
                $reservations_user = $db->query("SELECT * FROM ".TABLE_PREFIX."users
                LEFT JOIN ".TABLE_PREFIX."userfields
                ON ".TABLE_PREFIX."userfields.ufid = '$rid'
                WHERE uid = '$rid'
                ");
            
                $user = $db->fetch_array($reservations_user);

                // SPIELERNAME
               if ($user[$playerfid] == "") {
                $resname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                $spitzname = build_profile_link($resname, $user['uid']); 
            } else {
                $spitzname = build_profile_link($user[$playerfid], $user['uid']);
            }

                $status = "Das Gesuch ist <b>reserviert</b> für {$spitzname}";
                $statusicon = "<i class=\"fas fa-user-shield\"></i>";
               }

               // RESERVIERUNG VON EINEM GAST
               elseif ($search['searchstatus'] == "1" && $search['rid'] == "0") {
                $status = "Das Gesuch ist <b>reserviert</b> für {$reservationname} (Gast)";
                $statusicon = "<i class=\"fas fa-user-shield\"></i>";
               }
               else {
                $status = "Das Gesuch ist vergeben";
                $statusicon = "<i class=\"fas fa-lock\"></i>";
               }

             // LÖSCHEN UND BEARBEITEN VON KURZGESUCHE
				$option = "<a href=\"misc.php?action=shortsearch_own&delsearch={$search['sid']}\">Löschen</a> # <a href=\"misc.php?action=shortsearch_edit&sid={$sid}\">Bearbeiten</a> # <a href=\"misc.php?action=shortsearch_own&take={$search['sid']}\">Erledigt</a> # <a href=\"misc.php?action=shortsearch_own&free={$search['sid']}\">Reservierung lösen</a>";


             eval("\$shortsearch .= \"" . $templates->get("shortsearch_own_bit") . "\";");
         }
        }

         eval("\$shortsearch_category .= \"" . $templates->get ("shortsearch_category") . "\";");
     }

     // KURZGESUCH LÖSCHEN
	$delete = $mybb->input['delsearch'];
	if($delete) {
		$db->delete_query("shortsearch", "sid = '$delete'");
		redirect("misc.php?action=shortsearch_own");
	}

        // KURZGESUCH ALS ERLEDIGT MARKIEREN
	$taken = $mybb->input['take'];
    if($taken){
        $take = array(
			"searchstatus" => "2",
        );

        $db->update_query("shortsearch", $take, "sid = '".$taken."'");
        redirect("misc.php?action=shortsearch_own");
    }

        // RESERVIERUNG LÖSEN
	$free = $mybb->input['free'];
    if($free){
        $free_search = array(
			"searchstatus" => "0",
            "rid" => (int)$mybb->user['rid'],
            "reservationname" => $db->escape_string($mybb->get_input('reservationname')),
        );

        $db->update_query("shortsearch", $free_search, "sid = '".$free."'");
        redirect("misc.php?action=shortsearch_own");
    }

     eval("\$page = \"".$templates->get("shortsearch")."\";");
     output_page($page);
	         die();

    }
    else {
        error_no_permission();
    }
}

    // KURZGESUCH BEARBEITEN
    if($mybb->input['action'] == "shortsearch_edit") {

      // NAVIGATION
      add_breadcrumb ($lang->shortsearch, "misc.php?action=shortsearch");
      add_breadcrumb ($lang->shortsearch_edit, "misc.php?action=shortsearch_edit");

      $sid =  $mybb->get_input('sid', MyBB::INPUT_INT);

      // AUSWAHLMÖGLICHKEIT DER KATEGORIE AUTOMATISCH AUS DEN EINSTELLUNGEN ERSTELLEN
     // KATEGORIEN
     $shortsearch_cat_setting = $mybb->settings['shortsearch_category'];
        $shortsearch_cat = explode (", ", $shortsearch_cat_setting);
        foreach ($shortsearch_cat as $cat) {
            $cat_select .= "<option value='{$cat}'>{$cat}</option>";
        }
        
    // GESCHLECHT
     $shortsearch_gender_setting = $mybb->settings['shortsearch_gender'];
        $shortsearch_gender = explode (", ", $shortsearch_gender_setting);
        foreach ($shortsearch_gender as $gender) {
            $gender_select .= "<option value='{$gender}'>{$gender}</option>";
        }
        
    // BEZIEHUNGSSTATUS
     $shortsearch_relation_setting = $mybb->settings['shortsearch_relation'];
        $shortsearch_relation = explode (", ", $shortsearch_relation_setting);
        foreach ($shortsearch_relation as $relation) {
            $relation_select .= "<option value='{$relation}'>{$relation}</option>";
        }

      $edit_query = $db->query("
      SELECT * FROM ".TABLE_PREFIX."shortsearch
      WHERE sid = '".$sid."'
      ");

      $edit = $db->fetch_array($edit_query);

      // LEER LAUFEN LASSEN 
      $sid = "";
      $title = "";
      $gender = "";
      $age = "";
      $relationstatus = "";
      $job = "";
      $relation = "";
      $text = "";
      $avatar = "";
      $status = "";
      $wantedby = "";

      // MIT INFORMATIONEN FÜLLEN
      $type = $edit['type'];
      $avatar = $edit['searchavatar'];
      $sid = $edit['sid'];
      $title = $edit['searchtitle'];
      $gender = $edit['searchgender'];
      $age = $edit['searchage'];
      $relationstatus = $edit['searchrelationstatus'];
      $job = $edit['searchjob'];
      $relation = $edit['searchrelation'];
      $text = $edit['searchtext'];
      $reservationname = $edit['reservationname'];


      //Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten daten Überschrieben.
      if($_POST['edit_shortsearch']){
          $sid = $mybb->input['sid'];
          $edit_search = array(
              "type" => $db->escape_string($mybb->get_input('type')),
              "searchtitle" => $db->escape_string($mybb->get_input('searchtitle')),
              "searchage" => $db->escape_string($mybb->get_input('searchage')),
              "searchgender" => $db->escape_string($mybb->get_input('searchgender')),
              "searchrelationstatus" => $db->escape_string($mybb->get_input('searchrelationstatus')),
              "searchjob" => $db->escape_string($mybb->get_input('searchjob')),
              "searchrelation" => $db->escape_string($mybb->get_input('searchrelation')),
              "searchtext" => $db->escape_string($mybb->get_input('searchtext')),
              "searchavatar" => $db->escape_string($mybb->get_input('searchavatar')),
          );

          $db->update_query("shortsearch", $edit_search, "sid = '".$sid."'");
          redirect("misc.php?action=shortsearch_own");
      }
      eval("\$page = \"".$templates->get("shortsearch_edit")."\";");
      output_page($page);
	      die();

	}

}

// LÖSCHT KURGESUCHE VON GELÖSCHTEN USERN
function shortsearch_user_delete()
{
    global $db, $cache, $mybb, $user;
    $db->delete_query ('shortsearch', "wantedby = " . (int)$user['uid'] . " ");
}

// IM PROFIL ANZEIGEN LASSE
function shortsearch_member_profile_end(){
    global $db, $mybb, $lang, $templates, $memprofile, $shortsearch_profile;
	$lang->load('shortsearch');
    
    // EINSTELLUNG FÜR DIE FID FÜR DEN SPITZNAMEN
    $setting_profile = $mybb->settings['shortsearch_profile'];
    $playerfid = $mybb->settings['shortsearch_playerfid'];
    $playerfid = "fid".$playerfid;
    $member_uid = $mybb->get_input('uid', MyBB::INPUT_INT);

    // NUR WENN DIE EINSTELLUNG AUF JA STEHT
    if($setting_profile == 1){
        
        // ABFRAGE DER DATENBANKEN - SHORTSEARCH & USER & USERFIELDS
        $query = $db->query("SELECT * FROM ".TABLE_PREFIX."shortsearch 
        WHERE wantedby = $member_uid
        ORDER by searchtitle ASC");
    
        while ($search = $db->fetch_array ($query)) {
       
                    // LEER LAUFEN LASSEN 
                    $sid = "";
                    $title = "";
                    $gender = "";
                    $age = "";
                    $relationstatus = "";
                    $job = "";
                    $relation = "";
                    $avatar = "";
                    $status = "";
                    $reservationname = "";
                    $rid = "";
       
                    // MIT INFORMATIONEN FÜLLEN
                    $sid = $search['sid'];
                    $rid = $search['rid'];
                    $type = $search['type'];
                    $title = "<a href=\"misc.php?action=shortsearch\">{$search['searchtitle']}</a>";
                    $gender = $search['searchgender'];
                    $age = $search['searchage'];
                    $relationstatus = $search['searchrelationstatus'];
                    $job = $search['searchjob'];
                    $relation = $search['searchrelation'];
                    $avatar = $search['searchavatar'];
                    $reservationname = $search['reservationname'];
       
                    // GESUCHSSTATUS - ANZEIGE
                    if ($search['searchstatus'] == "0") {
                       $status = "Das Gesuch ist <b>frei</b>";
                       $statusicon = "<i class=\"fas fa-lock-open\"></i>";
                      }
       
                      // RESERVIERT VON EINEM USER
                      elseif ($search['searchstatus'] == "1" && $search['rid'] != "0") {

       // DATEN ZIEHEN VON DEM USER, WELCHER RESERVIERT HAT
                $reservations_user = $db->query("SELECT * FROM ".TABLE_PREFIX."users
                LEFT JOIN ".TABLE_PREFIX."userfields
                ON ".TABLE_PREFIX."userfields.ufid = '$rid'
                WHERE uid = '$rid'
                ");
            
                $user = $db->fetch_array($reservations_user);

                // SPIELERNAME
               if ($user[$playerfid] == "") {
                $resname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                $spitzname = build_profile_link($resname, $user['uid']); 
            } else {
                $spitzname = build_profile_link($user[$playerfid], $user['uid']);
            }
       
                       $status = "Das Gesuch ist <b>reserviert</b> für {$spitzname}";
                       $statusicon = "<i class=\"fas fa-user-shield\"></i>";
                      }
       
                      // RESERVIERUNG VON EINEM GAST
                      elseif ($search['searchstatus'] == "1" && $search['rid'] == "0") {
                       $status = "Das Gesuch ist <b>reserviert</b> für {$reservationname} (Gast)";
                       $statusicon = "<i class=\"fas fa-user-shield\"></i>";
                      }
                      else {
                       $status = "Das Gesuch ist vergeben";
                       $statusicon = "<i class=\"fas fa-lock\"></i>";
                      }
        
            eval("\$shortsearch_profile_bit .= \"".$templates->get("shortsearch_memprofile_bit")."\";");
        }
    
    eval("\$shortsearch_profile = \"".$templates->get("shortsearch_memprofile")."\";");
    }
    else {
        $shortsearch_profile = "";
    }
}

function shortsearch_modcp_nav()
{
    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_shortsearch;
    
    $lang->load('shortsearch');

    eval("\$nav_shortsearch= \"".$templates->get ("shortsearch_modcp_nav")."\";");
}

function shortsearch_modcp() {
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $page, $modcp_nav;

    // EINSTELLUNG FÜR DIE FID FÜR DEN SPITZNAMEN
    $playerfid = $mybb->settings['shortsearch_playerfid'];
    $playerfid = "fid".$playerfid;

    if($mybb->get_input('action') == 'shortsearch') {
        $lang->load('shortsearch');
        // Add a breadcrumb
        add_breadcrumb("Alle Kurzgesuche", "modcp.php?action=shortsearch");

        // ABFRAGE DER DATENBANKEN - SHORTSEARCH & USER & USERFIELDS
        $query = $db->query("SELECT * FROM ".TABLE_PREFIX."shortsearch
        LEFT JOIN ".TABLE_PREFIX."users
        ON ".TABLE_PREFIX."users.uid = ".TABLE_PREFIX."shortsearch.wantedby
        LEFT JOIN ".TABLE_PREFIX."userfields
        ON ".TABLE_PREFIX."userfields.ufid = ".TABLE_PREFIX."shortsearch.wantedby
        ORDER by searchtitle ASC");

        while ($search = $db->fetch_array ($query)) {

                // LEER LAUFEN LASSEN 
                $sid = "";
                $title = "";
                $status = "";
                $wantedby = "";
                $rid = "";
                $type = "";
                $reservationname = "";

                // MIT INFORMATIONEN FÜLLEN
                $sid = $search['sid'];
                $rid = $search['rid'];
                $type = $search['type'];
                $title = $search['searchtitle'];
                $reservationname = $search['reservationname'];
                $wantedby = $search['wantedby'];

                // CHARAKTERNAME
                $username = format_name($search['username'], $search['usergroup'], $search['displaygroup']);
                $charaname = build_profile_link($username, $search['wantedby']);

                // SPIELERNAME
                if ($search[$playerfid] == "") {
                    $spielername = ""; 
                } else {
                $spielername = "($search[$playerfid])";
                }

                // GESUCHSSTATUS - ANZEIGE
                if ($search['searchstatus'] == "0") {
                 $status = "<b>Frei</b>";
                }

                // RESERVIERT VON EINEM USER
                elseif ($search['searchstatus'] == "1" && $search['rid'] != "0") {

                $reservations_user = $db->query("SELECT * FROM ".TABLE_PREFIX."users
                LEFT JOIN ".TABLE_PREFIX."userfields
                ON ".TABLE_PREFIX."userfields.ufid = '$rid'
                WHERE uid = '$rid'
                ");
            
                $user = $db->fetch_array($reservations_user);

                // SPIELERNAME
               if ($user[$playerfid] == "") {
                $resname = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                $spitzname = build_profile_link($resname, $user['uid']); 
            } else {
                $spitzname = build_profile_link($user[$playerfid], $user['uid']);
            }

               

                 $status = "<b>Reserviert</b> für {$spitzname}";
                }

                // RESERVIERUNG VON EINEM GAST
                elseif ($search['searchstatus'] == "1" && $search['rid'] == "0") {
                 $status = "<b>Reserviert</b> für {$reservationname} (Gast)";
                }
                else {
                 $status = "<b>Vergeben</b>";
                }

                // LÖSCHEN UND BEARBEITEN VON KURZGESUCHE
				$option = "<a href=\"modcp.php?action=shortsearch&delsearch={$search['sid']}\">Löschen</a> # <a href=\"modcp.php?action=shortsearch&take={$search['sid']}\">Erledigt</a> # <a href=\"modcp.php?action=shortsearch&free={$search['sid']}\">Reservierung lösen</a>";
                 
                // KURZGESUCH LÖSCHEN
	$delete = $mybb->input['delsearch'];
	if($delete) {
		$db->delete_query("shortsearch", "sid = '$delete'");
		redirect("modcp.php?action=shortsearch");
	}

        // KURZGESUCH ALS ERLEDIGT MARKIEREN
	$taken = $mybb->input['take'];
    if($taken){
        $take = array(
			"searchstatus" => "2",
        );

        $db->update_query("shortsearch", $take, "sid = '".$taken."'");
        redirect("modcp.php?action=shortsearch");
    }

        // RESERVIERUNG LÖSEN
	$free = $mybb->input['free'];
    if($free){
        $free_search = array(
			"searchstatus" => "0",
            "rid" => (int)$mybb->user['rid'],
            "reservationname" => $db->escape_string($mybb->get_input('reservationname')),
        );

        $db->update_query("shortsearch", $free_search, "sid = '".$free."'");
        redirect("modcp.php?action=shortsearch");
    }

            eval("\$shortsearch_mod_bit .= \"".$templates->get("shortsearch_modcp_bit")."\";");
        }

        eval("\$page = \"".$templates->get("shortsearch_modcp")."\";");
        output_page($page);
	      die();

    }
}
