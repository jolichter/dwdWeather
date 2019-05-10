<!-- Demo Resource Call -->

	<div>
	<h2>Das Wetter für [[+location]] im 6-Stunden-Takt</h2>
	Stand [[+pubDateDay]] [[+pubDate]]<br />
	<small>Heute: Tageslicht [[+sunrise]] bis [[+sunset]] (Tageslänge [[+dayduration]] h)</small><br />
	<small>Luftdrucktendenz ist [[+pTendenz]] [[+pDelta]]</small><br />
	</div>

[[$dwdWetter?
   &STATION=`K428`
   &QTY=`18`
   &TPL=`dwdWetterTPL`
   &T1=`[[!getTimeRoundH]]`
   &T2=`[[!getTimeRoundH? &add=`6`]]`
   &T3=`[[!getTimeRoundH? &add=`12`]]`
   &T4=`[[!getTimeRoundH? &add=`18`]]`
]]
