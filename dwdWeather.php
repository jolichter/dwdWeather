<?php
#
# DWD Wettervorhersage MODX Snippet | MODX Weather Forecast V 22.04.043
#
# Entgeltfreie Versorgung mit DWD-Geodaten über dem Serverdienst https://opendata.dwd.de
# https://opendata.dwd.de/README.txt
#
# MOSMIX-Dateien werden in dem xml-ähnlichen kml-Format ausgeliefert, die Dateien sind als kmz-Dateien komprimiert
# DWD Stationskatalog (oder besser Vorhersagepunkte!): https://www.dwd.de/DE/leistungen/met_verfahren_mosmix/mosmix_stationskatalog.cfg?view=nasPublication&nn=16102
# z.B.: ID 10609 = Trier, ID 10513 = Koeln/Bonn, ID K428 = Bitburg, usw.
#
# Beispiele für Snippet-Aufrufe
# ohne Uhrzeit:
# [[!dwdWeather? &STATION=`K428` &TPL=`dwdWetterTPL`]]
# [[+dwdWeather]]
# (als Standard wird 12:00 Uhr genommen)
#
# alle Vorhersagen stündlich:
# [[!dwdWeather? &STATION=`K428` &fcAll=`true` &TPL=`dwdWetterTPL`]]
# [[+dwdWeather]]
#
# mit 4 Uhrzeiten pro Tag (T1 -T4) bei 12 Vorhersagen (QTY), also Vorhersage 3 Tage:
# [[!dwdWeather?
#   &STATION=`K428`
#   &TPL=`dwdWetterTPL`
#   &QTY=`12`
#   &T1=`06:00`
#   &T2=`12:00`
#   &T3=`18:00`
#   &T4=`00:00`
# ]]
# [[+dwdWeather]]
#
# eine definierte Zeit pro Tag:
# [[!dwdWeather?
#   &STATION=`K428`
#   &TPL=`dwdWetterTPL`
#   &T1=`18:00`
# ]]
# [[+dwdWeather]]
#
# oder
#
# im Dokument ein Chunk "chunkWeather" aufrufen: [[$chunkWeather? &STATION=`K428` &TPL=`dwdWetterTPL` &QTY=`QTY` &T1=`06:00` &T2=`12:00` &T3=`18:00` &T4=`00:00`]] [[+dwdWeather]]
# dann in dem Chunk das Snippet aufrufen: [[!dwdWeather? &STATION=`[[+STATION]]` &TPL=`[[+TPL]]` &QTY=`16` &T1=`[[+T1]]` &T2=`[[+T2]]` &T3=`[[+T3]]` &T4=`[[+T4]]`]]
# und ein eigenes HTML-Gerüst mit den Platzhalter erstellen
#
# Platzhalter für Chunks:
#   -> Ort und Vorhersagedatum: [[+location]] [[+pubDate]] [[+pubDateDay]]
#   -> Sonnenaufgang: [[+sunrise]], Sonnenuntergang: [[+sunset]], Tageslänge: [[+dayduration]], Luftdrucktendenz: [[+pTendenz]] [[+pDelta]]
#
# Beispiel für ein Chunk Template (z.B. dwdWetterTPL) welches per Platzhalter [[+dwdWeather]] dann platziert wird:
#  <div>
#    <div>
#    <h4>[[+fc0]] [[+fc2]] [[+fc1]]</h4>
#	   <img src='[[+fc17]]' title='[[+fc16]]' alt='' />
#	   </div>
#	   [[+fc5:gte=`0.1`:then=`<span class="label blue">[[+fc5]]</span>`:else=`<span class="label red">[[+fc5]]</span>`]]
#	   <br />
#	   <small>Sonnenschein: [[+fc14]]</small><br />
#	    <small>Wolkendecke: [[+fc10]]</small><br />
#	     <small>Niederschlag: [[+fc13]]</small><br />
#	      <small>Wind (Richtung): [[+fc8]] ([[+fc7]])</small><br />
#	       <small>Max. Windböe: [[+fc9]]</small><br />
#	        <small>Luftdruck: [[+fc11]]</small><br />
#	         <small>Luftfeuchte: [[+fc18]]</small><br />
#	          <small>Sichtweite: [[+fc15]]</small><br />
#  </div>
#
#   Vorhersage Platzhalter z.B. für Kalender
#   -> 20 Vorhersagen (10 Tage): [[+fc_V_E]]  (V = Vorhersage Nr (0-19) | E = Elemente (0-18), z.B. [[+fc_0_5]]
#
#   19 Elemente: 0 Date | 1 Time | 2 Day | 3 minT | 4 maxT | 5 2mT | 6 dewPoint | 7 windDir | 8 windSpeed | 9 windGust | 10 cloud |
#   11 hPa | 12 rainKg24h | 13 rainKg6h | 14 sun | 15 vis | 16 sigW | 17 picName | 18 hu |
#
#
#
# Variablen -Start------------------->
   # Chunk Template(default ist ohne)
   $strTPL = $modx->getOption('TPL',$scriptProperties,'');
   # Anzahl der Vorhersagen (default ist 40, bei 4 pro Tag sind das dann 10 Tage)
   $intQTY = $modx->getOption('QTY',$scriptProperties, 40);
   $strTMP = MODX_ASSETS_PATH.'dwd_temp/';
   $strURL = 'https://opendata.dwd.de/weather/local_forecasts/mos/MOSMIX_L/single_stations/';
   $strStation = $modx->getOption('STATION',$scriptProperties,'10609'); # Trier (älteste Stadt Deutschlands)
   $strURL .= $strStation . '/kml/MOSMIX_L_LATEST_' . $strStation . '.kmz';
   # Icons Bilder Pfad
   $strURLIcon = $modx->config['base_url'].'assets/dwd_img/';     # Wetter-Icons
   # z.B. Forecast 10 Tage = 24*10
   $MAX_COUNT = 24*10;
   # Forecast
   $fcAll = $modx->getOption('fcAll',$scriptProperties,'false'); # default all-Forecast false
   $time1 = $modx->getOption('T1',$scriptProperties,'12:00'); # default 12:00
   $time2 = $modx->getOption('T2',$scriptProperties,'');
   $time3 = $modx->getOption('T3',$scriptProperties,'');
   $time4 = $modx->getOption('T4',$scriptProperties,'');
   # Luftdruck gilt als stabil, wenn hPa Delta nicht grösser als
   $intPStable = 4;
   # Wetter Icons mit oder ohne Sonne (SunD3) anzeigen, ab x %
   $intSun = 30;
   # Hitzewelle gilt ab wie viel Grad? Wetter Icon Sonne 0h.png (TX)
   $valMaxT = 35.0;        # ab x Grad Celsius (z.B. 35.0)
   # wenn Winter-Sommerzeit angepasst werden muss
   $bolTimeOffset = false;
   $timeOffset = '0';
