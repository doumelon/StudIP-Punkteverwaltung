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
    
    
    
    Tools::showMessage($flash["message"]);
    
    try {
        
        /*
         if ($loStudent)
         {
         echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
         echo "<tr><td><strong>"._("Anerkennung f¸r den Studiengang:")."</strong></td><td colspan=\"2\">";
         
         $laStudiengang = reset($loStudent->studiengang($loVeranstaltung));
         if ($loVeranstaltung->isClosed())
         echo $laStudiengang["abschluss"]." ".$laStudiengang["fach"];
         
         else {
         $laStudiengaenge = $loStudent->studiengang();
         
         if (count($laStudiengaenge) > 1)
         {
         $laStudiengang = reset($loStudent->studiengang($loVeranstaltung));
         
         
         echo "<form method=\"post\" action=\"".$controller->url_for("show/studiengang")."\">\n";
         CSRFProtection::tokenTag();
         
         echo "<select name=\"studiengang\" size=\"1\">";
         foreach ($laStudiengaenge as $item)
         if ( ($item["abschluss_id"]) && ($item["fach_id"]) ) {
         $lcSelect = null;
         if ( (!empty($laStudiengang)) && ($laStudiengang["abschluss_id"] == $item["abschluss_id"]) && ($laStudiengang["fach_id"] == $item["fach_id"]) )
         $lcSelect = "selected=\"selected\"";
         
         echo "<option value=\"".$item["abschluss_id"]."#".$item["fach_id"]."\" ".$lcSelect.">".trim($item["abschluss"]." ".$item["fach"])."</option>";
         }
         echo "</select>";
         
         echo "<input type=\"submit\" name=\"submitted\" value=\""._("¸bernehmen")."\"/>";
         echo "</form>";
         
         } else {
         $laStudiengaenge = reset($laStudiengaenge);
         echo $laStudiengaenge["abschluss"]." ".$laStudiengaenge["fach"];
         }
         
         }
         echo "</td></tr>";
         
         echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
         if (!$loVeranstaltung->isClosed())
         echo "<tr><td colspan=\"3\"><strong>"._("Die nachfolgende Angabe bezieht sich auf den aktuellen Stand des ‹bungsbetriebes, somit ist die Angabe unter Umst‰nden inkorrekt / unvollst‰ndig z.B. wenn noch nicht alle Daten eingetragen wurden!")."</strong></td></tr>";
         echo "<tr><td>"._("bestanden (Bonuspunkte)")."</td><td colspan=\"2\">".($laAuswertung["studenten"][$loStudent->id()]["veranstaltungenbestanden"] ? _("ja") : _("nein"))." (".$laAuswertung["studenten"][$loStudent->id()]["bonuspunkte"].")</td></tr>";
         
         }
         
         echo "</table>";
         */
        
        
        
    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }
    
?>
