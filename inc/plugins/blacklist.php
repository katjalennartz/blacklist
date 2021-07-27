<?php

/**
 * automatische blacklistanzeige - by risuena
 * lslv.de
 * Risuena im sg
 * https://storming-gates.de/member.php?action=profile&uid=39
 */

// Fehleranzeige 
// error_reporting ( -1 );
// ini_set ( 'display_errors', true ); 

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function blacklist_info()
{
  return array(
    "name"      => "Blacklist 1.0",
    "description"  => "Der User wird auf dem Index oder/und per Mail automatisch gewarnt, wenn er auf der nächsten BL stehen würde + Übersichtsseite(https://forenadresse/blacklist_show.php)",
    "website"    => "https://github.com/katjalennartz",
    "author"    => "risuena",
    "authorsite"  => "https://github.com/katjalennartz",
    "version"    => "1.1",
    "compatibility" => "*"
  );
}


function blacklist_install()
{
  global $db, $cache;
  $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "blacklist` (
    `bid` int(10) NOT NULL AUTO_INCREMENT,
    `uid` int(10) NOT NULL,
    `strokes_cnt` int(10) NOT NULL,
    `stroke_date_last` int(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`bid`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

  $db->add_column("users", "blacklist_view", "INT(1) NOT NULL default '1'");
  $db->add_column("users", "blacklist_ice", "INT(1) NOT NULL default '1'");
  $db->add_column("users", "blacklist_ice_date", "datetime NOT NULL");


  //Einstellungen 
  $setting_group = array(
    'name' => 'blacklist',
    'title' => 'Blacklist',
    'description' => 'Einstellungen für die Blacklist',
    'disporder' => 6, // The order your setting group will display
    'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);

  $setting_array = array(
    'blacklist_duration' => array(
      'title' => 'Zeitraum?',
      'description' => 'Wie groß soll der Zeitraum sein, der von der Blacklist überpüft wird?',
      'optionscode' => 'numeric',
      'value' => '28', // Default
      'disporder' => 1
    ),

    'blacklist_date' => array(
      'title' => 'Datum der Blacklist',
      'description' => 'An welchem Tag des Monats erscheint eure Blacklist(1-31)? Achtung bei Februar und Monate ohne 31.!',
      'optionscode' => 'numeric',
      'value' => '1', // Default
      'disporder' => 2
    ),

    'blacklist_bewerbergruppe' => array(
      'title' => 'Gruppe für Bewerber',
      'description' => 'Wie ist die ID für eure Bewerbergruppe?',
      'optionscode' => 'numeric',
      'value' => '2', // Default
      'disporder' => 3
    ),
    //Dauer für Bewerber
    'blacklist_bewerberdauer' => array(
      'title' => 'Zeitraum für Bewerber',
      'description' => 'Wieviel Zeit(Tage) haben Bewerber einen Steckbrief zu posten?',
      'optionscode' => 'numeric',
      'value' => '10', // Default
      'disporder' => 4
    ),
    //Die fid (forenid) der Bewerbungsarea
    'blacklist_bewerberfid' => array(
      'title' => 'Bewerbungsarea',
      'description' => 'In welches Forum posten eure Bewerber die Steckbriefe(fid)?',
      'optionscode' => 'numeric',
      'value' => '16', // Default
      'disporder' => 5
    ),
    //ID fürs Ingame?
    'blacklist_ingame' => array(
      'title' => 'Ingamebereich',
      'description' => 'Wie ist die ID für euer Ingame?',
      'optionscode' => 'numeric',
      'value' => '4', // Default
      'disporder' => 6
    ),

    //ID fürs Archiv?
    'blacklist_archiv' => array(
      'title' => 'Archiv',
      'description' => 'Wenn die Posts im Archiv auch zählen sollen, die ID des Archivs eintragen, ansonsten bitte 0 eintragen.',
      'optionscode' => 'text',
      'value' => '29', // Default
      'disporder' => 7
    ),

    //Abwesenheit ja oder nein?
    'blacklist_away' => array(
      'title' => 'Abwesenheit beachten?',
      'description' => 'Soll beachtet werden, dass ein User abwesend gemeldet ist?.',
      "optionscode" => "yesno",
      'value' => '1', // Default
      'disporder' => 8
    ),
    //Eisliste ja oder nein?
    'blacklist_ice' => array(
      'title' => 'Eisliste',
      'description' => 'Können einzelne Charaktere auf Eis gelegt werden?
          Anzeigbar im Profil mit der Variable {$iceMeldung}',
      "optionscode" => "yesno",
      'value' => '1', // Default
      'disporder' => 9
    ),

    //ausgeschlossene user?
    'blacklist_excluded' => array(
      'title' => 'ausgeschlossene Benutzer',
      'description' => 'Gibt es Gruppen die ausgeschlossen werden sollen?',
      'optionscode' => 'groupselect',
      'value' => '0', // Default
      'disporder' => 10
    ),

    'blacklist_text' => array(
      'title' => 'Blacklist Text',
      'description' => 'Ein Text der auf dem Index angezeigt werden soll, wenn die Blacklist freigeschaltet wurde. Html möglich',
      'optionscode' => 'textarea',
      'value' => 'Die monatliche <a href="misc.php?action=show_blacklist">Blacklist</a> wurde veröffentlicht. <br>
      <a href="misc.php?action=show_blacklist&amp;set_blview=1">[okay]</a>', // Default
      'disporder' => 11
    ),

    'blacklist_show_user' => array(
      'title' => 'Ist die Blacklist aktiv?',
      'description' => 'Ist die Blacklist gerade aktiv und kann von den Usern eingesehen werden.',
      "optionscode" => "yesno",
      'value' => '0', // Default
      'disporder' => 12
    ),


  );


  foreach ($setting_array as $name => $setting) {
    $setting['name'] = $name;
    $setting['gid'] = $gid;
    $db->insert_query('settings', $setting);
  }
  rebuild_settings();

  //Task, der einmal im Monat -> angegeben in Settins bl_info wieder zurücksetzt. 
  $db->insert_query('tasks', array(
    'title' => 'Blacklist Meldung',
    'description' => 'An einem festgelegten Tag die Blacklist ausführen',
    'file' => 'blacklist',
    'minute' => '1',
    'hour' => '0',
    'day' => '1',
    'month' => '*',
    'weekday' => '*',
    'nextrun' => TIME_NOW,
    'lastrun' => 0,
    'enabled' => 1,
    'logging' => 1,
    'locked' => 0,
  ));
  $cache->update_tasks();

  $templategrouparray = array(
    'prefix' => 'blacklist',
    'title'  => $db->escape_string('Blacklist'),
    'isdefault' => 1
  );
  $db->insert_query("templategroups", $templategrouparray);

  //templates anlegen
  $insert_array = array(
    'title'    => 'blacklist_show_main',
    'template'  => '<html>
    <head>
    <title>{$mybb->settings[\'bbname\']}</title>
    {$headerinclude}
    
    </head>
    <body>
    {$header}
      <table border="0" cellspacing="0" cellpadding="5" class="tborder">
        <tr>
          <td class="thead forumbit_catdep1" colspan="5">Blacklist</td>
        </tr>
      {$blacklist_show_view}
      </table>
    {$footer}
    </body>
    </html>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_show_userbit',
    'template'  => '<tr>
    <td class="trow1" align="center" valign="top"><span class="blacklist_text">{$username}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$user[\'strokes_cnt\']}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$datestroke}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text" >{$user[\'tid\']}</span> <span class="blacklist_text">{$datepost}</span></td>
    <td class="trow1"  align="center" valign="top"> {$stroke} </td>
  </tr>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_show_userbitaway',
    'template'  => '<tr>
    <td class="trow1" align="center" valign="top"><span class="blacklist_text">{$username}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$away}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$awaydate}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text" >{$ice}</span> </td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$icedate}</span></td>
  </tr>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_show_userbitmod',
    'template'  => '<tr>
    <td class="trow1" align="center" valign="top"><span class="blacklist_text">{$username}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$user[\'strokes_cnt\']}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text">{$datestroke}</span></td>
    <td class="trow1"  align="center" valign="top"><span class="blacklist_text" >{$user[\'tid\']}</span> <span class="blacklist_text">{$datepost}</span></td>
    <td class="trow1"  align="center" valign="top"><a href="misc.php?action=show_blacklist&amp;delete={$user[\'uid\']}" onClick="return confirm(\'Möchtest du den Eintrag wirklich löschen?blacklist_show_viewmod\');">[von bl löschen]</a> 
    {$stroke}</td>
  </tr>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_show_viewmod',
    'template'  => '<tr>
    <td class="trow1" colspan="5" align="center"><span class="blacklist_text"><strong>Moderatorenansicht</strong></span>
    <p><span class="smalltext">Ist die Blacklist aktiv und kann von Usern eingesehen werden?
     <br/>
      <div class="mod_con">
      <form action="misc.php?action=show_blacklist" id="publish">
         <input type="radio" class="blactiv" name="blactiv" id="blactiv_yes" value="1" {$active_yes} /> <label for="blactiv_yes">ja</label>
        <input type="radio" class="blactiv" name="blactiv" id="blactiv_no" value="0" {$active_no} /> <label for="blactiv_no">nein</label><br />
        <input type="submit"  id="publish" name="publish" value="Veröffentlichen" onClick="return confirm(\'Achtung mit dem Veröffentlichen (Auswahl: ja) werden auch die Hauspunkte für alle User auf der BL abgezogen!\');">
      </form>	 
     
      <form action="misc.php?action=show_blacklist" id="blacklist_add">
         <input type="text" class="bl_adduser" name="bl_adduser" id="blactiv_add" value="username" /> <br />
        <input type="submit" name="bl_add" value="Hinzufügen">
      </form>	 
      </div>
     </span>
   </p>
   </td>
 </tr>
 <tr>
    <td class="trow1" colspan="5" align="center"><span class="blacklist_text"><strong>User auf der Blacklist</strong></span></td>
 </tr>
 <tr>
   <td class="trow1" align="center"><span class="blacklist_text">Username</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Wie oft gestrichen?</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Wann zuletzt gestrichen</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Letzter Post</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Action</span></td>
 </tr>
 {$blacklist_show_userbitmod}
 <tr>
    <td class="trow1" colspan="5" align="center"><hr></td>
 </tr>
    <td class="trow1" colspan="5" align="center"><span class="blacklist_text"><strong>Diese Charaktere sind abwesend oder auf Eis</strong></span></td>
 </tr>
 
 <tr>
   <td class="trow1" align="center"><span class="blacklist_text">Username</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">away?</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Seit Wann?</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">auf Eis?</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Seit Wann?</span></td>
 </tr>
 {$blacklist_show_userbitaway}',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_show_viewuser',
    'template'  => '<tr>
    <td class="trow1" colspan="5" align="center"><span class="blacklist_text"><strong>User auf der Blacklist</strong></span></td>
 </tr>
 <tr>
   <td class="trow1" align="center"><span class="blacklist_text">Username</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Wie oft gestrichen?</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Wann zuletzt gestrichen</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Letzter Post</span></td>
   <td class="trow1"  align="center"><span class="blacklist_text">Action</span></td>
 </tr>
 {$blacklist_show_userbit}',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_ucp',
    'template'  => '<div class="blacklist_ucp_wrapper">
    <h1>Folgende deiner Charaktere würden auf der nächsten Blacklist stehen:</h1>
    {$blacklist_ucp_bit}
  </div>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_ucp_bewerber_bit',
    'template'  => '<div class="blacklist_ucp_user">
    {$username} hat noch keinen Steckbrief gepostet
  </div>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_ucp_bit_3month',
    'template'  => '<div class="blacklist_ucp_user">
    <span style="color: red;"> {$username} hat über <strong>3 Monate</strong> nicht gepostet. -> Postpflicht </span>
    </div>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_ucp_edit',
    'template'  => '<fieldset class="trow2">
    <legend><strong>Eisliste</strong></legend>
    <table cellspacing="0" cellpadding="0">
    <tr>
    <td colspan="2"><span class="smalltext"><p>Einmal im Jahr kannst du deinen Charakter auf Eis legen und so vor der Blacklist schützen.</span></p>
    {$ice_input}
      </td>
      </tr>
    </table>
    </fieldset>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_ucp_user_bit',
    'template'  => '<div class="blacklist_ucp_user">
    {$username} hat seit {$days} Tag(en) nicht gepostet.
  </div>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklist_index_alert',
    'template'  => '<div class="red_alert blacklist_info">
    {$blacklist_index_info}
  <a href="misc.php?action=show_blacklist&amp;hide_view=0">[Meldung verbergen und zur Blacklist]</a>
  </div>',
    'sid'    => '-2',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  //Task, der einmal im Monat -> angegeben in Settins bl_info wieder zurücksetzt. 
  $db->insert_query('tasks', array(
    'title' => 'Blacklist',
    'description' => 'Stellt einmal im Monat die Blacklist zusammen.',
    'file' => 'setHauspunkte',
    'minute' => '01',
    'hour' => '00',
    'day' => '1',
    'month' => '*',
    'weekday' => '*',
    'nextrun' => TIME_NOW,
    'lastrun' => 0,
    'enabled' => 1,
    'logging' => 1,
    'locked' => 0,
  ));
  $cache->update_tasks();
}


//überprüft ob das Plugin in installiert ist
function blacklist_is_installed()
{
  global $db;
  if ($db->table_exists("blacklist")) {
    return true;
  }
  return false;
}

//Deinstallation des Plugins
function blacklist_uninstall()
{
  global $db, $cache;
  //ist das Plugin überhaupt installiert? 
  if ($db->field_exists("bl_info", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP blacklist_view");
  }
  if ($db->table_exists("blacklist")) {
    $db->drop_table("blacklist");
  }
  // Einstellungen entfernen
  $db->delete_query("templates", "title LIKE 'blacklist_%'");
  $db->delete_query('settinggroups', "name = 'blacklist'");
  //templates noch entfernen
  rebuild_settings();
  // Task löschen
  $db->delete_query('tasks', "file='blacklist'");
  $cache->update_tasks();

  $db->delete_query("templates", "title LIKE 'blacklist_%'");
  $db->delete_query('tasks', "file='blacklist'");
  $cache->update_tasks();
}

//Plugin Aktivieren
function blacklist_activate()
{
  global $db;

  include  MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", "#" . preg_quote('{$header}') . "#i", '{$header}{$blacklist_index}');
  //einfügen im profil
  find_replace_templatesets("usercp", "#" . preg_quote('{$latest_subscribed}') . "#i", '{$blacklist_ucp}{$latest_subscribed}');
  find_replace_templatesets("usercp_profile", "#" . preg_quote('{$contactfields}') . "#i", '{$contactfields}{$blacklist_ucp_edit}');

  //enable task
  $db->update_query('tasks', array('enabled' => 1), "file = 'blacklist'");
}

function blacklist_deactivate()
{
  global $db;
  include  MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", "#" . preg_quote('{$blacklist_index}') . "#i", '');
  //im profil noch entfernen
  find_replace_templatesets("usercp", "#" . preg_quote('{$blacklist_ucp}') . "#i", '');
  find_replace_templatesets("usercp_profile", "#" . preg_quote('{$blacklist_ucp_edit}') . "#i", '');


  // Disable the task
  $db->update_query('tasks', array('enabled' => 0), "file = 'blacklist'");
}

//Das Datum der Tasks anpassen, wenn im Adminbereich etwas geändert wird
//-> Wann wird die Warnung für alle User wieder angezeigt, auch wenn sie die zwischenzeitlich ausgeblendet haben. Also zum Beispiel eine woche vor der Blacklist
//Ausnahme: Der User wünscht ausdrücklich keine Warnung bei keinem Charakter
$plugins->add_hook("admin_config_settings_change", "blacklist_editTask");
function blacklist_editTask()
{
  global $db, $mybb;
  $var = $mybb->input['upsetting']['blacklist_days'];
  $db->update_query('tasks', array('day' => $var), "file = 'blacklist'");
}


$plugins->add_hook('usercp_start', 'blacklist_usercp_show');
function blacklist_usercp_show()
{
  global $db, $mybb, $templates, $blacklist_ucp;


  $opt_bl_days = intval($mybb->settings['blacklist_duration']); //Zeitraum
  $opt_bl_ingame = intval($mybb->settings['blacklist_ingame']);
  $opt_bl_archiv = intval($mybb->settings['blacklist_archiv']);
  $opt_bl_as = intval($mybb->settings['blacklist_as']);

  $opt_bewerber_days = intval($mybb->settings['blacklist_bewerberdauer']);
  $opt_bewerber = intval($mybb->settings['blacklist_bewerbergruppe']);
  $opt_steckiarea = intval($mybb->settings['blacklist_bewerberfid']);

  //Archiv ja/Nein?
  if ($opt_bl_archiv == 0) {
    $archiv = "";
  } else {
    $archiv =  " OR concat(',',parentlist,',') LIKE '%," . $opt_bl_archiv . ",%'";
  }

  $thisuser = $mybb->user['uid'];
  $charas = get_allcharsBL($thisuser);
  // $today = new DateTime();
  $today = new DateTime(date("Y-m-d H:i:s"));
  foreach ($charas as $uid => $username) {
    //Welche Benutzer gruppe? 
    $user = get_user($uid);

    if ($user['usergroup'] == $opt_bewerber || $user['usergroup'] == 5) {
      // schauen ob es einen post vom user in der steckiarea gibt
      $regdate = gmdate("Y-m-d H:i:s", $user['regdate']);
      $get_stecki = $db->write_query("
                        SELECT *, datediff(FROM_UNIXTIME(dateline), '{$regdate}' ) as diff FROM 
                        " . TABLE_PREFIX . "threads WHERE uid = {$uid} and fid = {$opt_steckiarea}
                        ");
      //in Array umwandeln mit dem wir arbeiten können
      $stecki = $db->fetch_array($get_stecki);

      if ($db->num_rows($get_stecki) > 0) {
        //es gibt einen erledigten beitrag in der bewerbungsares
        if ($stecki['threadsolved'] == 1) {
          //tu nichts
          //es gibt einen beitrag in der bewerbungsares aber nicht erledigt
        } elseif ($stecki['threadsolved'] == 0 && $stecki['diff'] > $opt_bewerber_days) {
          eval("\$blacklist_ucp_bit .=\"" . $templates->get("blacklist_ucp_bewerber_bit") . "\";");
        }
        //es gibt keinen stecki beitrag
      } else {
        //reg date holen und diff zu heute
        $regidate = new DateTime();
        $regidate->setTimestamp($user['regdate']); //wir benutzen set timestamp weil unix timestamp
        $interval = $regidate->diff($today); // <--- hier rechnen wir das intervall aus
        //vergleich mit erlaubter dauer
        if ($interval->days > $opt_bewerber_days) {
          // dauer überschritten -> auf blacklist
          eval("\$blacklist_ucp_bit .=\"" . $templates->get("blacklist_ucp_bewerber_bit") . "\";");
        }
      }
    } else {
      //angenommener user
      //Wir holen uns den neusten Post aus dem Ingame und Archiv
      $get_posts = $db->query("SELECT *,FROM_UNIXTIME(dateline) as date, DATEDIFF(CURDATE(),FROM_UNIXTIME(dateline)) as diff FROM 
      (SElECT uid, username, fid, tid, pid, dateline as dateline FROM  " . TABLE_PREFIX . "posts WHERE uid = {$uid} AND visible != '-2') as up 
        INNER JOIN
      (SELECT fid FROM " . TABLE_PREFIX . "forums WHERE concat(',',parentlist,',') LIKE '%," . $opt_bl_ingame . ",%' " . $archiv . ") as fids
      ON fids.fid = up.fid
        ORDER by dateline DESC
      LIMIT 1");
      //Gibt es einen Post?
      $post = $db->fetch_array($get_posts);
      $days = $post['diff'];
      if ($db->num_rows($get_posts)) {
        // wenn es einen gibt, überprüfe ob er länger her ist als erlaubt ($post['diff'] enthält die Tage, von letzten post zu heute) 
        if ($post['diff'] > $opt_bl_days) {
          //der charakter ist nicht away und nicht auf eis
          if ($user['away'] != 1 && $user['blacklist_ice'] != 1) {
            //muss auf die blackliste
            eval("\$blacklist_ucp_bit .=\"" . $templates->get("blacklist_ucp_user_bit") . "\";");
          }
          if (($user['away'] == 1 && $post['diff'] > 91)) {
            //user ist away, aber die 3 Monatsregel greift -> blacklist
            eval("\$blacklist_ucp_bit .=\"" . $templates->get("blacklist_ucp_bit_3month") . "\";");
          }
          if ($user['blacklist_ice'] == 1) {
            //User auf Eis, keine ausgabe
          }
        }
        //gar kein post
      } else {
        //Der user  ist neu
        //wir wollen das datum vom steckbrief in den area für angenommene
        $steckidate = $db->fetch_array($db->write_query("SELECT * FROM 
                    (SElECT uid, username, fid, tid, dateline FROM  " . TABLE_PREFIX . "threads WHERE uid = {$uid} AND visible != '-2') as up 
                      INNER JOIN
                    (SELECT fid FROM " . TABLE_PREFIX . "forums WHERE concat(',',parentlist,',') LIKE '%,17,%') as fids
                    ON fids.fid = up.fid
                    LIMIT 1"));
        $stecki = new DateTime();
        //Datum speichern
        $stecki->setTimestamp($steckidate['dateline']);
        $interval = $stecki->diff($today); //Wie viele tage zu heute? 
        // Wenn größer als erlaubter Zeitraum -> auf Blacklist
        if ($interval->days > $opt_bl_days) {
          eval("\$blacklist_ucp_bit .=\"" . $templates->get("blacklist_ucp_user_bit") . "\";");
        }
      }
    }  //angenommener user ende
  }
  //get template main
  eval("\$blacklist_ucp =\"" . $templates->get("blacklist_ucp") . "\";");
}

$plugins->add_hook('usercp_profile_start', 'blacklist_edit_profile');
function blacklist_edit_profile()
{
  global $mybb, $db, $templates, $blacklist_ucp_edit;
  $thisuser = intval($mybb->user['uid']);
  $blacklist_ice = intval($mybb->user['blacklist_ice']);
  $blacklist_date = ($mybb->user['blacklist_ice_date']);
  // var_dump($blacklist_date);

  $since = date('d.m.Y', strtotime($blacklist_date));
  $is_away = false;
  $charas = get_allcharsBL($thisuser);
  foreach ($charas as $uid => $username) {
    $user = get_user($uid);

    if ($user['blacklist_ice'] == 1 && $thisuser != $uid) {
      $is_onice = true;
    }
  }

  // {$blacklist_ucp_edit}
  if ($blacklist_ice == 0 && $since == "30.11.-0001") {
    $ice_input = "<p>
    <span class=\"smalltext\">Soll dieser Charakter auf Eis gelegt werden?<br/>
    <input type=\"checkbox\" class=\"bl\" name=\"blIce\" value=\"1\" /> ja</span>
    </p>";
  } elseif ($blacklist_ice == 0 && $since != "30.11.-0001") {
    $ice_input = "<p>
    <span class=\"smalltext\">Sorry, es ist noch kein Jahr her, dass du diesen Charakter auf Eis gelegt hast.<br/>
    Das letzte Mal war am: <strong>" . $since . "</strong></span>
    </p>";
  } elseif ($blacklist_ice == 1) {
    $ice_input = "<p>
    <span class=\"smalltext\">Dein Charakter liegt seit dem <strong>" . $since . "</strong> auf Eis.<br/>
     <strong>Auftauen?</strong><br/></span>
    <input type=\"checkbox\" class=\"bl\" id=\"blIce\" name=\"blIce\" value=\"0\"/> 
    <label for=\"blIce\">ja</label>
    </p>";
  }

  if ($is_onice) {
    $ice_input = "Sorry, du hast schon einen anderen Charakter auf Eis gelegt.";
  }

  eval("\$blacklist_ucp_edit.=\"" . $templates->get("blacklist_ucp_edit") . "\";");
}

$plugins->add_hook('usercp_do_profile_start', 'blacklist_edit_profile_do');
function blacklist_edit_profile_do()
{
  global $mybb, $db;
  $thisuser = $mybb->user['uid'];
  $blacklistAlert_ice = intval($mybb->input['blIce']);
  if (intval($mybb->input['blIce']) == 1) {
    $update = array(
      "blacklist_ice" => intval($mybb->input['blIce']),
      "blacklist_ice_date" => date("Y.m.d H:i")
    );
  } elseif (intval($mybb->input['blIce']) == 0) {
    $update = array(
      "blacklist_ice" => intval($mybb->input['blIce'])
    );
  }
  $db->update_query("users", $update, "uid='{$thisuser}'");
}

/**
 * Die magische coole Blacklistausgabe
 */
$plugins->add_hook("misc_start", "blacklist_show");
function blacklist_show()
{
  global $mybb, $db, $templates, $blacklist_show_main, $active_yes, $active_no, $header, $footer, $theme, $headerinclude, $blacklist_show_view, $lang, $blacklist_show_userbitaway;
  if ($db->table_exists("hauspunke")) {
    $lang->load('hauspunkte');
  }
  $thisuser = intval($mybb->user['uid']);
  $showuser = $mybb->settings['blacklist_show_user'];
  $ismod = $mybb->usergroup['canmodcp'];
  $blacklistpoints = $mybb->settings['hauspunkte_blacklist'];
  $blacklist_user = $db->simple_select("blacklist", "*", "", array("order_by" => 'username'));
  if (isset($mybb->input['bl_add'])) {
    $value = intval($mybb->input['blactiv']);
    $username = $db->escape_string($mybb->input['bl_adduser']);
    $query = $db->simple_select("users", "*", "username='" . $username . "'");
    $uid = $db->fetch_field($query, "uid");
    $insert = array(
      "uid" => $uid,
      "username" => $username,
    );
    $db->insert_query("blacklist", $insert);
    redirect("misc.php?action=show_blacklist");
  }

  if (isset($mybb->input['publish'])) {
    $value = intval($mybb->input['blactiv']);
    $db->write_query("UPDATE " . TABLE_PREFIX . "users SET blacklist_view = '1'");
    $db->write_query("UPDATE " . TABLE_PREFIX . "settings SET value = '" . $value . "' WHERE name='blacklist_show_user'");
    rebuild_settings();
    if ($value == 1) {
      while ($user = $db->fetch_array($blacklist_user)) {
        //Mails verschicken
        $forumname = $db->escape_string($mybb->settings['bbname']);
        $username = $db->escape_string($user['$username']);
        $subject = "Blacklist " . $forumname;
        $url = $mybb->settings['bburl'] . "misc.php?action=show_blacklist";
        $message = "Hallo {$username}<br /> 
        Diese Mail bekommen in der Regel nur die User, die auf der Blacklist gelandet sind. 
        Da du diese eMail gerade liest, solltest du dich, sofern du noch Interesse daran hast Mitglied im {$forumname} zu sein, 
        von der Blacklist streichen oder posten. Dazu hast du bis zum 08. des aktuellen Monats Zeit, denn an diesem Tag wird unsere Blacklist gelöscht.<br/>
        Zur unserer aktuellen Blacklist geht es <a href=\"{$url}\">hier</a> entlang!<br />
      
        Solltest du diese Mail bekommen, obwohl du nicht auf der Blacklist stehst, darfst du diese sehr gerne einfach ignorieren :)<br/>
        
        Danke und liebe Grüße,<br/>
        das Team des Golden Days";
        my_mail($user['email'], $subject, $message);

        //Hauspunkte abziehen
        if ($db->table_exists("hauspunke")) {
          $db->write_query("UPDATE " . TABLE_PREFIX . "users SET hauspunkte_points = hauspunkte_points - {$blacklistpoints}");
          $insert = array(
            "uid" => $user['uid'],
            "points" => "-" . $blacklistpoints,
            "reason" => $lang->hauspunkte_blacklist,
          );
          $db->insert_query("hauspunkte", $insert);
        }
      }
    } elseif ($value == 0) {
      $db->write_query("UPDATE " . TABLE_PREFIX . "users SET blacklist_view = '0'");

      $db->write_query("UPDATE " . TABLE_PREFIX . "settings SET value = '" . $value . "' WHERE name='blacklist_show_user'");
      rebuild_settings();
    }
    redirect("misc.php?action=show_blacklist");
  }

  if (isset($mybb->input['stroke'])) {
    $uid = intval($mybb->input['stroke']);
    $strokes = $db->fetch_field($db->simple_select("blacklist", "strokes_cnt", "uid = $uid"), "strokes_cnt");
    if (($thisuser == $uid && $strokes < 2) || $mybb->usergroup['canmodcp'] == 1) {
      $strokes = $strokes + 1;
      $insert = array(
        "uid" => $uid,
        "strokes_cnt" => $strokes,
        "stroke_date_last" => date("Y-m-d H:i")
      );

      $db->update_query("blacklist", $insert, "uid = {$uid}");
    }
    redirect("misc.php?action=show_blacklist");
  }


  if ($mybb->input['action'] == "show_blacklist") {
    if ($showuser == 1) {
      $active_yes = "checked";
      $active_no = "";
    } else {
      $active_no = "checked";
      $active_yes = "";
    }

    if ($ismod == 1) {
      if ($mybb->input['delete']) {
        $to_delete = intval($mybb->input['delete']);
        $db->delete_query("blacklist", "uid = {$to_delete}");
        redirect("misc.php?action=show_blacklist");
      }
      while ($user = $db->fetch_array($blacklist_user)) {
        $uid = $user['uid'];
        $username = build_profile_link($user['username'], $uid);
        if ($user['tid'] == "0") {
          $user['tid'] = "";
        } else {
          $user['tid'] = "<a href=\"showthread.php?tid={$user['tid']}\">Link</a>";
        }
        $datestroke = date('d.m.y', strtotime($user['stroke_date_last']));
        $datepost = date('d.m.y', strtotime($user['date']));
        if ($datepost == "30.11.-1") {
          $datepost = "Kein Ingamebeitrag";
        }
        if ($datestroke == "30.11.-1") {
          $datestroke = "noch nie";
        }
        if ($showuser == 1) {
          $stroke = "<a href=\"misc.php?action=show_blacklist&amp;stroke=" . $uid . "\" onClick=\"return confirm('Möchtest du " . $user['username'] . " wirklich streichen?');\">[streichen]</a> ";
        } else {
          $stroke = "";
        }
        eval("\$blacklist_show_userbitmod .= \"" . $templates->get("blacklist_show_userbitmod") . "\";");
      }
      $away_user = $db->simple_select("users", "*", "away = 1 OR blacklist_ice = 1");

      while ($user = $db->fetch_array($away_user)) {
        $uid = $user['uid'];
        $username = build_profile_link($user['username'], $uid);
        $away = $awaydate = "";
        $ice = $icedate = "";
        if ($user['away'] == 1) {
          $away = "abwesend";
          $awaydate = date("d.m.Y", $user['awaydate']);
        }
        if ($user['blacklist_ice'] == 1) {
          $ice = "auf Eis";
          $icedate = date("d.m.Y", strtotime($user['blacklist_ice_date']));
        }
        eval("\$blacklist_show_userbitaway.= \"" . $templates->get("blacklist_show_userbitaway") . "\";");
      }
      eval("\$blacklist_show_view = \"" . $templates->get("blacklist_show_viewmod") . "\";");
    } elseif ($thisuser == 0) {
      $blacklist_show_view = "<tr>
          <td class=\"trow1\" colspan=\"5\" align=\"center\"><span class=\"blacklist_text\">Gäste haben keinen Zugriff auf die Blacklist.</span></td>
        </tr>";
    } else {
      if ($showuser == 1) {
        while ($user = $db->fetch_array($blacklist_user)) {
          $uid = $user['uid'];
          $username = build_profile_link($user['username'], $uid);
          $datestroke = date('d.m.y', strtotime($user['stroke_date_last']));
          $datemonth = date('m.y', strtotime($user['stroke_date_last']));
          $thismonth = date('m.y');

          $datepost = date('d.m.y', strtotime($user['date']));

          if ($datestroke == "30.11.-1") {
            $datestroke = "noch nie";
          }

          if ($thisuser == $uid) {
            if ($user['strokes_cnt'] >= 2) {
              $stroke = "streichen nicht möglich";
            } else {
              $stroke = "<a href=\"misc.php?action=show_blacklist&amp;stroke=" . $uid . "\" onClick=\"return confirm('Möchtest du dich wirklich streichen lassen?');\">[streichen]</a> ";
            }
          } else {
            $stroke = "";
          }
          if ($datemonth != $thismonth) {
            eval("\$blacklist_show_userbit .= \"" . $templates->get("blacklist_show_userbit") . "\";");
          }
        }
        eval("\$blacklist_show_view = \"" . $templates->get("blacklist_show_viewuser") . "\";");
      } else {
        $blacklist_show_view = "<tr>
        <td class=\"trow1\" colspan=\"5\" align=\"center\"><span class=\"blacklist_text\">Die Blacklist wird am 1. des Monats freigeschaltet.</span></td>
      </tr>";
      }
    }

    eval("\$blacklist_show_main= \"" . $templates->get("blacklist_show_main") . "\";");
    output_page($blacklist_show_main);
  }
}
$plugins->add_hook("index_start", "blacklist_index");
function blacklist_index()
{
  global $db, $mybb, $templates;
  $active = intval($mybb->input['blactiv']);
  $index_view = intval($mybb->user['blacklist_view']);
  $blacklist_index_info = $mybb->settings['blacklist_text'];
  if ($active == 1 && $index_view == 1) {
    eval("\$blacklist_index_alert .= \"" . $templates->get("blacklist_index_alert") . "\";");
  }
}

/***
 * Delete users from Blacklist, when they have posted
 */
$plugins->add_hook('newthread_do_newthread_start', 'blacklist_do_newthread');
function blacklist_do_newthread()
{
  global $db, $tid, $mybb, $fid;

  $opt_bl_ingame = intval($mybb->settings['blacklist_ingame']);
  $uid = $mybb->user['uid'];

  $parents = $db->fetch_field($db->write_query("SELECT CONCAT(',',parentlist,',') as parents FROM " . TABLE_PREFIX . "forums WHERE fid = $fid"), "parents");
  $ingame =  "," . $opt_bl_ingame . ",";
  $containsIngame = strpos($parents, $ingame);

  if ($containsIngame !== false) {
    $ingameflag = true;
  } else {
    $ingameflag = false;
  }

  if ($ingameflag == true) {
    $db->delete_query("blacklist", "uid = {$uid}");
  }

  // include "/inc/plugins/hauspunkte.php";
  // $uid = $mybb->user['uid'];

  // if (testParentFidHP($fid, "ingame")) {
  //   $db->delete_query("blacklist", "uid = {$uid}");
  // }
}


$plugins->add_hook("newreply_do_newreply_end", "blacklist_do_newreply");
function blacklist_do_newreply()
{
  global $db, $pid, $mybb, $fid;
  include "/inc/plugins/hauspunkte.php";
  $uid = $mybb->user['uid'];

  if (testParentFidHP($fid, "ingame")) {
    $db->delete_query("blacklist", "uid = {$uid}");
  }
}

/*#######################################
#Hilfsfunktion für Mehrfachcharaktere (accountswitcher)
#Alle angehangenen Charas holen
#an die Funktion übergeben: Wer ist Online, die dazugehörige accountswitcher ID (ID des Hauptcharas) 
#außerdem die Info, ob der Admin erlaubt, dass Charas auf Eis gelegt werden dürfen -> entsprechend ändert sich die Abfrage!
######################################*/
function get_allcharsBL($thisuser)
{
  global $mybb, $db;
  //wir brauchen die id des Hauptcharas
  $as_uid = $mybb->user['as_uid'];
  $charas = array();
  if ($as_uid == 0) {
    // as_uid = 0 wenn hauptaccount oder keiner angehangen
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $thisuser) OR (uid = $thisuser) ORDER BY username");
  } else if ($as_uid != 0) {
    //id des users holen wo alle angehangen sind 
    $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $thisuser) OR (uid = $as_uid) ORDER BY username");
  }
  while ($users = $db->fetch_array($get_all_users)) {

    $uid = $users['uid'];
    $charas[$uid] = $users['username'];
  }
  return $charas;
}

/****
 * //TODO 
 * Modlist ->
 * Anzeige abwesend und auf eis
 * //TODO Sprachvariablen überprüfuen hauspunkte
 */
