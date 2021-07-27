# blacklist
automatic blacklist (WIP!)
Achtung, das Plugin ist noch nicht durchgetestet und zu dem nicht vollkommen dynamisch. Einige Regeln sind im Code verankert und nicht übers acp steuerbar. 

Noch nicht unbedingt für den Anfänger gebrauch geeignet ^^
Bewerbergruppe ist noch nicht dynamisch, steht zur Zeit auf usergruppe '5' 

-> Folgendes beachten:
tasks/blacklist.php
Hier wird die Blacklist und die Eisliste zurückgesetzt
Hart rein gecodete Regeln, betreffend der Eisliste. Ein User darf seinen Charakter nur einmal im Jahr auf Eis legen und nur 3 Monate lang. Der Task regelt dies ab Zeile 57. Wenn es bei euch andewrs ist, müsst ihr das hier ändern

# Allgemein: 
Das Plugin funktioniert nach folgendem Prinzip.
Einmal im Monat 00:01 des 01. des Monats wird der Task ausgeführt und stellt die Blacklist in einer DB Tabelle zusammen. 
User haben per Default erst einmal noch keinen Zugriff. Moderatoren können diese Blacklist schon einsehen.
misc.php?action=show_blacklist 

Sie können noch User manuell hinzufügen, oder User hinunternehmen. Es gibt außerdem eine Übersicht der abwesenden User (Über die Awayfunktion von Mybb), sowie die Charaktere die auf Eis gelegt sind (Pluginfunktion)

Erst wenn ein Moderator die Blacklist aktiviert, können nun auch User die Blacklist ansehen. (auch -> misc.php?action=show_blacklist). 
User können sich selbst streichen, dann wird der stroke cnt hochgezählt (nur 2x mal hinteinander ohne post möglich)
Postet der User oder erstellt eine neue Szene im Ingame, wird er automatisch von der Blacklist gelöscht. 

Nach den 7 tagen (oder wie lang auch immer) muss die Blacklist wieder auf inaktiv gesetzt werden. 
