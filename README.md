# automatische blacklist
Achtung, das Plugin ist noch nicht komplett durchgetestet. Solltet ihr es installieren macht auf jedenfall vorher ein backup. 

# Was kann das Plugin?
 - automatische Liste von Charakteren auf der Blacklist
 - Berücksichtigung von Abwesenheit
 - Möglichkeit Charaktere auf Eis zu legen
 - Verwaltung der Blacklist __vor__ Veröffentlichung (manuelles hinzufügen und löschen) 
 - Automatische Mail an Betroffene, wenn die BL veröffentlicht wird
 - User können sich streichen (bis zu 2x, wird für die nächste BL gespeichert) 
 - User werden automatisch von der BL gelöscht, wenn sie im Ingame gepostet haben
 - Es kann ein Zeitraum festgelegt werden, in dem gepostet werden __muss__ und die Abwesenheit nicht mehr berücksichtigt wird
 - Auf Eis legen kann auf Zeitraum beschränkt werden
 - Auf Eis legen kann auf eine Anzahl von Charakteren pro User begrenzt werden
 - Benutzergruppen können von der BL ausgeschlossen werden (z.B. NPCs)
 
 # Installation
1. Dateien aus dem Inc Ordner hochladen /inc/plugins/blacklist.php & inc/tasks/blacklist.php
2. Einstellen wann die Blacklist ausgeführt werden soll (/admin/index.php?module=tools-tasks  -> blacklist task auswählen und datum einstellen wann er ausgeführt werden soll)
3. Einstellungen der Blacklist vornehmen
4. (zum Testen kann der Task auch manuell ausgeführt werden -> /admin/index.php?module=tools-tasks auf die uhr beim Blacklisttask klicken ;) ) 

# Funktionsweise / Ablauf / Handling
Nachdem alles eingestellt wurde (siehe Punkt Installation) 
Am Tag wenn die Blacklist ausgeführt wurde, kann ein Moderator sie verwalten.
misc.php?action=show_blacklist  
Es können Mitglieder hinzugefügt oder gelöscht werden.

Erst wenn der Moderator/Admin die Blacklist veröffentlicht passiert folgendes:
- Automatische Mail an die betroffenen Mitglieder wird verschickt
- User können die Blacklist einsehen
- User können sich selbst streichen (zur Zeit bis zu 2x) Beim 3. mal können sie sich selbst nicht noch einmal streichen.

Die Blacklist wird vom Moderator wieder versteckt, wenn sie nicht mehr gültig ist. (z.B. Nach einer Woche) 
User müssen schließlich manuell gelöscht werden. 

# Was macht der Task genau?
Bei der Ausführung trägt er in eine DB Tabelle (mybb_blacklist) die Mitglieder ein, die betroffen sind und löscht die Einträge von der letzten BL (es sei denn User haben sich gestrichen, dies wird gespeichert)

Auch die Eisliste wird berücksichtigt und Charaktere evt. wieder zurück auf nicht auf Eis gesetzt (je nach angebener Begrenzung im acp) 

# Voraussetzungen 
- Erweiterter Account Switcher von doylecc

# Bugs / To Do
- Autovervollständigung von Usernamen beim manuellen Hinzufügen
- Doppeltes Hinzufügen abfangen
- Anzahl des Streichens dynamisch machen

# neue Templates
- blacklist_show_main
- blacklist_show_userbit
- blacklist_show_userbitaway
- blacklist_show_userbitmod
- blacklist_show_viewmod
- blacklist_show_viewuser
- blacklist_ucp
- blacklist_ucp_bewerber_bit
- blacklist_ucp_bit_3month
- blacklist_ucp_edit
- blacklist_ucp_user_bit
- blacklist_index_alert

# genutzte Variablen
- im header.tpl: {$blacklist_index} 
- im usercp.tpl: {$blacklist_ucp}
- im usercp_profile.tpl: {$blacklist_ucp_edit}
