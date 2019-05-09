# dwdWeather
## MODX Weather Forecast

#### Wettervorhersage für MODX Revolution vom deutschen Wetterdienst
#### Weather forecast for MODX Revolution by the German Weather Service
- URL DWD: https://www.dwd.de/
- DEUTSCHE DWD-Wettervorhersage via CURL für MODX, benötigt wird ein Snippet und 2 Chunks
- GERMAN DWD-Weather forecast via CURL for MODX, you need one Snippet and 2 Chunks

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
   &T3=`16:00`
   &T4=`20:00`
]]
[[+dwdWeather]]
```

Demo: https://jolichter.de/wetter/

![MODX-DWD-Wetter](wetterDWD.jpg)