# Variablen -Ende-------------------<

   if ($bolTimeOffset) {
      # Sommerzeit/Winterzeit
      # daylight timeOffset to UTC)
      # 1 bei Sommerzeit, ansonsten 0
         if(date('I') == 1) {
            $timeOffset = '7200';
         }
         else{
            $timeOffset = '3600';
         }
   }

   # Ordner anlegen, wenn fehlt
   if(!file_exists($strTMP)) {
      mkdir($strTMP, 0755, true);
   }


# relative Luftfeuchtigkeit berechnen
# calculate relative humidity (TTT(K), Td(K))
if (!function_exists('getHumidity')) {
function getHumidity($T, $TD) {
  if (is_numeric($T) && is_numeric($TD)) {
    $T = round($T - 273.15, 1);
    $TD = round($TD - 273.15, 1);
    $RH=round(100*(exp((17.625*$TD)/(243.04+$TD)) / exp((17.625*$T)/(243.04+$T))));
  } else {
    $RH = '---';
  }
  return $RH;
}
}

if (!function_exists('xml2array')) {
function xml2array ( $xmlObject, $out = array () ) {
  foreach ( (array) $xmlObject as $index => $node )
  $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
  return $out;
}
}

if (!function_exists('getParamArray')) {
function getParamArray($rootObj, $id) {
  foreach ($rootObj as $param) {
    if ((string) $param['elementName'] == $id) {
      $output = preg_replace('!\s+!', ';', (string) $param->value);
      $output = explode(';', $output);
      array_shift($output);
      return $output;
    }
  }
}
}

