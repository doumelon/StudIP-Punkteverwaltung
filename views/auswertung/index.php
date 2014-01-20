<?php

   /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@tu-clausthal.de>       #
    # This program is free software: you can redistribute it and/or modify     #
    # it under the terms of the GNU General Public License as                  #
    # published by the Free Software Foundation, either version 3 of the       #
    # License, or (at your option) any later version.                          #
    #                                                                          #
    # This program is distributed in the hope that it will be useful,          #
    # but WITHOUT ANY WARRANTY; without even the implied warranty of           #
    # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            #
    # GNU General Public License for more details.                             #
    #                                                                          #
    # You should have received a copy of the GNU General Public License        #
    # along with this program. If not, see <http://www.gnu.org/licenses/>.     #
    ############################################################################
    @endcond
    **/



    require_once(dirname(dirname(__DIR__)) . "/sys/auswertung.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltungpermission.class.php");


    
    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!VeranstaltungPermission::hasDozentRecht($loVeranstaltung))
            throw new Exception(_("Sie haben nicht die erforderlichen Rechte"));


        $loAuswertung    = new Auswertung( $loVeranstaltung );
        $laListe         = $loAuswertung->studententabelle();
        
        // Sortierung hart nach Matrikelnummern
        uasort($laListe["studenten"], function($a, $b) { return $a["matrikelnummer"] - $b["matrikelnummer"]; });

        // erzeuge Ausgabe ald PDF
        echo "<h1>PDF Export</h1>";
        echo "<p><table border=\"0\" width=\"15%\">";
        echo "<tr><td>"._("Liste f�r Aushang (nur Matrikelnummer, bestanden (ja/nein) und Bonuspunkte)")."</td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "pdf", "extern" => true))."\">PDF</a></td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "xlsx", "extern" => true))."\">XSLX</a></td></tr>";
        echo "<tr><td>"._("vollst�ndige Liste")."</td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "pdf"))."\">PDF</a></td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "xlsx"))."\">XSLX</a></td></tr>";
        echo "<tr><td>"._("nur bestandene Studenten")."</td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "pdf", "bestandenonly" => true))."\">PDF</a></td><td><a href=\"".$controller->url_for("auswertung/export", array("type" => "xlsx", "bestandenonly" => true))."\">XSLX</a></td></tr>";
        echo "</table> </p>";


        // Bild erzeugen
        // @see http://bl.ocks.org/mbostock/4061502
        echo "<h1>Statistik</h1>";
        echo "<p><div id=\"boxplot\" style=\"height: 350px; width: ".(80*count($laListe["uebungen"]))."px; background-color: #fafafa; border-color: #555; border-style: solid; border-width:1px; margin: 25px auto;\">";

        echo "<script type=\"text/javascript\">";

        echo "var margin = {top: 20, right: 20, bottom: 20, left: 20}, width = 60 - margin.left - margin.right, height = 300 - margin.top - margin.bottom;";
        echo "var min    = Infinity, max = -Infinity;";
        echo "var chart  = d3.box().whiskers(iqr(1.5)).width(width).height(height);";

        echo "d3.json(\"".$statistikaction."\", function(error, json) {";
        echo "var data = [];";
        echo "var i = 0;";
        echo "json.punkteliste.forEach(function(x) {";
        echo "min = Math.min( min, Math.min.apply(null, x) );";
        echo "max = Math.max( max, Math.max.apply(null, x) );";
        echo "data[i++] = x;";
        echo "});";

        echo "chart.domain([min, max]);";
        echo "var svg = d3.select(\"#boxplot\").selectAll(\"svg\")";
        echo ".data(data)";
        echo ".enter().append(\"svg\")";
        echo ".attr(\"class\", \"box\")";
        echo ".attr(\"width\", width + margin.left + margin.right)";
        echo ".attr(\"height\", height + margin.bottom + margin.top)";
        echo ".append(\"g\")";
        echo ".attr(\"transform\", \"translate(\" + margin.left + \",\" + margin.top + \")\")";
        echo ".call(chart);";

        echo "});";

        // berechnet den Interquartilsabstand
        // @see http://de.wikipedia.org/wiki/Quartilabstand#.28Inter-.29Quartilsabstand
        echo "function iqr(k) {";
        echo "return function(d, i) {";
        echo "var q1 = d.quartiles[0], q3 = d.quartiles[2],  iqr = (q3 - q1) * k, i = -1, j = d.length;";
        echo "while (d[++i] < q1 - iqr);";
        echo "while (d[--j] > q3 + iqr);";
        echo "return [i, j];";
        echo "};";
        echo "}";

        echo "</script>";
        echo "</div><p>";



        // Tabelle erzeugen
        echo "<h1>Liste</h1>";
        echo "<p><table width=\"100%\">";
        echo "<tr><th>"._("Name (EMail)")."</th><th>"._("Matrikelnummer")."</th>";

        foreach($laListe["uebungen"] as $laUebung)
            echo "<th>".$laUebung["name"]."  ("._("bestanden").")</th>";

        echo "<th>"._("bestanden")."</th><th>"._("Bonuspunkte")."</th></tr>";



        // erzeuge Tabelle
        foreach ($laListe["studenten"] as $lcStudentKey => $laStudent)
        {
            echo "<tr>";
            echo "<td>".$laStudent["name"]." (".$laStudent["email"].")</td>";
            echo "<td>".$laStudent["matrikelnummer"]."</td>";

            foreach($laListe["uebungen"] as $laUebung)
            {
                echo "<td>";
                echo $laUebung["studenten"][$lcStudentKey]["punktesumme"]." (".($laUebung["studenten"][$lcStudentKey]["bestanden"] ? _("ja") : _("nein")).")";
                echo "</td>";
            }
            echo "<td>".($laStudent["veranstaltungenbestanden"] ? "ja" : "nein")."</td>";
            echo "<td>".$laStudent["bonuspunkte"]."</td>";
            echo "</tr>";
        }

        echo "</table></p>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }


?>
