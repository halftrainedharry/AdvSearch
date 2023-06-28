<?php
namespace AdvSearch;

use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use AdvSearch\AdvSearch;
/**
 * AdvSearch - AdvSearchForm class
 *
 * @package 	AdvSearch
 * @author		Coroico
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 - 2015 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to display the search form
 *
 */

class AdvSearchForm extends AdvSearch {

    public function __construct(modX &$modx, array $config = array()) {
        // ajax mode parameters
        if ($config['withAjax']) {
            // &ajaxResultsId - [ resource id | 0]
            $ajaxResultsId = (int) $modx->getOption('ajaxResultsId', $config, 0);
            $config['ajaxResultsId'] = ($ajaxResultsId > 0) ? $ajaxResultsId : 0;
            if (!$config['ajaxResultsId']) {
                $msg = '[AdvSearch] &ajaxResultsId property is required and can not be zero!';
                $modx->log(modX::LOG_LEVEL_ERROR, $msg);
                throw new \Exception($msg);
            }
        }

        parent::__construct($modx, $config);
    }

    /**
     * Output the advSearch form
     *
     * @access public
     * @return string output as string
     */
    public function output() {
        $jsHeaderArray = array();
        $msg = '';

        // initialize searchString
        $this->searchString = $this->_initSearchString();

        // set up the search form
        // &resultsWindowTpl [ chunk name | 'AdvSearchResultsWindow' ]
        $this->config['resultsWindowTpl'] = $this->modx->getOption('resultsWindowTpl', $this->config, 'AdvSearchResultsWindow');

        // add the <div></div> section to set the results window throught jscript
        if ($this->config['withAjax']) {
            $placeholders = array('asId' => $this->config['asId']);
            $resultsWindow = $this->processElementTags($this->parseTpl($this->config['resultsWindowTpl'], $placeholders));
        } else {
            $resultsWindow = '';
        }

        // &method - [ post | get ]
        // $this->config['method'] = strtolower($this->modx->getOption('method', $this->config, 'get'));

        // &landing  [ int id of a document | 0 ]
        $landing = (int) $this->modx->getOption('landing', $this->config, 0);
        $this->config['landing'] = ($landing > 0) ? $landing : $this->modx->resource->get('id');

        // &liveSearch - [ 1 | 0 ]
        $this->config['liveSearch'] = (bool) (int) $this->modx->getOption('liveSearch', $this->config, 0);

        // &searchParam - [ search | any string ]
        // $this->config['searchParam'] = trim($this->modx->getOption('searchParam', $this->config, 'search'));

        // &uncacheScripts - [ 1 | 0 ]
        $uncacheScripts = (bool) (int) $this->modx->getOption('uncacheScripts', $this->config, 1);
        $this->config['uncacheScripts'] = $uncacheScripts ? '?_=' . time() : '';

        // display search form
        $placeholders = array(
            'method' => $this->config['method'],
            'landing' => $this->config['landing'],
            'asId' => $this->config['asId'],
            'searchValue' => htmlspecialchars($this->searchString),
            'searchParam' => $this->config['searchParam'],
            'liveSearch' => $this->config['liveSearch'] ? 1 : 0,
            'resultsWindow' => $resultsWindow
        );

        // &tpl [ chunk name | 'AdvSearchForm' ]
        $this->config['tpl'] = $this->modx->getOption('tpl', $this->config, 'AdvSearchForm');

        $placeholders = $this->cleanPlaceholders($placeholders);
        // set the form into a placeholder if requested
        $output = $this->processElementTags($this->parseTpl($this->config['tpl'], $placeholders));
        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
            $output = '';
        }

        // add the external css and js files
        // add advSearch css file
        if ($this->config['addCss'] == 1) {
            $this->modx->regClientCss($this->config['assetsUrl'] . 'css/advsearch.css' . $this->config['uncacheScripts']);
        }

