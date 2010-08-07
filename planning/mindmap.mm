<map version="0.9.0">
<!-- To view this file, download free mind mapping software FreeMind from http://freemind.sourceforge.net -->
<node CREATED="1243275503943" ID="Freemind_Link_377067031" MODIFIED="1279012338402" TEXT="Domain">
<node CREATED="1243275760099" FOLDED="true" ID="Freemind_Link_66902600" MODIFIED="1279012349513" POSITION="right" TEXT="questions">
<node CREATED="1243275765380" ID="Freemind_Link_827725689" MODIFIED="1243275782271" TEXT="how will the entity know its relationships?"/>
</node>
<node CREATED="1243275510662" ID="_" MODIFIED="1243275519334" POSITION="right" TEXT="Entity"/>
<node CREATED="1243275523005" FOLDED="true" ID="Freemind_Link_94958207" MODIFIED="1279012348753" POSITION="right" TEXT="Repository">
<node CREATED="1243275527490" ID="Freemind_Link_1743855736" MODIFIED="1243609242328" TEXT="loads entity with getBy&lt;Property&gt; calls"/>
<node CREATED="1243275592912" ID="Freemind_Link_281091827" MODIFIED="1243275605255" TEXT="stores entities in internal array and tracks changes"/>
<node CREATED="1243275605865" ID="Freemind_Link_1950408728" MODIFIED="1243275638568" TEXT="when repository life ends, inserts, updates or deletes stored entities"/>
</node>
<node CREATED="1249752779282" FOLDED="true" ID="Freemind_Link_605096501" MODIFIED="1279012347874" POSITION="right" TEXT="EntityManager">
<node CREATED="1249752791163" ID="Freemind_Link_717694763" MODIFIED="1249752800946" TEXT="Domain Description Language">
<node CREATED="1249752851474" ID="Freemind_Link_700651479" MODIFIED="1249752859429" TEXT="what should/does it to">
<node CREATED="1249752861732" ID="Freemind_Link_1674036470" MODIFIED="1249752976017" TEXT="command line tool(s) which will create/update the domain on a specified storage system (i.e. RDBMS, Modern Databases, XML)"/>
<node CREATED="1249752964320" ID="Freemind_Link_522077713" MODIFIED="1249753006711" TEXT="command line tool(s) which will create/update the domain Classes on the specified platform"/>
<node CREATED="1250966208056" ID="Freemind_Link_1970406391" MODIFIED="1250966227082" TEXT="should load information about the entities such as relations into the entity manager"/>
</node>
<node CREATED="1249752803405" ID="Freemind_Link_117786034" MODIFIED="1249752850140" TEXT="have to take into account all the syntax that will be needed"/>
</node>
</node>
<node CREATED="1278415183684" FOLDED="true" ID="ID_1692987069" MODIFIED="1279012353577" POSITION="right" TEXT="Proxy">
<node CREATED="1278415186531" ID="ID_427935502" MODIFIED="1278415257688" TEXT="?should load the related mapping when user tries to get or set the relation"/>
</node>
<node COLOR="#006699" CREATED="1278413978853" FOLDED="true" ID="ID_1646577673" MODIFIED="1279012350530" POSITION="right" TEXT="Domain Autoloader">
<node CREATED="1278413985830" ID="ID_1008870720" MODIFIED="1278414007663" TEXT="Should load domain configuration and class mapping with the class"/>
<node CREATED="1278414008243" ID="ID_1994664048" MODIFIED="1278414016930" TEXT="steps">
<node CREATED="1278414016932" ID="ID_1277316258" MODIFIED="1278414032652" TEXT="determine the domain and its folder"/>
<node CREATED="1278414033153" ID="ID_1827294247" MODIFIED="1278414050443" TEXT="check if the domain configuration was already loaded"/>
<node CREATED="1278414050863" ID="ID_649161680" MODIFIED="1278414056938" TEXT="if not load the configuration"/>
<node CREATED="1278414058862" ID="ID_890123721" MODIFIED="1278416131648" TEXT="find a way to seperate mapping for seperate classes"/>
</node>
</node>
<node CREATED="1279012254069" ID="ID_58825089" MODIFIED="1279012255808" POSITION="right" TEXT="hooks">
<node CREATED="1279012256836" ID="ID_364330323" MODIFIED="1279012267119" TEXT="user can register update/insert/delete hook"/>
<node CREATED="1279012267908" ID="ID_953127503" MODIFIED="1279012278623" TEXT="simdal will call the hook with the appropriate data"/>
<node CREATED="1279012278947" ID="ID_1638196409" MODIFIED="1279012290767" TEXT="the hook can send back data to be used in the callback"/>
<node CREATED="1279012291187" ID="ID_567234896" MODIFIED="1279012305902" TEXT="the data will be sent to a user defined handler"/>
<node CREATED="1279012375201" ID="ID_391285846" MODIFIED="1279012400827" TEXT="set certain entities to be commited last so they can be used in hooks"/>
</node>
</node>
</map>
