# Kurzgesuche 1.0
Es handelt sich um ein Kurzgesuch-Plugin, für Gesuche, welche zwar Anschluss bieten, aber kein vollständiges Charakterkonzept besitzen und eher freier sind.
Ausgewählte Gruppen können Kurzgesuche hinzufügen. Gäste und User können sich diese Gesuche reservieren und auf Wunsch werden die Gesuche auch im Profil angezeigt. Reservierungen können von Usern und Team auch wieder gelöst werden, falls sich der Gast oder der User nicht mehr meldet. Auch können User ihre Kurzgesuche bearbeiten, löschen und als erledigt markieren. Durch den Accountswitcher kann man all Kurzgesuche aller Accounts auf der Seite mit den eigenen Kurzgesuchen ansehen und bearbeiten.
Die Kategorien, Beziehungsstatus und Geschlechts Auswahlmöglichkeiten der Gesuche können manuell in ACP eingestellt werden. Die Gesuche können gefiltert werden auf der Hauptseite.
Sollten Accounts gelöscht werden, welche ein Kurzgesuch erstellt haben, werden diese automatisch gelöscht. Vorraussetzung ist dabei, dass die Accounts einzeln gelöscht werden.
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
- member_profile - {$shortsearch_profile

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
  Hauptseite der Kurzgesuche
  <img src="https://www.bilder-hochladen.net/files/big/m4bn-76-fc96.png" />
  
  Maske beim Hinzufügen
  <img src="https://www.bilder-hochladen.net/files/big/m4bn-77-d5de.png" />
  
  Übersicht aller eigenen Kurzgesuche
  <img src="https://www.bilder-hochladen.net/files/big/m4bn-7a-76ad.png" />
  
  Kurzgesuche im Profil
  <img src="https://www.bilder-hochladen.net/files/m4bn-7b-7b00.png" />
  
  Mod-CP
  <img src="https://www.bilder-hochladen.net/files/m4bn-79-d8f5.png" />
  
  Alert auf dem Index
  <img src="https://www.bilder-hochladen.net/files/m4bn-78-3c3a.png" />
  
  Popfenster für die Gästereservierunge
  <img src="https://www.bilder-hochladen.net/files/m4bn-7c-0965.png" />

# Danksagung
Ein Dank geht an Katja (risuena) und Alex (Ales), welche mir bei Problem immer wieder geholfen haben. 
Und ein Dank geht an Doro (Sternentänzerin) und Celine (SG-Name), welche das Plugin getestet haben auf ihre letzten Fehlerchen.
