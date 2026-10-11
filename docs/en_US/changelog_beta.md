# MySkoda plugin changelog (beta)

>**IMPORTANT**
>
>Beta version. The plugin runs on a petrol vehicle (trips, fuel, position, service) and on two electric vehicles, two Elroq (battery, range, charging locations; on one of them, charging detection and charging and air conditioning commands). Not yet tried on a real vehicle: a hybrid, auxiliary heating. Feedback is welcome.

The detailed history of the earlier beta versions is in the [French changelog](../fr_FR/changelog_beta.md).

# 0.84 — in preparation

- New “Display units” setting in the plugin configuration: “Metric (km)”, as before, or “Imperial (miles)”. In imperial, the tile, the logbook and its exports show distances in miles, speeds in mph, and consumption the way it is expressed in the United Kingdom: miles per kWh for an electric car, miles per gallon (UK gallon) for a combustion car. The setting applies to all vehicles and does not depend on the language.
- Only the display changes: command values stay in kilometres, so that a scenario does not change meaning with this setting. Temperatures stay in °C, and the radius of favourite places in metres.
- Tried on the example data of the demonstration vehicle; not yet with a user who drives in miles.

# 0.83 — in preparation

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
