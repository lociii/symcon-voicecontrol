# Voice control
Steuerung von Symcon Geraeten ueber Sprachbefehle mit dem Amazon Alexa Voice Service, z.B. mit einem Amazon Echo

ACHTUNG:

Der entsprechende SmartHome Skill befindet sich aktuell in der Zertifizierung durch Amazon.
Das Modul kann nur zusammen mit diesem Skill genutzt werden und hat daher aktuell noch keine Verwendung.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Einrichten der Instanzen in IP-Symcon](#2-einrichten-der-instanzen-in-ip-symcon)
3. [PHP-Befehlsreferenz](#3-php-befehlsreferenz)

### 1. Funktionsumfang

* An- und ausschalten von Geraeten ueber Sprachbefehl
* Setzen, erhoehen und verkleinern von prozentualen Werten fuer Geraete

### 2. Einrichten der Instanzen in IP-Symcon

* Unter 'Instanz hinzufuegen das 'VoiceControl'-Modul auswaehlen und eine neue Instanz erzeugen.
* Links zu allen zu steuernden Eigenschaften unterhalb der Instanz erzeugen (z.B. wenn die Eigenschaft 'Status' einer Lampe gesteuert werden soll, muss zu dieser Eigenschaft ein Link erstellt werden)

Unterstuetzte Werte:
* Variablen der Typen Boolean, Integer und Float
* digitalStrom Geraete der Typen 'dS Light', 'dS Shutter' und 'dS Joker'

Das Modul bietet keine weitere Konfigurationsmoeglichkeit.

### 3. PHP-Befehlsreferenz

`LOCIVC_DebugDiscoveryResponse();`
Schreibt die Ausgabe einer fiktiven Geraeteerkennungsanfrage ins Debug-Log des Moduls.
Hierdurch kann nachvollzogen werden, ob alle verlinkten Geraete mit den richtigen Aktionen erkannt wurden.
