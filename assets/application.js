(function ($) {
 
    $("#uebungsmenu").change(function() {
        window.location = $(":selected",this).attr("rel")
    });

}(jQuery));