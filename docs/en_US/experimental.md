# Experimental features: tests to run

> Page up to date for version 0.851 beta of the plugin, on 14/10/2026.

An experimental feature is written from Škoda's documentation, but nobody has tried it on a vehicle yet: the plugin's author has no electric vehicle. It has been checked without a car, against a fake Škoda server: what the plugin sends, and what it refuses. What remains to be seen is what the car does. Thanks to those willing to try it.

To enable it: plugin configuration (Plugins → Plugin management → MySkoda), “Experimental features” section, tick “Enable experimental features”, then Save. When unticked, the commands already created stay in place but are refused.

## Preferred charging times (version 0.85)

Three commands per charging location, described in the [documentation](index.md), “Commands” section: enable a charging time, disable it, change its hours. They match the “Preferred charging times” of the “Charging locations” screen of the MyŠkoda app.

### Getting ready

1. Update the plugin to version 0.85 or later, tick the box (see above).
2. Set the plugin log to Debug: plugin configuration, “Logs and monitoring” frame, tick “Debug”, Save.
3. On the vehicle page, click “Refresh” (or wait for the next reading, 10 minutes at most): the commands appear in the “Commands” tab. Their name starts with the charging location's name, for example “Home - enable a charging time”. They are hidden on the tile: they are meant for scenarios.
4. Take a screenshot of the “Charging locations” screen of the MyŠkoda app, for each location: this is the starting point, used to check that nothing else changed.
5. On the tile, unfold “See all locations”: with the box ticked, the four charging times of each location are numbered (no. 1 to no. 4), inactive ones greyed out, in the app's order (checked on an Elroq).
6. Check that the charging mode is “Preferred charging times”: otherwise the car ignores the charging times.

To run a command: create a scenario (Tools → Scenarios → Add), add an “Action” block, choose the command, fill in its values, save, then “Execute”. For “charging time hours”, the charging time number goes in the title field and the hours in the message field, written as 22:00-06:00.

Each change costs three requests from the hourly quota (reading the location, sending, reading again one minute later): space out the tests, keeping enough to read the vehicle. After each command, wait one or two minutes before looking at the app.

### Tests

1. **Enable then disable an existing charging time.** Pick an inactive charging time, enable it, check in the app; then disable it, check again.
2. **Change the hours of a charging time**, for example 21:00-07:00, then one that crosses midnight if it did not already (for example 23:30-05:30). Check in the app.
3. **Check that everything else is unchanged**, comparing with the starting screenshot: charge limit, minimum charge, departure times, the other charging times of the same location, and the other charging locations.
4. **A change during a charging session**, if the opportunity arises: does the car accept it, does charging carry on?
5. Optional: **two changes in a row** in one scenario (two action blocks), for example enable charging time 3 then change the hours of charging time 4. Both must show in the app.
6. **Change the charging mode from Jeedom** with the “Select charging mode” command: “Preferred charging times”, then “Immediate charging”. Does the app show the same choice? (That “Immediate charging” is Škoda's `MANUAL` mode has not been checked.)

### What to send back

- For each test: what worked or not, and the message shown when refused.
- The app screenshots, before and after.
- The **diagnostic report** (plugin configuration, “Diagnostics and support”) and the **`myskoda` log** (Analysis → Logs menu) covering the tests. The plugin removes from them the VIN, the number plate, the address, the coordinates and the names of the charging locations; the key and the S-PIN never appear.

After the tests: set the log back to its default level; the box can stay ticked.
