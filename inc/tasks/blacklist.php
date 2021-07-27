<?php

/**
 * blacklistAlert.php
 *
 * Blacklist plugin for MyBB 1.8
 * Automatische Anzeige der BL 
 * Aktualisierung des Feldes, BL Warnung ausblenden 
 *
 */
// error_reporting(-1);
// ini_set('display_errors', true);

/***
 * all the magic 
 * 
 */
function task_blacklist($task)
{
    global $db, $mybb, $lang;
    //settings 
    $opt_bl_days = intval($mybb->settings['blacklist_duration']); //Zeitraum

    $opt_bl_ingame = intval($mybb->settings['blacklist_ingame']);
    $opt_bl_archiv = intval($mybb->settings['blacklist_archiv']);
    $opt_bl_excluded = trim($mybb->settings['blacklist_excluded']);

    $opt_bewerber = intval($mybb->settings['blacklist_bewerbergruppe']);
    $opt_bewerber_days = intval($mybb->settings['blacklist_bewerberdauer']);
    $opt_steckiarea = intval($mybb->settings['blacklist_bewerberfid']);


    $build_string = "";
    
    //gelten posts im Archiv ja/Nein?
    if ($opt_bl_archiv == 0) {
        $archiv = "";
    } else {
        $archiv =  " OR concat(',',parentlist,',') LIKE '%," . $opt_bl_archiv . ",%'";
    }
    //ausgeschlossene gruppen
    $gids = explode(',', $mybb->settings['blacklist_excluded']);

    $today = new DateTime(date("Y-m-d H:i:s"));
    //alle user des boards
    $get_user = $db->simple_select("users", "*");

    while ($user = $db->fetch_array($get_user)) {
        $usergroup =  $user['usergroup'];
        //nur die gruppen die wir wollen
        if (!in_array($usergroup, $gids)) {
            $uid = $user['uid'];
            $username =  $user['username'];
            
            //Der User ist auf Eis gelegt. 
            //abfangen, dass datum leer ist
            if ($user['blacklist_ice_date'] != "0000-00-00 00:00:00") {
                $icedate  = new DateTime(date($user['blacklist_ice_date']));
                $interval = (array) date_diff($icedate, $today);

                // Ist er länger als 3 Monate auf Eis -> setze ihn automatisch wieder aktiv
                if ($interval['days'] > 91) {
                    $db->write_query("UPDATE " . TABLE_PREFIX . "users SET blacklist_ice = 0 WHERE uid = {$uid}");
                }
                if ($interval['days'] > 365) {
                    //Ist das Datum der letzten auf Eissetzung ein Jahr her, darf wieder.
                    $db->write_query("UPDATE " . TABLE_PREFIX . "users SET blacklist_ice_date = '' WHERE uid = {$uid}");
                }
            }

            //Steht der Charakter noch vom letzten Monat auf der Blackliste? 
            $flag = $db->num_rows($db->simple_select("blacklist", "uid", "uid = {$uid}"));

            //Der Charakter ist Bewerber oder wartet auf aktivierung
            //TODO Bewerbergruppe dynamisch
            if ($user['usergroup'] == $opt_bewerber || $user['usergroup'] == $opt_bewerber) {
                $regdate = gmdate("Y-m-d H:i:s", $user['regdate']);
                //Hole Threads aus der Bewerber area 
                $get_stecki = $db->write_query("
                        SELECT *, datediff(FROM_UNIXTIME(dateline), '{$regdate}' ) as diff FROM 
                        " . TABLE_PREFIX . "threads WHERE uid = {$uid} and fid = {$opt_steckiarea}
                        ");
                //in Array umwandeln mit dem wir arbeiten können
                $stecki = $db->fetch_array($get_stecki);

                //es gibt einen beitrag
                if ($db->num_rows($get_stecki) > 0) {
                    //ist der beitrag solved oder nicht
                    if ($stecki['threadsolved'] == 1) {
                        //solved -> nicht blacklist -> Eintrag löschen
                        $db->delete_query("blacklist", "uid = {$uid}");

                        // Der Thread ist nicht als erledigt markiert und älter als erlaubte dauer
                    } elseif ($stecki['threadsolved'] == 0 && $stecki['diff'] > $opt_bewerber_days) {
                        //notsolved ->  // && älter als frist -> auf blacklist
                        savebl($flag, $uid, $username, $stecki['tid'], $stecki['date']);
                        //thread ist nicht erledigt, aber dauer ist noch nicht überschritten
                    } elseif ($stecki['threadsolved'] == 0 && $stecki['diff'] <= $opt_bewerber_days) {
                        //nothing has to happen
                    }
                    //es gab keinen beitrag
                } else {
                    //reg date holen und diff zu heute
                    $regidate = new DateTime();
                    $regidate->setTimestamp($user['regdate']); //wir benutzen set timestamp weil unix timestamp
                    $interval = $regidate->diff($today); // <--- hier rechnen wir das intervall aus

                    //vergleich mit erlaubter dauer
                    if ($interval->days > $opt_bewerber_days) {
                        // dauer überschritten -> auf blacklist
                        savebl($flag, $uid, $username, "", "");
                    } else {
                        //Dauer ist okay, für den unwahrscheinlichen fall, dass der user auf der bl steht -> löschen
                        $db->delete_query("blacklist", "uid = {$uid}");
                    }
                }
                //kein Bewerber sondern angenommener user
            } else {
                //Wir holen uns den neusten Post aus dem Ingame und Archiv
                $get_posts = $db->query("SELECT *,FROM_UNIXTIME(dateline) as date, DATEDIFF(CURDATE(),FROM_UNIXTIME(dateline)) as diff FROM 
            (SElECT uid, username, fid, tid, pid, dateline as dateline FROM  " . TABLE_PREFIX . "posts WHERE uid = {$uid} AND visible != '-2') as up 
              INNER JOIN
            (SELECT fid FROM " . TABLE_PREFIX . "forums WHERE concat(',',parentlist,',') LIKE '%," . $opt_bl_ingame . ",%' " . $archiv . ") as fids
            ON fids.fid = up.fid
              ORDER by dateline DESC
            LIMIT 1");
                // umwandeln in Array mit dem wir arbeiten können
                $post = $db->fetch_array($get_posts);

                //Gibt es einen Post?
                if ($db->num_rows($get_posts)) {
                    // wenn es einen gibt, überprüfe ob er länger her ist als erlaubt ($post['diff'] enthält die Tage, von letzten post zu heute) 
                    if ($post['diff'] > $opt_bl_days) {
                        //der charakter ist nicht away und nicht auf eis
                        if ($user['away'] != 1 && $user['blacklist_ice'] != 1) {
                            //muss auf die blackliste
                            savebl($flag, $uid, $username, $post['tid'], $post['date']);
                        }
                        if (($user['away'] == 1 && $post['diff'] > 91)) {
                            //user ist away, aber die 3 Monatsregel greift -> blacklist
                            savebl($flag, $uid, $username, $post['tid'], $post['date']);
                        }
                        if ($user['blacklist_ice'] == 1) {
                            //User ist auf Eis muss nicht auf blacklist
                            //löschen falls er letzten Monat drauf stand.
                            $db->delete_query("blacklist", "uid = {$uid}");
                        }
                    } else {
                        //Der User hat in nötigen Zeitraum gepostet, sollte er letztes Mal draufgestanden haben, wird er gelöscht.
                        $db->delete_query("blacklist", "uid = {$uid}");
                    }
                    //gar kein Post im Ingame gefunden 
                } else {
                    //Der user  ist neu
                    //wir wollen das datum vom steckbrief in den area für angenommene
                    $steckidate = $db->fetch_array($db->write_query("SELECT * FROM 
                    (SElECT uid, username, fid, tid, dateline FROM  " . TABLE_PREFIX . "threads WHERE uid = {$uid} AND visible != '-2') as up 
                      INNER JOIN
                    (SELECT fid FROM " . TABLE_PREFIX . "forums WHERE concat(',',parentlist,',') LIKE '%,{$opt_steckiarea},%') as fids
                    ON fids.fid = up.fid
                    LIMIT 1"));
                    $stecki = new DateTime();
                    //Datum speichern
                    $stecki->setTimestamp($steckidate['dateline']);
                    $interval = $stecki->diff($today); //Wie viele tage zu heute? 
                    // Wenn größer als erlaubter Zeitraum -> auf Blacklist
                    if ($interval->days > $opt_bl_days) {
                        savebl($flag, $uid, $username, "", "");
                    } else {
                        //sonst muss der Chara nicht auf die Blacklist. 
                        $db->delete_query("blacklist", "uid = {$uid}");
                    }
                }
            }
        }
    }
    add_task_log($task, "Blacklist Task erfolgreich ausgeführt.");
}

/**
 * HELPER SAVE BLACKLIST
 */

function savebl($flag, $uid, $username, $tid, $date)
{
    global $db;
    if ($flag == 1) {
        $update = array(
            'uid' => $uid,
            'username' => $username,
            'tid' => $tid,
            'date' => $date
        );
        $db->update_query('blacklist', $update, "uid='" . $uid . "'");
    } else {
        $update = array(
            'uid' => $uid,
            'username' => $username,
            'tid' => $tid,
            'date' => $date
        );
        $db->insert_query('blacklist', $update);
    }
}
