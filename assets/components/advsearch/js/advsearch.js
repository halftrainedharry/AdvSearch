/*
 * advsearch 1.0.0 - package AdvSearch - jQuery 1.10.2
 * author:  Coroico - www.revo.wangba.fr - 15/05/2012
 *          goldsky - goldsky@virtudraft.com - 23/12/2013
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */
jQuery(function($) {
    // minimum number of characters. Should be coherent with advSearch snippet call
    var _minChars = 3;
    var blockHistoryEvent = true;
    var gMapMarkers = [];
    var markersArray = [];
    var gMapHolder;
    var searchTracker = [];

    // Google Map
    $.fn.advSearchGMap = function(advInstance, options) {
        this.gMap = null;
        var _this = this;

        var settings = $.extend({}, {
            zoom: 5,
            centerLat: 0,
            centerLon: 0
        }, options);

        this.initialize = function() {
            var mapOptions = {
                zoom: settings.zoom
            };

            _this.gMap = new google.maps.Map(_this.get(0), mapOptions);
            if (!gMapMarkers || gMapMarkers.length === 0) {
                if ((settings.centerLat === 0) && (settings.centerLon === 0)) {
                    var initialLocation = new google.maps.LatLng(0, 0);
                    var browserSupportFlag = new Boolean();

                    // https://developers.google.com/maps/articles/geolocation
                    // Try W3C Geolocation (Preferred)
                    if (navigator.geolocation) {
                        browserSupportFlag = true;
                        navigator.geolocation.getCurrentPosition(function(position) {
                            initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                            _this.gMap.setCenter(initialLocation);
                        }, function() {
                            handleNoGeolocation(browserSupportFlag);
                        });
                    }
                    // Browser doesn't support Geolocation
                    else {
                        browserSupportFlag = false;
                        handleNoGeolocation(browserSupportFlag);
                    }

                    function handleNoGeolocation(errorFlag) {
                        if (errorFlag === true) {
                            alert("Geolocation service failed.");
                        } else {
                            alert("Your browser doesn't support geolocation.");
                        }
                        _this.gMap.setCenter(initialLocation);
                    }
                } else {
                    _this.gMap.setCenter(new google.maps.LatLng(settings.centerLat, settings.centerLon));
                }
            } else {
                var bounds = new google.maps.LatLngBounds(),
                        as;
                if (typeof(advInstance) !== 'object') {
                    as = JSON.parse(advInstance);
                } else {
                    as = advInstance;
                }
                _this.gMap.markers = [];
                $.each(gMapMarkers, function(index, item) {
                    var markerOptions = {
                        position: item['position'],
                        map: _this.gMap,
                        title: item['title'],
                        urlID: item['urlID']
                    };

                    var marker = new google.maps.Marker(markerOptions);

                    google.maps.event.addListener(marker, 'click', function(event) {
                        $.ajax({
                            url: as.mapUrl,
                            cache: false,
                            data: {
                                urlID: item['urlID']
                            },
                            'dataType': 'html',
                            'success': function(data) {
                                marker.infowindow = new google.maps.InfoWindow({
                                    content: data
                                });
                                marker.infowindow.open(_this.gMap, marker);
                            }
                        });
                    });

                    markersArray.push(marker);
                    bounds.extend(item['position']);
                    _this.gMap.markers[String(item['lat']) + ',' + String(item['long'])] = marker;
                });
                _this.gMap.fitBounds(bounds);
            }
            // save the object for cleaning service
            gMapHolder = _this.gMap;
            _this.data('map', _this.gMap);

            return _this;
        };

        this.getMarkers = function() {
            return markersArray;
        };

        this.getOptions = function() {
            return _this.settings;
        };

        this.setOptions = function(settings) {
            _this.settings = $.extend({}, _this.settings, settings);
        };

        return this;
    };

    $.fn.advSearchInit = function(as) {
        activateAsInstance(as);
        return this;
    };

    $.fn.reswinUp = function(action) {
        return this.each(function() {
            switch (action) {
                case "showfade":
                    $(this).fadeOut(800).hide(1200);
                    break;
                case "slidefade":
                    $(this).fadeOut(800).slideUp(1200);
                    break;
                case "basic":
                default:
                    $(this).hide();
            }
        });
    };

    $.fn.reswinDown = function(action) {
        return this.each(function() {
            switch (action) {
                case "showfade":
                    $(this).show(800).fadeIn(1200);
                    break;
                case "slidefade":
                    $(this).slideDown(800).fadeIn(1200);
                    break;
                case "basic":
                default:
                    $(this).show();
            }
        });
    };

    /**
     * activate search instances
     * @param {object} opt options
     * @returns {undefined}
     */
    function activateSearch(opt) {
        // Each advsearch instance has its own index ias
        for (var ias = 0; ias < advsea.length; ias++) {
            var asv = JSON.parse(advsea[ias]);
            if (opt && opt.isHistoryEvent) {
                asv.isHistoryEvent = opt.isHistoryEvent;
            }
            activateAsInstance(asv);
        }
    }

    function activateAsInstance(as) {
        if (!as.ajaxUrl) {
            return false; // no AJAX endpoint defined to get the results from
        }

        // as.asid : advsearch instance id

        if (!as.liveSearch) {
            // live search off by default
            as.liveSearch = 0;
        }
        if (!as.init) {
            // initial display
            as.init = 'none';
        }
        if (!as.minChars) {
            // min chars
            as.minChars = _minChars;
        }
        if (!as.searchParam) {
            // search index
            as.searchParam = 'search';
        }
        if (!as.pageParam) {
            // page index
            as.pageParam = 'page';
        }

        as.liveSearchTimeout = null;   // livesearch timeout
        as.isSearching = false;  // is searching flag

        var prefix = as.asid + '_';
        as.prefix = prefix; //advsearch instance prefix

        as.searchField = $('#' + prefix + 'advsea-search');   // advsearch input field
        as.searchField.unbind();  // detach existing function if any
        var ref = as.searchField;

        as.selectField = $('#' + prefix + 'advsea-select');   // select input field if it exists
        as.selectField.unbind();  // detach existing function if any
        as.submitButtonVal = "Search";
        if (!as.liveSearch) {
            as.submitButton = $('#' + prefix + 'advsea-submit');  // advsearch submit button if it exists
            as.submitButton.unbind();  // detach existing function if any
            as.submitButtonVal = as.submitButton.attr('value');
            ref = as.submitButton;
        }

        $('.advsea-close-img').each(function() {
            $(this).remove();
        });
        if (as.closeImgId && $('#' + as.closeImgId)[0]) {
            as.closeImgEl = $(as.closeImg).addClass('advsea-close-img').hide(); // advsearch close img
            $('#' + as.closeImgId).html(as.closeImgEl);
        } else {
            as.closeImgEl = $(as.closeImg).addClass('advsea-close-img').insertAfter(ref).hide(); // advsearch close img
        }
        $('.advsea-load-img').each(function() {
            $(this).remove();
        });
        if (as.loadImgId && $('#' + as.loadImgId)[0]) {
            as.loadImgEl = $(as.loadImg).addClass('advsea-load-img').hide(); // advsearch load img
            $('#' + as.loadImgId).html(as.loadImgEl);
        } else {
            as.loadImgEl = $(as.loadImg).addClass('advsea-load-img').insertAfter(ref).hide(); // advsearch load img
        }

        as.resultEl = $('#' + prefix + 'advsea-reswin').hide().removeClass('init'); // advsearch results window - hide window

        as.closeImgEl.unbind();  // detach existing function if any
        as.closeImgEl.click(function(event) {
            (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
            // adds the closeSearch function to the on click on close image.
            closeSearch(as);
            return false;
        });

        if (!as.liveSearch) {
            // with non livesearch adds the doSearch function to the submit button
            as.submitButton.click(function(event) {
                (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                if (as.useHistory && as.isHistoryEvent) {
                    delete(as.isHistoryEvent);
                }
                doSearch(as);
                return false;
            });
        } else {
            // with the livesearch mode, adds the doLiveSearch function. Launched after each typed character.
            as.searchField.keyup(function() {
                if (as.useHistory && as.isHistoryEvent) {
                    delete(as.isHistoryEvent);
                }
                return doLiveSearch(as);
            });
        }

        if (as.searchField.length) {
            // add the doSearch function to the input field. Launched after each typed character.
            as.searchField.keydown(function(event) {
                var keyCode = event.keyCode || event.which;
                if (keyCode === 13) {
                    (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                    if (as.useHistory && as.isHistoryEvent) {
                        delete(as.isHistoryEvent);
                    }
                    return doSearch(as);
                }
            });
        }

        var isInitialSearch = true;

        return doSearch(as, isInitialSearch); // display results
    }

    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    function setGMapMarkers(as, json) {
        if (typeof (google) !== 'object') {
            console.error('Missing google object');
            return;
        }
        gMapMarkers = []; // reset data
        // reset existing markers
        if (markersArray) {
            for (var i in markersArray) {
                markersArray[i].setMap(null);
            }
            markersArray.length = 0;
        }

        $.each(json, function(index, item) {
            if (!item[as['mapLat']] || !item[as['mapLong']]) {
                return;
            }
            var options = {
                position: new google.maps.LatLng(item[as['mapLat']], item[as['mapLong']]),
                lat: item[as['mapLat']],
                long: item[as['mapLong']],
                title: item[as['mapTitle']],
                urlID: item['id']
            };
            gMapMarkers.push(options);
            // check existing gMap's instance
            if (gMapHolder) {
                var marker = new google.maps.Marker(options);
                marker.setMap(gMapHolder);
                markersArray.push(marker);
            }
        });
    }

    function doLiveSearch(as) {
        if (as.liveSearchTimeout) {
            window.clearTimeout(as.liveSearchTimeout);
        }
        as.liveSearchTimeout = window.setTimeout(function() {
            return doSearch(as);
        }, 400);
    }

    function doSearch(as, isInitialSearch = false) {
        var prefix = as.asid + '_';      // prefix for the instance
        as.useHistory = as.useHistory - 0; // type casting
        if (!as.liveSearch && as.isSearching) {
            return false;  // search already launched
        }

        // search term analysis
        var searchString = '';
        if (as.searchField.length) {
            // simple search
            searchString = as.searchField.val();
        } else if (as.selectField.length) {  // multiple select input
            var sl = new Array();
            as.selectField.find('option:selected').each(function(i) {
                sl.push($(this).attr('value'));
            }); // get the selected options
            searchString = sl.join(" "); // concatenation of the selected options
        }
        as.searchString = searchString;

        // if ((as.init !== 'all') ||
        //         as.searchField.length && as.liveSearch && (st.length < as.minChars) // liveSearch needs minChars before to start
        //         ) {
        //     return false;
        // }

        var pars = {
            asid: as.asid,
            sub: as.submitButtonVal
        };
        pars[as.searchParam] = as.searchString;

        if (as.useHistory) {
            var uri = new URI(document.location.href),
                    uriQuery = uri.query(true);
        }

        /**
         * Page number
         */
        if (as.useHistory && (as.isHistoryEvent || isInitialSearch) && !as.navLinkClicked) {
            if (uriQuery[as.pageParam]) {
                pars[as.pageParam] = uriQuery[as.pageParam];
            }
        } else if (typeof (as.page) === 'number' && as.page > 0) {
            pars[as.pageParam] = as.page;
        } else {
            pars[as.pageParam] = 1;
        }

        if (typeof (pars[as.pageParam]) === 'undefined' || pars[as.pageParam] === 'undefined') {
            pars[as.pageParam] = 1;
        }

        // form content as serialized object
        var formDom = $('#' + prefix + 'advsea-form');
        // populate URL if history exists or direct URL
        if (as.useHistory && (as.isHistoryEvent || isInitialSearch)) {
            var formData = {};
            $.each(uriQuery, function(idx, val) {
                var checkbox = /(\[\])$/.test(idx);
                if (checkbox) {
                    idx = idx.replace(/(\[\])$/, '');
                    val = val.split(',');
                }
                formData[idx] = val;
            });
            formDom.populate(formData);

            if (isInitialSearch && !uriQuery[as.searchParam] && (as.init !== 'all')){
                //Abort search if there is no search-string in the URL
                return;
            }

            pars[as.searchParam] = uriQuery[as.searchParam];
        }

        if (isInitialSearch && (as.init == 'all')){
            // Don't test the length of the search-string
        } else {
            // Test the length of the search-string
            if (as.searchField.length && ((as.liveSearch && (pars[as.searchParam].length < as.minChars)) || (!as.liveSearch && pars[as.searchParam].length == 0))) { // liveSearch needs minChars before to start
                return false;
            }
        }

        var formVals = formDom.serializeObject();
        as.formValsJson = JSON.stringify(formVals);
        // page
        as.page = (as.formValsJson !== searchTracker[searchTracker.length - 1]) ? 1 : parseInt(as.page);

        if (searchTracker.length > 0 && (as.formValsJson !== searchTracker[searchTracker.length - 1])){
            // Reset page number if form data has changed
            pars[as.pageParam] = 1;
        }

        pars['asform'] = as.formValsJson;

        // ======================== we start the search
        as.isSearching = true;
        if (!as.liveSearch) {
            as.submitButton.attr('disabled', 'disabled');  // submit button disabled
        }

        as.closeImgEl.hide(); // hide the close button
        as.loadImgEl.show(); // show the load button
        as.resultEl.css('opacity', as.opacity / 2);

        return $.getJSON(as.ajaxUrl, pars, function(data) {
            if (data) {
                var ids = '';
                if (data.ids) {
                    ids = data.ids;
                }
                var json = '';
                if (data.json) {
                    json = data.json;
                }
                var html = '';
                if (data.html) {
                    html = data.html;
                }

                as.perpage = parseInt(data.perpage);    // amount of results per page
                as.page = parseInt(data.page);    // current page
                as.pagingtype = parseInt(data.pagingtype);    // paging type
                as.total = parseInt(data.total);    // total amount of results

                as.resultEl.hide();
                as.resultEl.html(html).css('opacity', as.opacity).reswinDown(as.effect);
                if (as.mapId && json) {
                    var mapCanvas = $('#' + as.mapId);
                    if (mapCanvas.length > 0) {
                        var map = mapCanvas.advSearchGMap(as, {
                            "zoom": (as.mapZoom - 0),
                            "centerLat": as.mapCenterLat,
                            "centerLong": as.mapCenterLong
                        });
                        setGMapMarkers(as, JSON.parse(json));
                        map.initialize();
                    }
                }
                if (as.pagingtype === 1) {
                    initPageType1(as);
                } else if (as.pagingtype === 2) {
                    initPageType2(as);
                } else if (as.pagingtype === 3) {
                    initPageType3(as);
                }
            }
            if (!as.liveSearch) {
                as.submitButton.removeAttr('disabled'); // submit button enabled
            }
            as.loadImgEl.hide();   // hide the load button
            as.closeImgEl.show();   // show the close button
            as.isSearching = false;  // new search allowed
            if (as.useHistory) {
                if (!as.isHistoryEvent) {
                    setHistory(as, pars);
                }
            }

            searchTracker.push(as.formValsJson);
        });
    }

    function closeSearch(as) {
        as.resultEl.reswinUp(as.effect);
        as.closeImgEl.hide();
        as.loadImgEl.hide();
        if (as.searchField.length) {
            $('#' + as.prefix + 'advsea-form')[0].reset();
            as.page = 1;
            if (as.useHistory) {
                History.pushState({}, document.title, document.location.origin + document.location.pathname);
            }
        }
        as.isSearching = false;
        if (!as.liveSearch) {
            as.submitButton.removeAttr('disabled'); // enabled the submit button
        }
    }

//============================================== Previous / next functions ==========================

    function initPageType1(as) {  // add previous & next links after the display of results
        if (as) {
            var next = as.resultEl.find('.advsea-next a');
            next.prop("href", "javascript:void(0);"); // remove href
            next.attr("href", "javascript:void(0);"); // remove href, blame IE
            next.click(function(event) {
                (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                prevNext(as, 1);
                return false;
            });
            var prev = as.resultEl.find('.advsea-previous a');
            prev.prop("href", "javascript:void(0);"); // remove href
            prev.attr("href", "javascript:void(0);"); // remove href, blame IE
            prev.click(function(event) {
                (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                prevNext(as, -1);
                return false;
            });
        }

    }

//============================================== Page number links ==========================

    function initPageType2(as) {  // add link to each page number
        if (as) {
            var links = as.resultEl.find('.advsea-page a').not('.advsea-current-page a');
            links.each(function() {
                var href = $(this).data("href");
                if (typeof(href) === 'undefined' || href === '') {
                    href = $(this).attr("href");
                }
                $(this).prop("href", "javascript:void(0);"); // remove href
                $(this).attr("href", "javascript:void(0);"); // remove href, blame IE
                var rg = /&page=([0-9]*)/i;
                var pag = rg.exec(href);
                $(this).click(function(event) {
                    (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                    pageLink(as, pag[1]);
                    return false;
                });
            });
        }

    }

//============================ Previous / next + Page number links ==========================

    function initPageType3(as) {
        if (as) {
            var links = as.resultEl.find('.advsea-page a').not('.advsea-current-page a');
            links.each(function() {
                var href = $(this).data("href");
                if (typeof(href) === 'undefined' || href === '') {
                    href = $(this).attr("href");
                }
                $(this).prop("href", "javascript:void(0);"); // remove href
                $(this).attr("href", "javascript:void(0);"); // remove href, blame IE
                var rg = /&page=([0-9]*)/i;
                var pag = rg.exec(href);
                $(this).click(function(event) {
                    (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                    pageLink(as, pag[1]);
                    return false;
                });
            });
            var next = as.resultEl.find('.advsea-next a');
            next.prop("href", "javascript:void(0);"); // remove href
            next.attr("href", "javascript:void(0);"); // remove href, blame IE
            next.click(function(event) {
                (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                prevNext(as, 1);
                return false;
            });
            var prev = as.resultEl.find('.advsea-previous a');
            prev.prop("href", "javascript:void(0);"); // remove href
            prev.attr("href", "javascript:void(0);"); // remove href, blame IE
            prev.click(function(event) {
                (event.preventDefault) ? event.preventDefault() : (event.returnValue = false);
                prevNext(as, -1);
                return false;
            });
        }
    }

//======================================== links generators ==========================

    function prevNext(as, dir) { // update of the page of results
        as.page = as.page - 0 + dir; // typecasting
        as.navLinkClicked = true;
        if (as.useHistory && as.isHistoryEvent) {
            delete(as.isHistoryEvent);
        }
        return doSearch(as);
    }

    function pageLink(as, page) { // add page link
        as.page = page - 0; // typecasting
        as.navLinkClicked = true;
        if (as.useHistory && as.isHistoryEvent) {
            delete(as.isHistoryEvent);
        }
        return doSearch(as);
    }

//============================================== history.js ==========================

    var History = window.History;

    function setHistory(as, pars) {
        if (!History || !History.enabled || as.isHistoryEvent) {
            return;
        }
        var href = buildUrl(as, pars);
        if (href !== document.location.href) {
            blockHistoryEvent = true;
            History.pushState(pars, document.title, href);
        }
        return href;
    }

    function buildUrl(as, pars) {
        var asformArr = new Array();
        var parseForm = JSON.parse(as.formValsJson);
        var uri = new URI(document.location.href),
                uriQuery = uri.query(true);
        //var newUri = $.extend({}, uriQuery, parseForm, {sub: as.submitButtonVal});
        var newUri = $.extend({}, parseForm, {sub: as.submitButtonVal});

        // add page parameter
        if (typeof (newUri[as.pageParam]) == 'undefined'){
            if (pars[as.pageParam]) {
                newUri[as.pageParam] = pars[as.pageParam];
            } else {
                newUri[as.pageParam] = 1;
            }
        } else {
            if (typeof (newUri[as.pageParam]) !== 'undefined' || newUri[as.pageParam] !== 'undefined' && (newUri[as.pageParam] - 0) === as.page) {
                newUri[as.pageParam] = pars[as.pageParam];
            } else {
                newUri[as.pageParam] = 1;
            }
        }

        $.each(newUri, function(index, item) {
            $.merge(asformArr, [index + '=' + item]);
        });
        var asformStr = "?" + asformArr.join('&');
        var hash = uri.hash();
        if (hash) {
            asformStr = asformStr + hash;
        }
        return document.location.origin + document.location.pathname + asformStr;
    }

    if (History && History.enabled) {
        History.Adapter.bind(window, 'statechange', function() {
            if (!blockHistoryEvent) {
                activateSearch({isHistoryEvent: 1});
            }
            // resetting value
            blockHistoryEvent = false;
        });
    }

    activateSearch(); // as soon as the DOM is loaded

});