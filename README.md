# dwdWeather
## MODX Weather Forecast

#### Wettervorhersage für MODX Revolution vom deutschen Wetterdienst
#### Weather forecast for MODX Revolution by the German Weather Service
- [Link DWD.de](https://www.dwd.de/)
- DEUTSCHE DWD-Wettervorhersage via CURL für MODX - benötigt eine Ressource, 2 Snippets und 2 Chunks
- GERMAN DWD-Weather forecast via CURL for MODX - needs one Resource, 2 Snippets and 2 Chunks

---

#### Aufruf Beispiel / example call

- Wetter Bitburg per WOEID abrufen
- get the weather for Bitburg with WOEID

```
[[!dwdWeather? &STATION=`K428` &TPL=`dwdWetterTPL`]]
[[+dwdWeather]]
```

- oder mit 4 Uhrzeiten pro Tag (T1 -T4) bei 12 Vorhersagen (QTY), also Vorhersage 3 Tage
- or with 4 times per day (T1-T4) with 12 forecasts (QTY), so forecast 3 days
```
[[!dwdWeather?
   &STATION=`K428`
   &TPL=`dwdWetterTPL`
   &QTY=`12`
   &T1=`06:00`
   &T2=`12:00`
   &T3=`18:00`
   &T4=`00:00`
]]
[[+dwdWeather]]
```

- Der MOSMIX-Vorhersagedatensatz "MOSMIX_L" enthält ca. 150 Wettervariablen pro Vorhersage und die maximale Vorhersagezeit beträgt +240 Stunden. Die Vorhersage wird 4 mal täglich um 03, 09, 15 und 21 Uhr UTC aktualisiert. Das Snippet holt 19 dieser Wettervariablen stündlich per cURL (nur wenn Seite geladen wird) und setzt diese in Platzhalter, bzw. in ein Chunk Template für Wetterelemente (10 Tage Trend).

- The MOSMIX forecast data set "MOSMIX_L" contains about 150 weather variables per prediction and the maximum forecast time is +240 hours. The forecast is updated 4 times daily at 03 am, 09 am, 3 pm and 9 pm o'clock UTC. The snippet fetches 19 of these weather variables per hour via cURL (only if page loaded) and places them in placeholders, or in a chunk template for weather elements (10 days trend).

##### German INFOS und LINKS (some documents you can switch to english)
- [README opendata.dwd.de](https://opendata.dwd.de/README.txt)
- [MOSMIX-Elemente - DWD](https://www.dwd.de/DE/leistungen/opendata/help/schluessel_datenformate/kml/mosmix_elemente_pdf)
- [Content of opendata.dwd.de/weather](https://www.dwd.de/DE/leistungen/opendata/help/inhalt_allgemein/opendata_content_de_en_pdf)
- [Beschreibungen der einzelnen Parameter des Elementes Wetter (ww...)](https://www.dwd.de/DE/leistungen/opendata/help/schluessel_datenformate/kml/mosmix_element_weather_xls.html)
- [MOSMIX-Vorhersagedaten FAQ](https://rcccm.dwd.de/DE/leistungen/met_verfahren_mosmix/faq/faq_mosmix_node.html)
- [DWD Stationskatalog (Vorhersagepunkte)](https://www.dwd.de/DE/leistungen/met_verfahren_mosmix/mosmix_stationskatalog.cfg?view=nasPublication&nn=16102)
- [ww-Code - Hashs mit deutschen Konditionen (Code/Description)](https://wetterkanal.kachelmannwetter.com/was-ist-der-ww-code-in-der-meteorologie/)

##### Demo Weather Print Screen
![MODX-DWD-Wetter](wetterDWD.jpg)
##### Demo Weather Print Screen with [fullCalendar](https://fullcalendar.io/)
![MODX-DWD-Kalender-Wetter](wetterKalenderDWD.jpg)
- [Demo Page: jolichter.de/wetter](https://jolichter.de/wetter/)