# function direction in N/E/S/W instead of Grad (Windrichtung)
if (!function_exists('getWindDirection')) {
   function getWindDirection($degree = 0) {
     $direction = array('N', 'NNO', 'NO', 'ONO', 'O', 'OSO', 'SO', 'SSO', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW');
     $step = 360 / (count($direction));
     $b = floor(($degree + ($step/2)) / $step);
   return $direction[$b % count($direction)];
   }
}


# ww-Code - Hashs mit deutschen Konditionen (Code/Description), Quellen:
# https://wetterkanal.kachelmannwetter.com/was-ist-der-ww-code-in-der-meteorologie/
# https://www.dwd.de/DE/leistungen/opendata/help/schluessel_datenformate/kml/mosmix_element_weather_xls.html
# es werden nicht alle benötigt, aber wer weiss... ;-)
$strConditions_de = array(
// Bewölkung
 '0'  => 'Effektive Wolkendecke weniger als 2/8',
 '1'  => 'Effektive Wolkendecke zwischen 2/8 und 5/8',
 '2'  => 'Effektive Wolkendecke zwischen 5/8 und 6/8',
 '3'  => 'Effektive Wolkendecke mindestens 6/8',
// Dunst, Rauch, Staub oder Sand
 '4'  => 'Sicht durch Rauch oder Asche vermindert',
 '5'  => 'trockener Dunst (relative Feuchte < 80 %)',
 '6'  => 'verbreiteter Schwebstaub, nicht vom Wind herangeführt',
 '7'  => 'Staub oder Sand bzw. Gischt, vom Wind herangeführt',
 '8'  => 'gut entwickelte Staub- oder Sandwirbel',
 '9'  => 'Staub- oder Sandsturm im Gesichtskreis, aber nicht an der Station',
// Trockenereignisse
 '10' => 'feuchter Dunst (relative Feuchte > 80 %)',
 '11' => 'Schwaden von Bodennebel',
 '12' => 'durchgehender Bodennebel',
 '13' => 'Wetterleuchten sichtbar, kein Donner gehört',
 '14' => 'Niederschlag im Gesichtskreis, nicht den Boden erreichend',
 '15' => 'Niederschlag in der Ferne (> 5 km), aber nicht an der Station',
 '16' => 'Niederschlag in der Nähe (< 5 km), aber nicht an der Station',
 '17' => 'Gewitter (Donner hörbar), aber kein Niederschlag an der Station',
 '18' => 'Markante Böen im Gesichtskreis, aber kein Niederschlag an der Station',
 '19' => 'Tromben (trichterförmige Wolkenschläuche) im Gesichtskreis',
// Ereignisse der letzten Stunde, aber nicht zur Beobachtungszeit
 '20' => 'nach Sprühregen oder Schneegriesel',
 '21' => 'nach Regen',
 '22' => 'nach Schneefall',
 '23' => 'nach Schneeregen oder Eiskörnern',
 '24' => 'nach gefrierendem Regen',
 '25' => 'nach Regenschauer',
 '26' => 'nach Schneeschauer',
 '27' => 'nach Graupel- oder Hagelschauer',
 '28' => 'nach Nebel',
 '29' => 'nach Gewitter',
// Staubsturm, Sandsturm, Schneefegen oder -treiben
 '30' => 'leichter oder mäßiger Sandsturm, an Intensität abnehmend',
 '31' => 'leichter oder mäßiger Sandsturm, unveränderte Intensität',
 '32' => 'leichter oder mäßiger Sandsturm, an Intensität zunehmend',
 '33' => 'schwerer Sandsturm, an Intensität abnehmen',
 '34' => 'schwerer Sandsturm, unveränderte Intensität',
 '35' => 'schwerer Sandsturm, an Intensität zunehmend',
 '36' => 'leichtes oder mäßiges Schneefegen, unter Augenhöhe',
 '37' => 'starkes Schneefegen, unter Augenhöhe',
 '38' => 'leichtes oder mäßiges Schneetreiben, über Augenhöhe',
 '39' => 'starkes Schneetreiben, über Augenhöhe',
// Nebel oder Eisnebel
 '40' => 'Nebel in einiger Entfernung',
 '41' => 'Nebel in Schwaden oder Bänken',
 '42' => 'Nebel, Himmel erkennbar, dünner werdend',
 '43' => 'Nebel, Himmel nicht erkennbar, dünner werdend',
 '44' => 'Nebel, Himmel erkennbar, unverändert',
 '45' => 'Nebel, Himmel nicht erkennbar, unverändert',
 '46' => 'Nebel, Himmel erkennbar, dichter werdend',
 '47' => 'Nebel, Himmel nicht erkennbar, dichter werdend',
 '48' => 'Nebel mit Reifansatz, Himmel erkennbar',
 '49' => 'Nebel mit Reifansatz, Himmel nicht erkennbar',
// Sprühregen
 '50' => 'unterbrochener leichter Sprühregen',
 '51' => 'durchgehend leichter Sprühregen',
 '52' => 'unterbrochener mäßiger Sprühregen',
 '53' => 'durchgehend mäßiger Sprühregen',
 '54' => 'unterbrochener starker Sprühregen',
 '55' => 'durchgehend starker Sprühregen',
 '56' => 'leichter gefrierender Sprühregen',
 '57' => 'mäßiger oder starker gefrierender Sprühregen',
 '58' => 'leichter Sprühregen mit Regen',
 '59' => 'mäßiger oder starker Sprühregen mit Regen',
// Regen
 '60' => 'unterbrochener leichter Regen oder einzelne Regentropfen',
 '61' => 'durchgehend leichter Regen',
 '62' => 'unterbrochener mäßiger Regen',
 '63' => 'durchgehend mäßiger Regen',
 '64' => 'unterbrochener starker Regen',
 '65' => 'durchgehend starker Regen',
 '66' => 'leichter gefrierender Regen',
 '67' => 'mäßiger oder starker gefrierender Regen',
 '68' => 'leichter Schneeregen',
 '69' => 'mäßiger oder starker Schneeregen',
// Schnee
 '70' => 'unterbrochener leichter Schneefall oder einzelne Schneeflocken',
 '71' => 'durchgehend leichter Schneefall',
 '72' => 'unterbrochener mäßiger Schneefall',
 '73' => 'durchgehend mäßiger Schneefall',
 '74' => 'unterbrochener starker Schneefall',
 '75' => 'durchgehend starker Schneefall',
 '76' => 'Eisnadeln (Polarschnee)',
 '77' => 'Schneegriesel',
 '78' => 'Schneekristalle',
 '79' => 'Eiskörner (gefrorene Regentropfen)',
// Schauer
 '80' => 'leichter Regenschauer',
 '81' => 'mäßiger oder starker Regenschauer',
 '82' => 'äußerst heftiger Regenschauer',
 '83' => 'leichter Schneeregenschauer',
 '84' => 'mäßiger oder starker Schneeregenschauer',
 '85' => 'leichter Schneeschauer',
 '86' => 'mäßiger oder starker Schneeschauer',
 '87' => 'leichter Graupelschauer',
 '88' => 'mäßiger oder starker Graupelschauer',
 '89' => 'leichter Hagelschauer',
 '90' => 'mäßiger oder starker Hagelschauer',
// Gewitter
 '91' => 'Gewitter in der letzten Stunde, zurzeit leichter Regen',
 '92' => 'Gewitter in der letzten Stunde, zurzeit mäßiger oder starker Regen',
 '93' => 'Gewitter in der letzten Stunde, zurzeit leichter Schneefall/Schneeregen/Graupel/Hagel',
 '94' => 'Gewitter in der letzten Stunde, zurzeit mäßiger oder starker Schneefall/Schneeregen/Graupel/Hagel',
 '95' => 'leichtes oder mäßiges Gewitter mit Regen oder Schnee',
 '96' => 'leichtes oder mäßiges Gewitter mit Graupel oder Hagel',
 '97' => 'starkes Gewitter mit Regen oder Schnee',
 '98' => 'starkes Gewitter mit Sandsturm',
 '99' => 'starkes Gewitter mit Graupel oder Hagel',
 '100' => 'not available'
);



# Wetter Icons
if (!function_exists('wwPic')) {
   function wwPic($Code, $bolSun, $intTagBeginn, $intTagEnde, $WT, $bolMaxT) {
     # $intHour = date('H');
     $intHour = $WT;
     $bolDay  = ($intHour > $intTagBeginn && $intHour < $intTagEnde);

      switch ($Code) {
	case 0:
		# wenn Tag und heiss: 0h | wenn Tag: 0d | wenn Nacht: 0n
            if ($bolDay == true and $bolMaxT == true) {
                $icon = '0h';
            } elseif ($bolDay == true) {
                $icon = '0d';
            } else {
                $icon = '0n';
            }
		break;
	case 1:
		# wenn sonniger Tag: 1s | wenn Tag: 1d | wenn Nacht: 1n
            if ($bolDay == true and $bolSun == true) {
                $icon = '1s';
            } elseif ($bolDay == true) {
                $icon = '1d';
            } else {
                $icon = '1n';
            }
		break;
	case 2:
		# wenn sonniger Tag: 2s | wenn Tag: 2d | wenn Nacht: 2n
            if ($bolDay == true and $bolSun == true) {
                $icon = '2s';
            } elseif ($bolDay == true) {
                $icon = '2d';
            } else {
                $icon = '2n';
            }
		break;
	case 3:
                # wenn Tag: 3d | wenn Nacht: 3n
		$icon = ($bolDay) ? '3d' : '3n';
		break;
	case 4:
	case 5:
	case 6:
	case 7:
	case 8:
	case 9:
		$icon = '4-9';
		break;
	case 10:
	case 11:
	case 12:
	case 13:
	case 14:
	case 15:
	case 16:
		$icon = '10-16';
		break;
	case 17:
		$icon = '17';
		break;
	case 18:
		$icon = '18';
		break;
	case 19:
		$icon = '19';
		break;
	case 20:
		$icon = '20';
		break;
	case 21:
		$icon = '21';
		break;
	case 22:
		$icon = '22';
		break;
	case 23:
	case 24:
		$icon = '23-24';
		break;
	case 25:
		$icon = '25';
		break;
	case 26:
		$icon = '26';
		break;
	case 27:
		$icon = '27';
		break;
	case 28:
		$icon = '28';
		break;
	case 29:
		$icon = '29';
		break;
	case 30:
	case 31:
	case 32:
		$icon = '30-32';
		break;
	case 33:
	case 34:
	case 35:
		$icon = '33-35';
		break;
	case 36:
	case 37:
	case 38:
	case 39:
		$icon = '36-39';
		break;
	case 40:
	case 41:
	case 42:
	case 43:
	case 44:
	case 45:
	case 46:
	case 47:
	case 48:
	case 49:
		$icon = '40-49';
		break;
	case 50:
	case 51:
	case 52:
	case 53:
		$icon = '50-53';
		break;
	case 54:
	case 55:
	case 56:
	case 57:
	case 58:
	case 59:
		$icon = '55-59';
		break;
	case 60:
	case 61:
	case 62:
	case 63:
	case 64:
	case 65:
		$icon = '60-65';
		break;
	case 66:
	case 67:
		$icon = '66-67';
		break;
	case 68:
	case 69:
		$icon = '68-69';
		break;
	case 70:
	case 71:
	case 72:
	case 73:
	case 74:
	case 75:
	case 76:
	case 77:
	case 78:
	case 79:
		$icon = '70-79';
		break;
	case 80:
		# wenn sonniger Tag: 80s | wenn Tag: 80d | wenn Nacht: 80n
            if ($bolDay == true and $bolSun == true) {
                $icon = '80s';
            } elseif ($bolDay == true) {
                $icon = '80d';
            } else {
                $icon = '80n';
            }
		break;
	case 81:
		$icon = '81';
		break;
	case 82:
		$icon = '82';
		break;
	case 83:
	case 84:
		$icon = '83-84';
		break;
	case 85:
	case 86:
    case 87:
    case 88:
		$icon = '85-88';
		break;
	case 89:
	case 90:
		$icon = '89-90';
		break;
	case 91:
	case 92:
	case 93:
	case 94:
	case 95:
	case 96:
	case 97:
	case 98:
	case 99:
		$icon = '91-99';
		break;
	// default
         default:
		$icon = 'unknown';
		break;
      }
   return $icon.'.png';
   }
}



   # max. stündlich eine neue Datei ([Station]_[ISO-Datum]_[h]_dwdWeather.kmz) erstellen und alle alte [Station].* löschen
   # Start------------------->
   $strDatumStunde = date('Y-m-d_G');
   $strZieldatei = $strTMP.$strStation.'_'.$strDatumStunde.'_dwdWeather.kmz';

   if(!file_exists($strZieldatei)) {
      array_map('unlink', glob($strTMP.$strStation.'*'));
      # Datei per CURL abholen -Start------------------->
      if (function_exists('curl_version')) {
         $ch = curl_init($strURL);
         $zieldatei = fopen($strZieldatei, 'w');
         # deaktiviere SSL Überprüfung
         # curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
         # curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         curl_setopt($ch, CURLOPT_FILE, $zieldatei);
         curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
         curl_exec($ch);
         $intReturnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         fclose($zieldatei);
         # prüfe ob die Seite erreichbar ist!
         if ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) {return 'ERROR: Page not available!';};
         }
      # Datei per CURL abholen -Ende--------------------<
   } # max. stündlich -Ende-------------------<


   # lösche Datei wenn die Dateigrösse 0 ist
   clearstatcache();
   if(0 == filesize($strZieldatei)) {
     array_map('unlink', glob($strTMP.$strStation.'*'));
     return 'ERROR: File is empty and due that deleted!';
   }


    # downloaded source data (*.kmz)
    $fn = $strZieldatei;
    $za = new ZipArchive();
    $za->open($fn);


    # Header-Infos
    $stat = $za->statIndex(0);
    $data = file_get_contents('zip://'.$strZieldatei.'#'.$stat['name']);

    # Ort, Ausgabezeit und Lokation (für Sonnenaufgang und Sonnenuntergang Berechnung)
    $xml2 = simplexml_load_string($data);
    $xmlDocument = $xml2->children('kml', true)->Document;

    $location = (string) $xmlDocument->Placemark->description;
    $coordinates = (string) $xmlDocument->Placemark->Point->coordinates;   # Bitburg "6.53,49.98,359.0"
    $coordinates = explode(',', $coordinates);

    $now = time();
    $zenith = 90+50/60;
    $sunset = date_sunset($now, SUNFUNCS_RET_TIMESTAMP, $coordinates[1], $coordinates[0], $zenith);
    $sunrise = date_sunrise($now, SUNFUNCS_RET_TIMESTAMP, $coordinates[1], $coordinates[0], $zenith);
    $mycoordinates = $coordinates[1] .', '. $coordinates[0];
    $dayduration = $sunset - $sunrise;
      $sunrise = date('H:i',$sunrise);
      $sunset = date('H:i',$sunset);
        $dayduration = round($dayduration/60/60, 2);
        $dayduration = str_replace(',', '.', $dayduration);

    # Platzhalter Sonnenaufgang, Sonnenuntergang, Tageslänge und Koordinaten
    $modx->setPlaceholder('sunrise', $sunrise);
    $modx->setPlaceholder('sunset', $sunset);
    $modx->setPlaceholder('dayduration', $dayduration);
    $modx->setPlaceholder('coordinates', $mycoordinates);

    $pubDate = (string) $xmlDocument->ExtendedData->children('dwd', true)->ProductDefinition->IssueTime;
      $pubDate = strtotime($pubDate) + $timeOffset;
        $pubDateDay = date('w', $pubDate);
        $pubDate = date('Y-m-d H:i', $pubDate);
        $wochentag = array('So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.');
        $pubDateDay = $wochentag[$pubDateDay];

    # Platzhalter Ort, Koordinaten und Veröffentlichungsdatum
    $modx->setPlaceholder('location', $location);
    $modx->setPlaceholder('pubDate', $pubDate);
    $modx->setPlaceholder('pubDateDay', $pubDateDay);


    # short name / long name (for header)
    # RR6c not available for all hours! 6am, 12am, 6pm and 12pm - if needed for all hours: change all RR6c to RR1c
    # RR6c = better precipitation forecasts
    $alias = array(
      'TN' => 'minT',        // Minimum temperature - within the last 12 hours (Kelvin) | nur 06:00 und 18:00 Uhr!
      'TX' => 'maxT',        // Maximum temperature - within the last 12 hours (Kelvin) | nur 06:00 und 18:00 Uhr!
      'TTT' => '2mT',        // Temperature 2m above surface (Kelvin)
      'Td' => 'dewPoint',    // Dewpoint 2m above surface (Kelvin)
      'DD' => 'windDir',     // 0°..360°, Wind direction
      'FF' => 'windSpeed',   // Wind speed (m/s) | m/s * 3.6 = km/h
      'FX3' => 'windGust',   // Wind speed (m/s) | m/s * 3.6 = km/h
      'Neff' => 'cloud',     // Effective cloud cover (%)
      'PPPP' => 'hPA',       // Surface pressure, reduced | hPA (mBAR)= Pa/1000
      'RRdc' => 'rainKG24h', // Total precipitation last 24 hour consistent with significant weather | Niederschlag 1 Ltr pro kg/m2 = 1 mm
      'RR6c' => 'rainKg6h',  // Total precipitation last 6 hour consistent with significant weather | Niederschlag 1 Ltr pro kg/m2 = 1 mm
      'SunD3' => 'sun',      // Sunshine duration during the last three hours (s)
      'VV' => 'vis',         // Visibility (m) | wird in km umgerechnet
      'ww' => 'sigW'         // Significant Weather (ID)
    );
    $ids = array_keys($alias);