        //jQuery used by ajax mode
        if ($this->config['withAjax']) {
            // &addJQuery - [ 0 | 1 | 2 ]
            $addJQuery = (int) $this->modx->getOption('addJQuery', $this->config, 1);
            $this->config['addJQuery'] = ($addJQuery == 0 || $addJQuery == 1 || $addJQuery == 2) ? $addJQuery : 1;

            // &jsJQuery - [ Location of the jQuery javascript library ]
            $this->config['jsJQuery'] = $this->modx->getOption('jsJQuery', $this->config, $this->config['assetsUrl'] . 'js/jquery-1.10.2.min.js');
            $this->config['jsJQuery'] = $this->replacePropPhs($this->config['jsJQuery']);

            // include or not the jQuery library (required for clear default text, ajax mode)
            if ($this->config['addJQuery'] == 1) {
                //regClientStartupHTMLBlock
                $this->modx->regClientStartupHTMLBlock('<script>window.jQuery || document.write(\'<script src="' . $this->config['jsJQuery'] . '"><\/script>\');</script>');
            } elseif ($this->config['addJQuery'] == 2) {
                $this->modx->regClientHTMLBlock('<script>window.jQuery || document.write(\'<script src="' . $this->config['jsJQuery'] . '"><\/script>\');</script>');
            }

            // &jsSearch - [ url | $assetsUrl . 'js/advsearch.min.js' ]
            $this->config['jsSearch'] = $this->modx->getOption('jsSearch', $this->config, $this->config['assetsUrl'] . 'js/advsearch.js');
            $this->config['jsSearch'] = $this->replacePropPhs($this->config['jsSearch']);

            // &useHistory - [ 0 | 1 ]
            $this->config['useHistory'] = $this->modx->getOption('useHistory', $this->config, 0);

            if ($this->config['useHistory']) {
                // &jsURI - [ URI.js library ]
                $this->config['jsURI'] = $this->modx->getOption('jsURI', $this->config, $this->config['assetsUrl'] . 'vendors/urijs/src/URI.min.js');
                $this->config['jsURI'] = $this->replacePropPhs($this->config['jsURI']);

                // &jsHistory - [ History.js library ]
                $this->config['jsHistory'] = $this->modx->getOption('jsHistory', $this->config, $this->config['assetsUrl'] . 'vendors/historyjs/scripts/bundled-uncompressed/html5/jquery.history.js');
                $this->config['jsHistory'] = $this->replacePropPhs($this->config['jsHistory']);

                // &jsPopulateForm - [ js populate form library ]
                $this->config['jsPopulateForm'] = $this->modx->getOption('jsPopulateForm', $this->config, $this->config['assetsUrl'] . 'vendors/populate/jquery.populate.pack.js');
                $this->config['jsPopulateForm'] = $this->replacePropPhs($this->config['jsPopulateForm']);
            }

            // include the advsearch js file in the header
            if ($this->config['addJs'] == 1) {
                $addJs = 'regClientStartupScript';
            } elseif ($this->config['addJs'] == 2) { // if ($this->config['addJs'] == 2)
                $addJs = 'regClientScript';
            }

            if ($this->config['addJs'] != 0) {
                $this->modx->$addJs($this->config['jsSearch'] . $this->config['uncacheScripts']);
                if ($this->config['useHistory']) {
                    $this->modx->$addJs($this->config['jsURI']);
                    $this->modx->$addJs($this->config['jsHistory']);
                    $this->modx->$addJs($this->config['jsPopulateForm']);
                }
            }

            // add ajaxResultsId, liveSearch mode and some other parameters in js header
            $jsHeaderArray['asid'] = $this->config['asId'];

            if ($this->config['liveSearch']) {
                $jsHeaderArray['liveSearch'] = $this->config['liveSearch'];
                $jsHeaderArray['minChars'] = $this->config['minChars'];
            }

            if ($this->config['searchParam'] != 'search') {
                $jsHeaderArray['searchParam'] = $this->config['searchParam'];
            }

            if ($this->config['pageParam'] != 'page') {
                $jsHeaderArray['pageParam'] = $this->config['pageParam'];
            }

            if ($this->config['init'] != 'none') {
                $jsHeaderArray['init'] = $this->config['init'];
            }

            $jsHeaderArray['useHistory'] = $this->config['useHistory'];

            // ajax connector
            $jsHeaderArray['ajaxUrl'] = $this->modx->makeUrl($this->config['ajaxResultsId'], '', array(), $this->config['urlScheme']);

            // &ajaxLoaderImageTpl - [ the chunk of spinning loader image. @FILE/@CODE/@INLINE[/@CHUNK] ]
            $ajaxLoaderImageTpl = $this->modx->getOption('ajaxLoaderImageTpl', $this->config, '@CODE <img src="' . $this->config['assetsUrl'] . 'js/images/indicator.white.gif' . '" alt="loading" />');
            $ajaxLoaderImageTpl = $this->replacePropPhs($ajaxLoaderImageTpl);

            // &ajaxCloseImageTpl - [ the chunk of close image. @FILE/@CODE/@INLINE[/@CHUNK] ]
            $ajaxCloseImageTpl = $this->modx->getOption('ajaxCloseImageTpl', $this->config, '@CODE <img src="' . $this->config['assetsUrl'] . 'js/images/close2.png' . '" alt="close search" />');
            $ajaxCloseImageTpl = $this->replacePropPhs($ajaxCloseImageTpl);

            // loader image
            $ajaxLoaderImage = $this->processElementTags($this->parseTpl($ajaxLoaderImageTpl));
            if (!empty($ajaxLoaderImage)) {
                $jsHeaderArray['loadImg'] = addslashes(trim($ajaxLoaderImage));
                // DOM ID that holds the loader image
                $jsHeaderArray['loadImgId'] = $this->modx->getOption('ajaxLoaderImageDOMId', $this->config);
            }
            // close image
            $ajaxCloseImage = $this->processElementTags($this->parseTpl($ajaxCloseImageTpl));
            if (!empty($ajaxCloseImage)) {
                $jsHeaderArray['closeImg'] = addslashes(trim($ajaxCloseImage));
                // DOM ID that holds the close image
                $jsHeaderArray['closeImgId'] = $this->modx->getOption('ajaxCloseImageDOMId', $this->config);
            }

            // &opacity - [ 0. < float <= 1. ]  Should be a float value
            $opacity = floatval($this->modx->getOption('opacity', $this->config, 1.));
            $this->config['opacity'] = ($opacity > 0. && $opacity <= 1.) ? $opacity : 1.0;
            $jsHeaderArray['opacity'] = $this->config['opacity'];

            // &effect - [ 'basic' | 'showfade' | 'slidefade' ]
            $effect = strtolower($this->modx->getOption('effect', $this->config, 'basic'));
            $this->config['effect'] = in_array($effect, ['basic', 'showfade', 'slidefade']) ? $effect : 'basic';
            $jsHeaderArray['effect'] = $this->config['effect'];

            /**
             * Google Map
             */
            $jsHeaderArray['mapId'] = $this->modx->getOption('googleMapDomId', $this->config);
            $jsHeaderArray['mapLat'] = $this->modx->getOption('googleMapLatTv', $this->config);
            $jsHeaderArray['mapLong'] = $this->modx->getOption('googleMapLonTv', $this->config);
            $jsHeaderArray['mapTitle'] = $this->modx->getOption('googleMapMarkerTitleField', $this->config);
            $googleMapMarkerWindowId  = intval($this->modx->getOption('googleMapMarkerWindowId', $this->config));
            if (!empty($googleMapMarkerWindowId)) {
                $jsHeaderArray['mapUrl'] = $this->modx->makeUrl($googleMapMarkerWindowId, '', '', $this->config['urlScheme']);
            }
            $jsHeaderArray['mapZoom'] = (int) $this->modx->getOption('googleMapZoom', $this->config, 5);
            $jsHeaderArray['mapCenterLat'] = $this->modx->getOption('googleMapCenterLat', $this->config);
            $jsHeaderArray['mapCenterLong'] = $this->modx->getOption('googleMapCenterLong', $this->config);

            // &keyval
            $this->config['keyval'] = $this->modx->getOption('keyval', $this->config, '');

            if ($this->config['keyval']) {
                $keyvals = array_map("trim", explode(',', $this->config['keyval']));
                foreach ($keyvals as $keyval) {
                    list($key, $val) = array_map("trim", explode(':', $keyval));
                    $jsHeaderArray[$key] = $val;
                }
            }
        }

