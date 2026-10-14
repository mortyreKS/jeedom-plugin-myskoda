# MySkoda plugin changelog (beta)

>**IMPORTANT**
>
>Beta version, state of the tests on 14/10/2026. The plugin runs on a petrol vehicle (trips, fuel, position, service) and on three electric vehicles, three Elroq (battery, range, charging locations; on one of them, charging detection, charging and air conditioning commands, customised tile and trips). Not yet tried on a real vehicle: a hybrid, auxiliary heating, and the experimental features. Feedback is welcome.

The detailed history of the earlier beta versions is in the [French changelog](../fr_FR/changelog_beta.md).

# 0.851 — 14/10/2026

Revision of 0.85, after the first real test of the preferred charging times, on a tester's Elroq: the feature works, and the numbering of the charging times is the app's.

- The charging modes use the MyŠkoda app's words: “Preferred charging times” stays, “Immediate charging” replaces “Manual” (in French, the app's “Heures préférées” and “Recharge immédiate”). Only the text of the “Select charging mode” list changes: the values sent are the same, so are your scenarios.
- Documentation: the charging times are only used if the “Preferred charging times” charging mode is chosen, in the app or with the “Select charging mode” command.
- The feature stays experimental: only one vehicle has tried it.

# 0.85 — 14/10/2026

- New “Experimental features” section in the plugin configuration, with an “Enable experimental features” box, unticked by default. An experimental feature is written from Škoda's documentation but has never been tried on a vehicle: it only appears for those who tick the box. If it is unticked later, its commands stay in place but are refused. Without the box, nothing changes.
- **Experimental, not tested on a vehicle**: setting the preferred charging times of the charging locations (requested by a tester). Three commands per charging location, for scenarios: enable a charging time, disable it, change its hours (for example 22:00-06:00; a charging time can cross midnight). The plugin reads the charging location again just before sending it and only changes the targeted charging time; each change costs three requests. With the box ticked, the tile shows the charging times numbered, inactive ones greyed out.
- Tried on the Jeedom box, without a vehicle, against a fake Škoda server: charging location sent back unchanged except the targeted charging time, unknown fields included; two changes in a row that do not cancel each other; clear refusals (charging time that does not exist, badly written hours, unknown charging location); names of the charging locations absent from the log. Not yet tried on a car: what the vehicle does, and whether the numbering of the charging times follows the app.
- Documentation: new page “Experimental features: tests to run”, for those who want to try.

# 0.84 — 12/10/2026

- New “Display units” setting in the plugin configuration: “Metric (km)”, as before, or “Imperial (miles)”. In imperial, the tile, the logbook and its exports show distances in miles, speeds in mph, and consumption the way it is expressed in the United Kingdom: miles per kWh for an electric car, miles per gallon (UK gallon) for a combustion car. The setting applies to all vehicles and does not depend on the language.
- Only the display changes: command values stay in kilometres, so that a scenario does not change meaning with this setting. Temperatures stay in °C, and the radius of favourite places in metres.
- New “Currency” setting, next to the units: the symbol shown next to prices and costs (€, £, CHF or $). No amount is converted: prices are entered directly in your currency. Units and currency are two separate settings: you can drive in kilometres and count in Swiss francs.
- Tried on the example data of the demonstration vehicle; not yet with a user who drives in miles.
- Plugin configuration page redesigned: at the top, a banner shows until when the API key is valid and whether your vehicles are up to date, with shortcuts to the logbook and to the vehicles page; a “Your vehicles” table gives, for each one, its level, its last update and the requests left this hour. Entering the key is presented in three steps. No setting has changed or disappeared: “Re-reading after a command” and “Radius of favourite places” are now under “Advanced settings”. Nothing is requested from Škoda to display this page.
- New plugin icon.
- Fixes from a full code review. Quota: an error after a successful reading no longer makes the vehicle be re-read every minute; the wait requested by Škoda when the quota is used up is no longer cancelled by saving the configuration, updating the plugin or the “Refresh” command; saving the configuration without changing anything no longer uses a request. Commands: a command with a value called without a value or out of range is rejected instead of sending 0 to the car. Diagnostic report: it no longer writes the name you gave the vehicle, only its model. Places tab: the name and address of a place can no longer be interpreted as code in the map tooltip. Logbook: fewer history readings per trip, period capped at 400 days, maps released when switching tabs.

# 0.83 — 12/10/2026

- The plugin is available in English. In a Jeedom set to English (Settings → System → Configuration → General → Language), the tile, the logbook, the vehicles page and the plugin configuration are displayed in English, with the words of the MyŠkoda app in English where they exist (“Inspection”, “Oil change”, “Boot”, “Bonnet”, “Smart home”…). Dates follow the language, numbers use a decimal point, and the Excel export then separates its columns with commas. In a Jeedom set to French, nothing changes.
- Documentation: it is now available in English, and the “Documentation” and “Changelog” buttons open it in the Jeedom language. Its “Commands” section now gives the full list of commands, one by one, with their exact name, their type and their value (possible charging and air conditioning states, limits of the settings), grouped by topic. Useful for writing a scenario.
- This version brings no new feature: it adds English and completes the documentation.
- The “Card” layout of the tile is now called the “customised tile”: in French the word was confused with the “Map” tab of the logbook (thanks to the tester who reported it). On the vehicle page, the setting becomes “Tile customisation”, with the choices “Standard (default)” and “Customised”, and “Customised tile background colour”. Your choice is kept, only the words change.
- The customised tile and the logbook trips are now confirmed on an electric car (an Elroq).
- What stays in French for now: the diagnostic report, the plugin log lines and the screenshots of the documentation.
- Good to know: a command name is given once and for all, in the Jeedom language at the time the vehicle is added; changing the language afterwards does not rename existing commands.

# 0.3 to 0.82 — 06/10/2026 to 11/10/2026

Summary of the private beta, in French only at the time:

- Tile with the vehicle picture, battery or fuel, range, charging, air conditioning, locking and position; optional customised tile for designs.
- Charging and air conditioning commands, auxiliary heating with S-PIN code, charge limit and charging mode.
- Logbook on desktop and mobile: day, map, places and favourites, trips, charging sessions or refuelling, statistics, Excel export.
- “Managed charging” option for charging driven by a scenario (solar surplus, home battery, off-peak hours).
- Charging locations defined in the MyŠkoda app, read-only, with four info commands.
- Service follow-up entered by hand, diagnostic report without personal data.
- Installation from GitHub and updates through the Jeedom update centre.
