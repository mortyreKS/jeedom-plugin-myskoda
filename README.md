# MySkoda — plugin Jeedom

Suivi et pilotage des véhicules Škoda dans Jeedom par l'[API publique officielle MyŠkoda](https://public.api.connect.skoda-auto.cz/docs).

**Version beta.** Le plugin tourne sur un véhicule à essence (trajets, carburant, position, entretien) et sur un véhicule électrique (batterie, autonomie, détection de la recharge, commandes de charge et de climatisation). Pas encore essayés sur un véhicule réel : un hybride, le chauffage auxiliaire, et le journal de bord d'une électrique sur plusieurs jours.

## Prérequis

Jeedom 4.4 ou plus récent, sous Debian 12 ou Debian 13. Ni démon ni dépendance : le plugin n'utilise que ce que Jeedom contient déjà.

## Installation

Depuis Jeedom, par la source « Fichier » (zip) : Plugins, Gestion des plugins, ajout depuis une autre source, identifiant `myskoda`. La mise en service (clé à créer dans l'application MyŠkoda, ajout du véhicule) est décrite dans la documentation.

## Documentation

- [Documentation](docs/fr_FR/index.md)
- [Journal des versions de la beta](docs/fr_FR/changelog_beta.md)

Les pages du dossier `aide/` sont ces mêmes documents convertis en HTML. Elles servent aux boutons « Documentation » et « Changelog » de Jeedom, qui interdit l'accès web aux dossiers `docs/` des plugins ; elles seront remplacées par des pages en ligne quand le plugin aura un dépôt public.

## Composants tiers

- [Leaflet](https://leafletjs.com) 1.9.4, licence BSD à deux clauses (`3rdparty/leaflet/LICENSE`), pour la carte du journal de bord.
- Fond de carte © les contributeurs d'[OpenStreetMap](https://www.openstreetmap.org/copyright), chargé par le navigateur depuis les serveurs d'OpenStreetMap.

## Licence et origine

Licence : GNU GPL version 3 ou ultérieure, voir le fichier `LICENSE`.

Ce plugin succède, sans en reprendre le code, au plugin « Skoda » (`jeeda`) de Pappa, qui s'appuyait sur l'ancienne API non officielle arrêtée en octobre 2026. Sa structure part du [modèle officiel de plugin Jeedom](https://github.com/jeedom/plugin-template).

Škoda et MyŠkoda sont des marques de Škoda Auto ; ce plugin n'est ni édité ni soutenu par Škoda Auto.