        // set up of js header for the current instance
        $jshCount = count($jsHeaderArray);
        if ($jshCount) {
            $json = json_encode($jsHeaderArray);
            $jsline = "advsea[advsea.length]='{$json}';";
            $jsHeader = <<<EOD
<!-- start AdvSearch header -->
<script type="text/javascript">
//<![CDATA[
var advsea = new Array();
{$jsline}
//]]>
</script>
<!-- end AdvSearch header -->
EOD;
            if ($this->config['addJs'] == 1) {
                $this->modx->regClientStartupScript($jsHeader);
            } elseif ($this->config['addJs'] == 2)  {
                $this->modx->regClientScript($jsHeader);
            }
        }

        // log elapsed time
        $this->ifDebug("Elapsed time:" . $this->getElapsedTime(), __METHOD__, __FILE__, __LINE__);

        return $output;
    }

    /**
     * _initSearchString - initialize searchString
     *
     * @access private
     * @return string the search string
     */
    private function _initSearchString() {
        $searchString = '';
        if (isset($this->config['searchString'])) {
            $searchString = $this->config['searchString']; //default value
        }
        if (isset($_REQUEST[$this->config['searchParam']]) && (!empty($_REQUEST[$this->config['searchParam']])) && ($this->forThisInstance())) {
            $searchString = $this->sanitizeSearchString($_REQUEST[$this->config['searchParam']]);
        }
        return $searchString;
    }

}
