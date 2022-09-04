/*
 * AdvSearch 1.0.0 - AjaxSearchForm
 * author: Coroico - www.modx.wangba.fr - 30/12/2011
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */

var advsea = new Array();

jQuery(function($) {

    function activateAsfInstance(asv) {
        var p = asv.asid + '_';      // prefix for the instance

        if (asv.cdt) {   // clear default requested
            var si = $('#' + p + 'advsea-search');   // search input
            if (si) {
                si.prop('defaultValue', '');
                si.val('');
                si.prop('placeholder', asv.cdt);
            }
        }
    }

    function activateAdvSearchForm() {
        for (var ias = 0; ias < advsea.length; ias++) { //= Each newSearch instance is activated
            var asv = eval('(' + advsea[ias] + ')');
            activateAsfInstance(asv);
        }
    }

    activateAdvSearchForm(); //= as soon as the DOM is loaded

});