for($i=0; $i<$za->numFiles; $i++) {
    $stat = $za->statIndex($i);
    $data = file_get_contents('zip://'.$strZieldatei.'#'.$stat['name']);

    $data = str_replace(
      array("kml:", "dwd:"),
      array("", ""),
      $data
    );

    $xml = simplexml_load_string($data);
    $timeSteps = xml2array($xml->Document->ExtendedData->ProductDefinition->ForecastTimeSteps->TimeStep);
    $lines = array_fill(0, count($timeSteps), array());


	# Datum (ISO) | Zeit | Wochentag
    foreach ($timeSteps as $key => $value) {
        $date = new DateTime($value);
        array_push($lines[$key], $date->format('Y-m-d'));
        array_push($lines[$key], $date->format('H:i'));
		array_push($lines[$key], $wochentag[$date->format('w')]);
    } // $timeSteps


   $fnode = $xml->Document->Placemark->ExtendedData->Forecast;
    foreach ($ids as $id) {
        $param = getParamArray($fnode, $id);

        if(is_array($param)){
           if (count($param) === 0) {
             $param = array_fill(0, count($timeSteps), '---');
           }
        } else {
             # PHP7.2 prevents warning: "Parameter must be an array or an object that implements Countable"
             $param = array_fill(0, count($timeSteps), '---');
        }

        foreach ($param as $key => $value) {
            # prevents PHP warning "a non-numeric value encountered"
            if($value !== null && !is_numeric($value)) {
                $value = 0;
            }
            $v = $value;

            if (in_array($id, array('TN', 'TX', 'TTT', 'Td'))) {
                $v = round(floatval($value) - 273.15, 1);
                $v = str_replace(',', '.', $v);
            }
            if ($id == 'PPPP') {
                $v = round($value / 100, 0);
            }
            if (in_array($id, array('Neff', 'Nh', 'Nm', 'Nl', 'ww'))) {
                $v = round($value);
            }
            if (in_array($id, array('RRhc', 'RRdc', 'RR6c'))) {
                $v = round($value, 1);
                $v = str_replace(',', '.', $v);
            }
            if ($id == 'VV') {
                $v = round(floatval($value) / 1000, 2);
                $v = str_replace(',', '.', $v);
            }
            if ($id == 'ww') {
                $v = $strConditions_de[$v];
            }
            if ($id == 'DD') {
                $v = getWindDirection(round($value));
            }
            if (in_array($id, array('FF', 'FX3'))) {
                $v = round(floatval($value) * 3.6);
            }

            # Returns an array with units
            if (in_array($id, array('TN', 'TX', 'TTT', 'Td'))) {
                $v = $v.' °C';
            }
            if (in_array($id, array('FF', 'FX3'))) {
                $v = $v.' km/h';
            }
            if ($id == 'Neff') {
                $v = $v.' % (effektiv)';
            }
            if ($id == 'SunD3') {
                # Zeit umrechnen in %/3h
                $v = floatval($v) / 3600;
                $v = round($v * 100 / 3);
                $v = $v.' %';
            }
            if ($id == 'DRR1') { # 1h
                $v = $v / 60;
                $v = $v.' min/h';
            }
            if ($id == 'RRdc') { # 24h
                $v = $v.' Ltr/24h';
            }
            if ($id == 'RRhc') { # 12h
                $v = $v.' Ltr/12h';
            }
            if ($id == 'RR6c') { # 6h
                $v = $v.' Ltr/6h';
            }
            if ($id == 'PPPP') {
                $v = $v.' hPA';
            }
            if ($id == 'VV') {
                $v = $v.' km';
            }
            if ($id == '-') {
                $v = '---';
            }
            array_push($lines[$key], $v);
        }
    }// foreach $ids



    // get Picture No (Significant Weather - ww)
    // get Sun (SunD3)
    // get Temp (TTT)
    // get Weather Time ($WT) | Uhrzeit
    $ww = getParamArray($fnode, 'ww');
    $rs = getParamArray($fnode, 'SunD3');
    $ttt = getParamArray($fnode, 'TTT');
    foreach ($ww as $key => $value) {
       # $intW = round(floatval($ww[$key]));
       $valTTT = round(floatval($ttt[$key]) - 273.15, 1);
       $intS = round(floatval($rs[$key]));

          # Zeit umrechnen in %/3h
          $intS = floatval($intS) / 3600;
          $intS = round($intS * 100 / 3);

          if($intS >= $intSun) {
             $bolSun = true;
          }
          else{
             $bolSun = false;
          }
          if($valTTT >= $valMaxT) {
             $bolMaxT = true;
          }
          else{
             $bolMaxT = false;
          }

      # für Wetter Icons (Tag oder Nacht)
      $tr = strtotime($sunrise);
      $tr = intval(date('G',$tr));
      $intTagBeginn = $tr - 1;

      $ts = strtotime($sunset);
      $ts = intval(date('G',$ts));
      $intTagEnde = $ts + 1;

      $WT = strtotime($lines[$key][1]); # Uhrzeit
      $WT = intval(date('G',$WT));

      array_push($lines[$key], $strURLIcon.wwPic(round(floatval($value)), $bolSun, $intTagBeginn, $intTagEnde, $WT, $bolMaxT));
    }

    // berechnen der Luftfeuchtigkeit
    // calculate humidity (hu %)
    $t = getParamArray($fnode, 'TTT');  # Temperature 2m above surface
    $d = getParamArray($fnode, 'Td');   # Dewpoint 2m above surface
    foreach ($t as $key => $value) {
        array_push($lines[$key], getHumidity($value, $d[$key]).' %');
    }


    $csvOutput = '';

    // output header
    # $csvOutput = str_replace(
    #  array_keys($alias),
    #  array_values($alias),
    #  'Date|Time|Tag|'.implode('|', $ids).'|pic'.'|hu'.'||'
    # );

    // slice & output content
    $lines = array_slice($lines, 0, $MAX_COUNT);
    foreach ($lines as $line) {
       if ($fcAll == 'false'){
           if ($line[1] == $time1 or $line[1] == $time2 or $line[1] == $time3 or $line[1] == $time4){
               $csvOutput = $csvOutput. implode('|', $line).'||';
           }
       } else {
           $csvOutput = $csvOutput. implode('|', $line).'||';
       }
    }

} // END numFiles


