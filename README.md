# MySkoda — plugin Jeedom

Suivi et pilotage des véhicules Škoda dans Jeedom par l'[API publique officielle MyŠkoda](https://public.api.connect.skoda-auto.cz/docs).

**Version beta.** Le plugin tourne sur un véhicule à essence (trajets, carburant, position, entretien) et sur trois véhicules électriques, trois Elroq (batterie, autonomie, points de recharge ; sur l'un des trois, détection de la recharge, commandes de charge et de climatisation, tuile personnalisée et trajets). Pas encore essayés sur un véhicule réel : un hybride, le chauffage auxiliaire. Les retours sont les bienvenus, dans les [tickets](https://github.com/mortyreKS/jeedom-plugin-myskoda/issues) du dépôt ou sur le [forum Jeedom](https://community.jeedom.com/t/beta-privee-myskoda-nouveau-plugin-skoda-sur-lapi-officielle-je-cherche-des-testeurs/151174).

## Langues

Français et anglais : l'écran et la documentation suivent la langue de Jeedom. English documentation: [docs/en_US/index.md](docs/en_US/index.md).

## Prérequis

Jeedom 4.4 ou plus récent (le plugin est testé sur Jeedom 4.6), sous Debian 12 ou Debian 13. Debian 11 est en fin de vie : la logique est de passer à Debian 12 au minimum ; pour autant, le plugin y fonctionne aujourd'hui (version 0.81 constatée sous Debian 11.11 avec PHP 7.4.33). Ni démon ni dépendance : le plugin n'utilise que ce que Jeedom contient déjà.

## Installation

Depuis Jeedom, par la source « Github » : Plugins, Gestion des plugins, ajout depuis une autre source, puis :

| Champ | Valeur |
|---|---|
| Type de source | Github |
| ID logique du plugin | `myskoda` |
| Utilisateur ou organisation du dépôt | `mortyreKS` |
| Nom du dépôt | `jeedom-plugin-myskoda` |
| Branche | `beta` |

Si « Github » n'apparaît pas dans les types de source, activez-le d'abord dans Réglages, Système, Configuration, onglet « Mises à jour/Market ». La mise en service (clé à créer dans l'application MyŠkoda, ajout du véhicule) est décrite dans la documentation.

## Documentation

En ligne, pour la version publiée : [mortyreks.github.io/jeedom-plugin-myskoda](https://mortyreks.github.io/jeedom-plugin-myskoda/) (français et anglais).

Dans le dépôt :

- [Documentation](docs/fr_FR/index.md)
- [Journal des versions de la beta](docs/fr_FR/changelog_beta.md)

Les pages du dossier `aide/` sont ces mêmes documents, en français et en anglais, convertis en HTML. Elles servent aux boutons « Documentation » et « Changelog » de Jeedom, qui interdit l'accès web aux dossiers `docs/` des plugins ; elles sont donc toujours celles de la version installée.

## Composants tiers

- [Leaflet](https://leafletjs.com) 1.9.4, licence BSD à deux clauses (`3rdparty/leaflet/LICENSE`), pour la carte du journal de bord.
- Fond de carte © les contributeurs d'[OpenStreetMap](https://www.openstreetmap.org/copyright), chargé par le navigateur depuis les serveurs d'OpenStreetMap.

## Licence et origine

Licence : GNU GPL version 3 ou ultérieure, voir le fichier `LICENSE`.

Ce plugin succède, sans en reprendre le code, au plugin « Skoda » (`jeeda`) de Pappa, qui s'appuyait sur l'ancienne API non officielle arrêtée en octobre 2026. Sa structure part du [modèle officiel de plugin Jeedom](https://github.com/jeedom/plugin-template).

Škoda et MyŠkoda sont des marques de Škoda Auto ; ce plugin n'est ni édité ni soutenu par Škoda Auto.
