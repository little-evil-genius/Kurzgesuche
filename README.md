# Kurzgesuche 1.0
Es handelt sich um ein Kurzgesuch-Plugin, für Gesuche, welche zwar Anschluss bieten, aber kein vollständiges Charakterkonzept besitzen und eher freier sind.
Ausgewählte Gruppen innerhalb der Einstellungen können Kurzgesuche hinzufügen. Gäste und User können sich diese Gesuche reservieren und auf Wunsch werden die Gesuche auch im Profil angezeigt. Bei Gästereservierungen können die Gäste eine Spitznamen (welcher dann angezeigt wird) und eine Nachricht, welcher zB Kontaktdaten oder ähnliches beinhaltet hinterlassen. Diese Nachricht bekommt der User dann per PN automatisch zugeschickt, vom Teamaccount (oder die ID, welche man im ACP angibt). 
Reservierungen können von Usern und Team auch wieder gelöst werden, falls sich der Gast oder der User nicht mehr meldet. Auch können User ihre Kurzgesuche bearbeiten, löschen und als erledigt markieren. Durch den Accountswitcher kann man alle Kurzgesuche aller Accounts auf der Seite mit den eigenen Kurzgesuchen ansehen und bearbeiten.
Die Kategorien, Beziehungsstatus und Geschlechts Auswahlmöglichkeiten der Gesuche können manuell in ACP eingestellt werden und werden dann automatisch angepasst, so das man keine Änderungen in den Templates oder php vornehmen muss. Die Gesuche können so auch nach Geschlecht und Beziehungststatus gefiltert werden auf der Hauptseite.
Sollten Accounts gelöscht werden, welche ein Kurzgesuch erstellt haben, werden diese automatisch gelöscht. Voraussetzung ist dabei, dass die Accounts einzeln gelöscht werden.
Das Team hat eine kleiner gesamt Übersicht im Mod-CP und kann dort auch Kurzgesuche löschen, als erledigt markieren oder Reservierungen lösen.

# Voraussetzungen
- Eingebundene Icons von Fontawesome
- Accountswitcher

# Datenbank-Änderungen
Hinzugefügte Tabellen:
- PRÄFIX_shortsearch

Veränderte Tabellen:
- PRÄFIX_users um die Spalte shortsearch_new

# Neue Templates
- shortsearch
- shortsearch_add
- shortsearch_alert
- shortsearch_bit
- shortsearch_category
- shortsearch_edit
- shortsearch_filter
- shortsearch_guest_reservation
- shortsearch_memprofile
- shortsearch_memprofile_bit
- shortsearch_menu
- shortsearch_modcp
- shortsearch_modcp_bit
- shortsearch_modcp_nav
- shortsearch_none
- shortsearch_own
- shortsearch_own_bit

# Template Änderungen - neue Variablen
- header - {$new_shortsearch}
- modcp_nav_users - {$nav_shortsearch}
- member_profile - {$shortsearch_profile}

# ACP-Einstellungen - Kurzgesuche
- Erlaubte Gruppen
- Kategorien
- Geschlechts-Möglichkeiten
- Beziehungsstatus-Möglichkeiten
- Profilfeld des Spielernamens
- Teamaccount
- Kurzgesuche im Profil

# Sonstiges
- Neues Stylesheet "shortsearch.css" in jedem Theme

# Links
- https://euerforum.de/misc.php?action=shortsearch
- https://euerforum.de/misc.php?action=shortsearch_add
- https://euerforum.de/misc.php?action=shortsearch_own
- https://euerforum.de/modcp.php?action=shortsearch

# Demo
  Hauptseite der Kurzgesuche<p>
  <img src="https://stormborn.at/plugins/sortsearch_uebersicht.png" />
  
  Maske beim Hinzufügen</p>
  <img src="https://stormborn.at/plugins/sortsearch_add.png" />
  
  Übersicht aller eigenen Kurzgesuche</p>
  <img src="https://stormborn.at/plugins/sortsearch_eigene.png" />
  
  Kurzgesuche im Profil</p>
  <img src="https://stormborn.at/plugins/sortsearch_profil.png" />
  
  Mod-CP</p>
  <img src="https://stormborn.at/plugins/sortsearch_modcp.png" />
  
  Alert auf dem Index</p>
  <img src="https://stormborn.at/plugins/shortsearch_alert.png" />
  
  Popfenster für die Gästereservierung</p>
  <img src="https://stormborn.at/plugins/shortsearch_gast.png" />

# Danksagung
Ein Dank geht an Katja (risuena) und Alex (Ales), welche mir bei Problem immer wieder geholfen haben. Auch ein Dank Sophie (aheartforspinach), welche über den Code nochmal geschaut hat und mir Tipps gegeben hat, wie man einiges besser löschen könnte.
Und ein Dank geht an Doro (Sternentänzerin), Katja und Celine (SG-Name), welche das Plugin getestet haben auf ihre letzten Fehlerchen.

# Getestet unter
- MyBB Version	1.8.26, 1.8.25, 1.8.23
- PHP Version	7.4.16, 7.4.1, 7.3.26