# echo $csvOutput;

# mehrdimensionales Array erstellen
$array = array_map(function($v){return str_getcsv($v, '|');}, explode('||', $csvOutput));
# print_r($array);

$intCA = count($array) -1;
unset($array[$intCA]); # RIP last array (it is empty)


    # Luftdrucktendenz - Zeitdifferenz in Stunden zwischen den 2 Messungen
    $tPa1 = strtotime($array[0][0].' '.$array[0][1]);
    $tPa2 = strtotime($array[1][0].' '.$array[1][1]);
    $tPaDelta = $tPa2 - $tPa1;
    $intStundenPaDelta = round($tPaDelta/60/60, 0);

    # Luftdrucktendenz (Stabil wenn hPa Delta kleiner als $intPStable)
    $hPa1 = intval($array[0][11]);
    $hPa2 = intval($array[1][11]);

    $hPaDelta = $hPa2 - $hPa1;
    $hPaDeltaABS = abs($hPaDelta);
    # bei positiven hPaDelta ein Pluszeichen für die Ausgabe setzen
      if ($hPaDelta > 0) {
          $hPaDelta = sprintf("%+d",$hPaDelta);
      }

    if ($hPaDeltaABS > $intPStable) {
       if ($hPa1 > $hPa2) {
           $strPTendenz = 'fallend';
       } else {
           $strPTendenz = 'steigend';
       }
       $modx->setPlaceholder('pDelta', '('.$hPaDelta.' hPa/'.$intStundenPaDelta.'h)');

    } else {
         $strPTendenz = 'stabil';
         $modx->setPlaceholder('pDelta', '');
    }
    $modx->setPlaceholder('pTendenz', $strPTendenz);


  $output = '';
  $arr = '';
  $arr = array();
  $arr2 = '';
  $arr2 = array();

  foreach ($array AS $key => $value) {
  if ($key >= $intQTY) break;

        foreach ($value AS $subKey => $subValue) {
            # echo $key.' | '.$subKey.' | '.$subValue.'<br>';

            # Platzhalter (z.B. für Kalender)
            $modx->setPlaceholder('fc_'.$key.'_'.$subKey, $subValue);

            # Array für getChunk (kann per Platzhalter [[+dwdWeather]] platziert werden)
            $arr = ['FC' => $key, 'fc'.$subKey => $subValue];
            $arr = $arr + $arr2;
            $arr2 = $arr;
        }

        if ($arr) {
            $output .= $modx->getChunk($strTPL, $arr);
        }
  }

$modx->setPlaceholder('dwdWeather', $output);
