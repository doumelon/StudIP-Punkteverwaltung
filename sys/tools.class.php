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



    /** Klasse f�r zentrale Funktionen **/
    class Tools
    {

        /** Methode, die eine Messagebox generiert, sofern Daten vorhanden sind
         * @see http://docs.studip.de/develop/Entwickler/ModalerDialog
         * @see http://docs.studip.de/develop/Entwickler/Messagebox
         * @param $paMessage Message-Array
         * @return Booleanwert, ob die Nachricht eine Information / Success war
         **/
        static function showMessage( $paMessage, $pcURL = "?" )
        {
            if ( (empty($paMessage)) || (!is_array($paMessage)) || (!isset($paMessage["type"])) || (!isset($paMessage["msg"])) )
                return true;

            $la = array();
            if ( (isset($paMessage["info"])) && (is_array($paMessage["info"])) )
                $la = $paMessage["info"];

            if (($paMessage) && (strcasecmp($paMessage["type"], "error") == 0))
            {
                echo MessageBox::error($paMessage["msg"], $la);
                return false;
            } elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "success") == 0))
                echo MessageBox::success($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "info") == 0))
                echo MessageBox::info($paMessage["msg"], $la);
            elseif ( ($paMessage) && (strcasecmp($paMessage["type"], "question") == 0) )
                echo createQuestion($paMessage["msg"], array("dialogyes" => true), array("dialogno" => true), $paMessage["url"] );

            return true;
        }


        /** Methode um einen Messagetext zu generieren
         * @param $pcTyp ist der Messagetyp, Werte sind: error, success, info, question
         * @param $pcText Text der Nachricht
         * @param $paInfo weitere Texte oder f�r den Question-Dialog das return Array
         * @param $pcURL URL auf die geleitet werden soll
         * @return Array mit Messagedaten
         **/

        static function createMessage( $pcType, $pcText, $paInfo = array(), $pcURL = "?" )
        {
            return array("type" => $pcType, "msg" => $pcText, "info" => $paInfo, "url" => $pcURL );
        }
    
    
        /** setzt alle notwendigen Elemente in den HTML Header
         * @warning nach HTML5 ist das Attribut "charset" veraltet
         * @see http://docs.studip.de/develop/Entwickler/PageLayout
         * @param $poPlugin PluginObjekt
         **/
        static function addHTMLHeaderElements( $poPlugin )
        {
            // da StudIP < 3 keine Charset-Option bei addScript bzw addStylesheet erlaubt
            // wird �ber addHeaderElement der Eintrag manuell gesetzt und UTF-8 als
            // Encoding verwendet, da StudIP Windows-1252 als Encoding ist (was einfach
            // absolut veraltet ist und dadurch massiv zu Encoding-Problemen f�hrt)
        
            PageLayout::addHeadElement( "link", array( "charset" => "UTF-8", "rel" => "stylesheet", "href" => $poPlugin->getPluginUrl() . "/assets/style.css") );
            PageLayout::addHeadElement( "link", array( "charset" => "UTF-8", "rel" => "stylesheet", "href" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/themes/lightcolor/blue/jtable.min.css") );
        
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/assets/application.js") );
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/jquery.jtable.min.js") );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/jquery.jtable.min.js") );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/jtable/localization/jquery.jtable.de.js") );
        
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/d3.v3/d3.v3.min.js" ) );
            PageLayout::addHeadElement( "script", array( "charset" => "UTF-8", "src" => $poPlugin->getPluginUrl() . "/sys/extensions/d3.v3/box.js") );
        }
        
    }
    
    ?>
