<?php
namespace AdvSearch;

use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use MODX\Revolution\modChunk;
use AdvSearch\AdvSearch;
/**
 * AdvSearch - AdvSearchResults class
 *
 * @package 	AdvSearch
 * @author		Coroico - coroico@wangba.fr
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 - 2015 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Class to get search results
 *
 */

class AdvSearchResults extends AdvSearch {

    public $mainClass = modResource::class;
    public $primaryKey = 'id';
    public $mainFields = array();
    public $joinedFields = array();
    public $tvFields = array();
    public $resultsCount = 0;
    public $results = array();
    public $idResults = array();
    public $htmlResult = '';
    protected $page = 1;
    protected $queryHook = null;
    protected $ids = array();
    protected $sortby = array();
    protected $mainWhereFields = array();
    protected $joinedWhereFields = array();
    protected $tvWhereFields = array();
    protected $driver;

    public function __construct(modX & $modx, array & $config = array()) {
        //parent::__construct($modx, $config);
        // get time of starting
        $mtime = explode(" ", microtime());
        $this->tstart = $mtime[1] + $mtime[0];
        
        $this->modx = & $modx;
        $this->debug = ($config['debug'] > 0);
        $this->config = $config;
    }

    /**
     * Run the search
     */
    public function doSearch($asContext) {
        $this->searchString = $asContext['searchString'];
        $this->searchTerms = $asContext['searchTerms'];
        $this->page = $asContext['page'];
        $this->queryHook = $asContext['queryHook'];

        $this->_loadResultsProperties();
        $asContext['mainFields'] = $this->mainFields;
        $asContext['tvFields'] = $this->tvFields;
        $asContext['joinedFields'] = $this->joinedFields;
        $asContext['mainWhereFields'] = $this->mainWhereFields;
        $asContext['tvWhereFields'] = $this->tvWhereFields;
        $asContext['joinedWhereFields'] = $this->joinedWhereFields;
        $asContext['sortby'] = $this->sortby;

        $engine = trim($this->config['engine']);
        if (empty($engine)) {
            $msg = 'Engine was not defined';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }

        // get results
        if (!$this->driver) {
            if ($this->mainClass === modResource::class) {
                // default package (modResource + Tvs) and possibly joined packages
                try {
                    $this->driver = $this->loadDriver($engine);
                } catch (\Exception $ex) {
                    $msg = 'Could not load driver for engine: "' . $engine . '". Exception: ' . $ex->getMessage();
                    $this->setError($msg);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }
            } else {
                // search in a different main package and possibly joined packages
                try {
                    $this->driver = $this->loadDriver('Custom');
                } catch (\Exception $ex) {
                    $msg = 'Could not load driver for engine: "' . $engine . '" Exception: ' . $ex->getMessage();
                    $this->setError($msg);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }
            }
        }
        if ($this->driver) {
            $this->results = $this->driver->getResults($asContext);
            $this->resultsCount = $this->driver->getResultsCount();
            $this->page = $this->driver->getPage();
        } else {
            $msg = 'Driver could not generate the result';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }

        // reset pagination if the output empty while the counter shows more
        if ($this->resultsCount > 0 && count($this->results) === 0) {
            $asContext['page'] = 1;
            $this->page = 1;
            return $this->doSearch($asContext);
        }

        if (empty($this->results)) {
            $this->page = 1;
        } else {
            if (in_array('html', $this->config['output'])) {
                $this->htmlResult = $this->renderOutput($this->results);
            }
            if (in_array('ids', $this->config['output'])) {
                $this->idResults = $this->driver->idResults;
            }
        }

        return $this->results;
    }

    public function getPage() {
        return $this->page;
    }

    public function loadDriver($name) {
        if (!empty($this->config['driverClass'])) {
            $driverClass = $this->config['driverClass'];
        } else {
            $driverClass = 'AdvSearch\\Drivers\\' . $name;
        }

        if (!class_exists($driverClass)) {
            $msg = 'Missing Driver class: ' . $driverClass;
            $this->setError($msg);
            throw new \Exception($msg);
        }

        $driver = new $driverClass($this->modx, $this->config);
        return $driver;
    }

    /**
     * Check the parameters for results part
     *
     * @access private
     * @tutorial Whatever the main class (modResource or an other class) params run the same check process
     *           Some initial values could be overried by values from the query hook
     */
    private function _loadResultsProperties() {
        if (!empty($this->queryHook['main'])) { // a new main package is declared in query hook
            $msg = '';

            if (empty($this->queryHook['main']['class'])) {
                $msg = 'Main - Class name should be defined in queryHook';
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            //Check if the class already exists
            if (!class_exists($this->queryHook['main']['class'])) {
                //Try adding the package
                if (empty($this->queryHook['main']['package'])) {
                    $msg = 'Main - Package name should be declared in queryHook';
                } elseif (empty($this->queryHook['main']['packagePath'])) {
                    $msg = 'Main - Package path should be declared in queryHook';
                }

                if (!empty($msg)) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }

                $this->queryHook['main']['packagePath'] = $this->replacePropPhs($this->queryHook['main']['packagePath']);
                $tablePrefix = isset($this->queryHook['main']['tablePrefix']) ? $this->queryHook['main']['tablePrefix'] : $this->modx->config[modX::OPT_TABLE_PREFIX];
                $added = $this->modx->addPackage($this->queryHook['main']['package'], $this->queryHook['main']['packagePath'], $tablePrefix); // add package
                if (!$added) {
                    return false;
                }
            }

            $this->mainClass = $this->queryHook['main']['class'];  // main class
            $this->primaryKey = $this->modx->getPK($this->mainClass); // get primary key
        }

        // &contexts [ comma separated context names | $modx->context->get('key') ]
        $lstContexts = $this->modx->getOption('contexts', $this->config, $this->modx->context->get('key'));
        $this->config['contexts'] = implode(',', array_map('trim', explode(',', $lstContexts)));

        // &engine [ 'MySql' | ... ] - name of search engine to use
        $engine = trim($this->modx->getOption('engine', $this->config, 'MySql'));
        $this->config['engine'] = !empty($engine) ? $engine : 'MySql';
        $this->config['driverClass'] = $this->modx->getOption('driverClass', $this->config);

        // &fields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource)  '' otherwise ]
        $lstFields = $this->modx->getOption('fields', $this->config, 'pagetitle,longtitle,alias,description,introtext,content');
        if (!empty($this->queryHook['main']['fields'])) {
            $lstFields = $this->queryHook['main']['fields'];
        }
        $fields = array();
        if (!empty($lstFields)) {
            $fields = array_map('trim', explode(',', $lstFields));
        }
        $this->config['fields'] = implode(',', $fields);

        // initialise mainFields : 'id', 'template', 'context_key', 'createdon' + docFields for modResource
        if ($this->mainClass == modResource::class) {
            $requiredFields = array('id', 'context_key');
        } else {
            $requiredFields = array($this->primaryKey);
        }
        $this->mainFields = array_merge($requiredFields, $fields);

        if (!empty($this->queryHook['main']['withFields'])) {
            $lstWithFields = $this->queryHook['main']['withFields'];
        } else {
            // &withFields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource) '' (all fields) otherwise]
            $lstWithFields = $this->modx->getOption('withFields', $this->config, 'pagetitle,longtitle,alias,description,introtext,content');
        }
        if (!empty($lstWithFields)) {
            $this->mainWhereFields = array_map('trim', explode(',', $lstWithFields));
            $this->config['withFields'] = implode(',', $this->mainWhereFields);
        } else {
            $this->config['withFields'] = $lstWithFields;
        }

        if ($this->mainClass == modResource::class) {
            // &hideMenu [ 0 | 1 | 2 ]  Search in hidden documents from menu.
            $hideMenu = (int) $this->modx->getOption('hideMenu', $this->config, 2);
            $this->config['hideMenu'] = (($hideMenu < 3) && ($hideMenu >= 0)) ? $hideMenu : 2;

            // &includeTVs - [ comma separated tv names | '' ]
            $lstIncludeTVs = $this->modx->getOption('includeTVs', $this->config, '');
            if (!empty($lstIncludeTVs)) {
                $this->tvFields = array_map('trim', explode(',', $lstIncludeTVs));
                $this->config['includeTVs'] = implode(',', $this->tvFields);
            } else {
                $this->config['includeTVs'] = $lstIncludeTVs;
            }

            // &withTVs - [ a comma separated list of TV names | '' ]
            $lstWithTVs = $this->modx->getOption('withTVs', $this->config, '');
            if (!empty($lstWithTVs)) {
                $this->tvWhereFields = array_map('trim', explode(',', $lstWithTVs));
                $this->config['withTVs'] = implode(',', $this->tvWhereFields);
            } else {
                $this->config['withTVs'] = $lstWithTVs;
            }

            // remove duplicates between withTVs and includeTVs parameters
            // $this->tvFields = array_unique(array_merge($this->tvWhereFields, $this->tvFields));
        }

        $this->joinedFields = array_merge($this->mainFields, $this->tvFields);
        $this->joinedWhereFields = array_merge($this->mainWhereFields, $this->tvWhereFields);

        if (!empty($this->queryHook['main']['lstIds'])) {
            $lstIds = $this->queryHook['main']['lstIds'];
        } else {
            // &ids [ comma separated list of Ids | '' ] - ids or primary keys for custom package
            $lstIds = $this->modx->getOption('ids', $this->config, '');
        }

        if (!empty($lstIds)) {
            $this->ids = array_map('trim', explode(',', $lstIds));
            $this->config['ids'] = implode(',', $this->ids);
        } else {
            $this->config['ids'] = $lstIds;
        }

        if ((!empty($this->queryHook)) && (!empty($this->queryHook['perPage']))) {
            $perPage = $this->queryHook['perPage'];
        } else {
            // &perPage [ int | 10 ] - Set to 0 if unlimited
            $perPage = (int) $this->modx->getOption('perPage', $this->config, 10);
        }
        $this->config['perPage'] = (($perPage >= 0)) ? $perPage : 10;

        if (!empty($this->queryHook['sortby'])) {
            $lstSortby = $this->queryHook['sortby'];
        } else if (!empty($this->queryHook['main']['sortby'])) {
            $lstSortby = $this->queryHook['main']['sortby'];
        } else {
            // &sortby - comma separated list of couple "field [ASC|DESC]" to sort by.
            // field from joined resource should be named resourceName_fieldName. e.g: quipComment_body
            $lstSortby = $this->modx->getOption('sortby', $this->config, 'id DESC');
        }

        if (!empty($lstSortby)) {
            $this->sortby = array();
            $sortCpls = array_map('trim', explode(',', $lstSortby));
            foreach ($sortCpls as $sortCpl) {
                $sortElts = array_map('trim', explode(' ', $sortCpl));
                $classField = !empty($sortElts[0]) ? $sortElts[0] : 'id';
                $dir = strtolower((count($sortElts) < 2 || empty($sortElts[1])) ? 'desc' : $sortElts[1]);
                $dir = in_array($dir, array('asc', 'desc')) ? $dir : 'desc';
                $this->sortby[$classField] = $dir;
            }
        }

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return;
    }

    /*
     * Returns search results output
     *
     * @access public
     * @param AdvSearchResults $asr a AdvSearchResult object
     * @return string Returns search results output
     */

    public function renderOutput($results = array()) {
        if (empty($results)) {
            return false;
        }

        $this->searchTerms = array_unique($this->searchTerms);
        $this->displayedFields = array_merge($this->mainFields, $this->tvFields, $this->joinedFields);
        $this->_loadOutputProperties();

        // pagination
        $pagingOutput = $this->_getPaging($this->resultsCount);

        // results
        $resultsOutput = '';
        $resultsArray = array();
        $idx = ($this->page - 1) * $this->config['perPage'] + 1;
        foreach ($results as $result) {
            if ($this->nbExtracts && count($this->extractFields)) {
                $text = '';
                foreach ($this->extractFields as $extractField) {
                    $text .= "{$this->processElementTags($result[$extractField])}";
                }

                $extracts = $this->_getExtracts(
                    $text, $this->nbExtracts, $this->config['extractLength'], $this->searchTerms, $this->config['extractTpl'], $this->config['extractEllipsis']
                );
            } else {
                $extracts = '';
            }

            $result['idx'] = $idx;
            $result['extracts'] = $extracts;
            if (empty($result['link'])) {
                $ctx = (!empty($result['context_key'])) ? $result['context_key'] : $this->modx->context->get('key');
                if ((int) $result[$this->primaryKey]) {
                    $result['link'] = $this->modx->makeUrl($result[$this->primaryKey], $ctx, '', $this->config['urlScheme']);
                }
            }

            if ($this->config['toArray']) {
                $resultsArray[] = $result;
            } else {
                $result = $this->cleanPlaceholders($result);
                $resultsOutput .= $this->processElementTags($this->parseTpl($this->config['tpl'], $result));
            }
            $idx++;
        }

        $resultsPh = array(
            'paging' => $pagingOutput,
            'pagingType' => $this->config['pagingType'],
        );
        if ($this->config['toArray']) {
            $resultsPh['properties'] = $this->config;
            $resultsPh['results'] = $resultsArray;
            $output = '<pre class="advsea-code">' . print_r($resultsPh, 1) . '</pre>';
        } else {
            $resultsPh['results'] = $resultsOutput;
            $resultsPh = $this->cleanPlaceholders($resultsPh);
            $output = $this->processElementTags($this->parseTpl($this->config['containerTpl'], $resultsPh));
        }

        return $output;
    }

    /**
     * Check parameters for the displaying of results
     *
     * @access private
     * @param array $displayedFields Fields to display
     */
    private function _loadOutputProperties() {

        // &containerTpl [ chunk name | 'AdvSearchResults' ]
        $this->config['containerTpl'] = $this->modx->getOption('containerTpl', $this->config, 'AdvSearchResults');

        // &tpl [ chunk name | 'AdvSearchResult' ]
        $this->config['tpl'] = $this->modx->getOption('tpl', $this->config, 'AdvSearchResult');

        // &showExtract [ string | '1:content' ]
        $showExtractArray = explode(':', $this->modx->getOption('showExtract', $this->config, '1:content'));
        if ((int) $showExtractArray[0] < 0) {
            $showExtractArray[0] = 0;
        }
        if ($showExtractArray[0]) {
            if (!isset($showExtractArray[1])) {
                $showExtractArray[1] = 'content';
            }
            // check that all the fields selected for extract exists in mainFields, tvFields or joinedFields
            $extractFields = array_map('trim', explode(',', $showExtractArray[1]));
            foreach ($extractFields as $key => $field) {
                if (!in_array($field, $this->displayedFields)) {
                    unset($extractFields[$key]);
                }
            }
            $this->extractFields = array_values($extractFields);
            $this->nbExtracts = $showExtractArray[0];
            $this->config['showExtract'] = $showExtractArray[0] . ':' . implode(',', $this->extractFields);
        } else {
            $this->nbExtracts = 0;
            $this->config['showExtract'] = '0';
        }

        if ($this->nbExtracts && count($this->extractFields)) {
            // &extractEllipsis [ string | '...' ]
            $this->config['extractEllipsis'] = $this->modx->getOption('extractEllipsis', $this->config, '...');

            // &extractLength [ 50 < int < 800 | 200 ]
            $extractLength = (int) $this->modx->getOption('extractLength', $this->config, 200);
            $this->config['extractLength'] = (($extractLength < 800) && ($extractLength >= 50)) ? $extractLength : 200;

            // &extractTpl [ chunk name | 'Extract' ]
            $this->config['extractTpl'] = $this->modx->getOption('extractTpl', $this->config, 'AdvSearchExtract');

            // &highlightResults [ 0 | 1 ]
            $highlightResults = (int) $this->modx->getOption('highlightResults', $this->config, 1);
            $this->config['highlightResults'] = (($highlightResults == 0 || $highlightResults == 1)) ? $highlightResults : 1;

            if ($this->config['highlightResults']) {
                // &highlightClass [ string | 'advsea-highlight']
                $this->config['highlightClass'] = $this->modx->getOption('highlightClass', $this->config, 'advsea-highlight');

                // &highlightTag [ tag name | 'span' ]
                $this->config['highlightTag'] = $this->modx->getOption('highlightTag', $this->config, 'span');
            }
        }

        // &pagingType[ 0 | 1 | 2 | 3 ]
        $pagingType = (int) $this->modx->getOption('pagingType', $this->config, 1);
        $this->config['pagingType'] = (($pagingType <= 3) && ($pagingType >= 0)) ? $pagingType : 1;

        if ($this->config['pagingType'] == 1) {
            // &pagingTpl [ chunk name | 'AdvSearchPaging1' ]
            $this->config['pagingTpl'] = $this->modx->getOption('pagingTpl', $this->config, 'AdvSearchPaging1');
        } elseif ($this->config['pagingType'] == 2) {
            // &pagingTpl [ chunk name | 'AdvSearchPaging2' ]
            $this->config['pagingTpl'] = $this->modx->getOption('pagingTpl', $this->config, 'AdvSearchPaging2');

            // &currentPageTpl [ chunk name | 'CurrentPageLink' ]
            $this->config['currentPageTpl'] = $this->modx->getOption('currentPageTpl', $this->config, 'AdvSearchCurrentPageLink');

            // &pageTpl [ chunk name | 'PageLink' ]
            $this->config['pageTpl'] = $this->modx->getOption('pageTpl', $this->config, 'AdvSearchPageLink');

            // &pagingSeparator
            $this->config['pagingSeparator'] = $this->modx->getOption('pagingSeparator', $this->config, ' | ');
        } elseif ($this->config['pagingType'] == 3) {
            // &pagingTpl [ chunk name | 'AdvSearchPaging3' ]
            $this->config['pagingTpl'] = $this->modx->getOption('pagingTpl', $this->config, 'AdvSearchPaging3');

            // &currentPageTpl [ chunk name | 'CurrentPageLink' ]
            $this->config['currentPageTpl'] = $this->modx->getOption('currentPageTpl', $this->config, 'AdvSearchCurrentPageLink');

            // &pageTpl [ chunk name | 'PageLink' ]
            $this->config['pageTpl'] = $this->modx->getOption('pageTpl', $this->config, 'AdvSearchPageLink');

            // &pagingSeparator
            $this->config['pagingSeparator'] = $this->modx->getOption('pagingSeparator', $this->config, ' | ');

            // &pagingOuterRange
            $outerRange = (int) $this->modx->getOption('paging3OuterRange', $this->config, 2);
            $this->config['paging3OuterRange'] = ($outerRange > 0) ? $outerRange : 2;

            // &pagingMiddleRange
            $middleRange = (int) $this->modx->getOption('paging3MiddleRange', $this->config, 3);
            $this->config['paging3MiddleRange'] = ($middleRange > 0) ? $middleRange : 3;

            // &pagingRangeSplitter
            $this->config['paging3RangeSplitterTpl'] = $this->modx->getOption('paging3RangeSplitterTpl', $this->config, '@INLINE <span class="advsea-page"> ... </span>');
        }

        if ($this->config['withAjax']) {
            // &moreResults - [ int id of a document | 0 ]
            $moreResults = (int) $this->modx->getOption('moreResults', $this->config, 0);
            $this->config['moreResults'] = ($moreResults > 0) ? $moreResults : 0;

            // &moreResultsTpl [ chunk name | 'AdvSearchMoreResults' ]
            $this->config['moreResultsTpl'] = $this->modx->getOption('moreResultsTpl', $this->config, 'AdvSearchMoreResults');
        }

        // &toArray [ 0| 1 ]
        $this->config['toArray'] = (bool) $this->modx->getOption('toArray', $this->config, 0);

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return true;
    }

    /*
     * Format pagination
     *
     * @access private
     * @param integer $resultsCount The number of results found
     * @param integer $pageResultsCount The number of results for the current page
     * @return string Returns search results output header info
     */

    private function _getPaging($resultsCount) {
        if (!$this->config['perPage'] || !$this->config['pagingType']) {
            return;
        }
        $id = $this->modx->resource->get('id');
        $idParameters = $this->modx->request->getParameters(); //Pagination doesn't work for POST. $this->modx->request->getParameters([], 'REQUEST');
        $this->page = intval($this->page);

        // first: number of the first result of the current page, last: number of the last result of current page,
        // page: number of the current page, nbpages: total number of pages
        $nbPages = (int) ceil($resultsCount / $this->config['perPage']);
        $flatCount = $this->page * $this->config['perPage'];
        $last = $flatCount <= $resultsCount ? $flatCount : $resultsCount;
        $pagePh = array(
            'first' => ($this->page - 1) * $this->config['perPage'] + 1,
            'last' => $last,
            'total' => $resultsCount,
            'currentpage' => $this->page,
            'page' => $this->page, // by convention
            'nbpages' => $nbPages,
            'totalPage' => $nbPages, // by convention
        );

        // $this->modx->setPlaceholders($pagePh, $this->config['placeholderPrefix']);

        $qParameters = array();
        if (!empty($this->queryHook['requests'])) {
            $qParameters = $this->queryHook['requests'];
        }

        if ($this->config['pagingType'] == 1) {
            // pagination type 1
            $previousCount = ($this->page - 1) * $this->config['perPage'];
            $pagePh['previouslink'] = '';
            if ($previousCount > 0) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageParam'] => $this->page - 1
                ));
                $pagePh['previouslink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $nextPage = ($this->page + 1);
            $pagePh['nextlink'] = '';
            if ($nextPage <= $nbPages) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageParam'] => $this->page + 1
                ));
                $pagePh['nextlink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $pagePh = $this->cleanPlaceholders($pagePh);
            $output = $this->processElementTags($this->parseTpl($this->config['pagingTpl'], $pagePh));
        } elseif ($this->config['pagingType'] == 2) {
            // pagination type 2
            $paging2 = array();
            for ($i = 0; $i < $nbPages; ++$i) {
                $pagePh['text'] = $i + 1;
                $pagePh['separator'] = $this->config['pagingSeparator'];
                $pagePh['page'] = $i + 1;
                if ($this->page == $i + 1) {
                    $pagePh['link'] = $i + 1;
                    $pagePh = $this->cleanPlaceholders($pagePh);
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['currentPageTpl'], $pagePh));
                } else {
                    $parameters = array_merge($idParameters, $qParameters, array(
                        $this->config['pageParam'] => $pagePh['page']
                    ));
                    $pagePh['link'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                    $pagePh = $this->cleanPlaceholders($pagePh);
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['pageTpl'], $pagePh));
                }
            }
            $paging2 = @implode($this->config['pagingSeparator'], $paging2);
            $phs = $this->cleanPlaceholders(['paging2' => $paging2]);
            $output = $this->processElementTags($this->parseTpl($this->config['pagingTpl'], $phs));
        } elseif ($this->config['pagingType'] == 3) {
            // pagination type 3
            $paging3 = array();

            $previousCount = ($this->page - 1) * $this->config['perPage'];
            $previouslink = '';
            if ($previousCount > 0) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageParam'] => $this->page - 1
                ));
                $previouslink = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $nextPage = ($this->page + 1);
            $nextlink = '';
            if ($nextPage <= $nbPages) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageParam'] => $this->page + 1
                ));
                $nextlink = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $maxOuterRange = $this->config['paging3OuterRange'] + $this->config['paging3MiddleRange'];
            $middleWingRange = (int) ceil(($this->config['paging3MiddleRange'] - 1) / 2);
            $middleWingRange = $middleWingRange > 0 ? $middleWingRange : 1;

            for ($i = 1; $i <= $nbPages; ++$i) {
                $parameters = array_merge($idParameters, $qParameters);

                if ($i <= $this->config['paging3OuterRange'] ||
                    $i > ($nbPages - $this->config['paging3OuterRange'])
                ) {
                    $paging3[] = $this->_formatPaging3($i, $id, $parameters);
                } else {
                    if ($nbPages <= ($this->config['paging3OuterRange'] * 2)) {
                        continue;
                    }
                    // left splitter
                    if ($i === ($this->config['paging3OuterRange'] + 1) &&
                        $this->page >= $maxOuterRange) {
                        $paging3[] = $this->processElementTags($this->parseTpl($this->config['paging3RangeSplitterTpl']));
                    }

                    if ($i <= ($this->page + $middleWingRange) &&
                        $i >= ($this->page - $middleWingRange)) {
                        $paging3[] = $this->_formatPaging3($i, $id, $parameters);
                    }

                    // right splitter
                    if ($i === ($nbPages - $this->config['paging3OuterRange']) &&
                        $this->page <= ($nbPages - $maxOuterRange) + 1) {
                        $paging3[] = $this->processElementTags($this->parseTpl($this->config['paging3RangeSplitterTpl']));
                    }
                }
            } // for ($i = 1; $i <= $nbPages; ++$i)

            $paging3 = @implode($this->config['pagingSeparator'], $paging3);
            $phs = $this->cleanPlaceholders([
                'previouslink' => $previouslink,
                'paging3' => $paging3,
                'nextlink' => $nextlink,
                ]);
            $output = $this->processElementTags($this->parseTpl($this->config['pagingTpl'], $phs));
        }
        return $output;
    }

    private function _formatPaging3($idx, $docId, $parameters = array()) {
        $pagePh = array();
        $pagePh['text'] = $idx;
        $pagePh['separator'] = $this->config['pagingSeparator'];
        $pagePh['page'] = $idx;

        if ($this->page == $idx) {
            $pagePh['link'] = $idx;
            $pagePh = $this->cleanPlaceholders($pagePh);
            $output = $this->processElementTags($this->parseTpl($this->config['currentPageTpl'], $pagePh));
        } else {
            $parameters = array_merge($parameters, array(
                $this->config['pageParam'] => $idx
            ));
            $pagePh['link'] = $this->modx->makeUrl($docId, '', $parameters, $this->config['urlScheme']);
            $pagePh = $this->cleanPlaceholders($pagePh);
            $output = $this->processElementTags($this->parseTpl($this->config['pageTpl'], $pagePh));
        }

        return $output;
    }

    /*
     * Returns extracts with highlighted searchterms
     *
     * @access private
     * @param string $text The text from where to extract extracts
     * @param integer $nbext The number of extracts required / found
     * @param integer $extractLength The extract lenght wished
     * @param array $searchTerms The searched terms
     * @param string $tpl The template name for extract
     * @param string $ellipsis The string to use as ellipsis
     * @return string Returns extracts output
     * @tutorial this algorithm search several extracts for several search terms
     * 		if some extracts intersect then they are merged. Searchterm could be
     *      a lucene regexp expression using ? or *
     */

    private function _getExtracts($text, $nbext = 1, $extractLength = 200, $searchTerms = array(), $tpl = '', $ellipsis = '...') {

        mb_internal_encoding($this->config['charset']); // set internal encoding to UTF-8 for multi-bytes functions

        $text = trim(preg_replace('/\s+/', ' ', $this->sanitize($text)));
        $textLength = mb_strlen($text);
        if (empty($text)) {
            return '';
        }

        $trimchars = "\t\r\n -_()!~?=+/*\\,.:;\"'[]{}`&";
        $nbTerms = count($searchTerms);
        if (!$nbTerms) {
            // with an empty searchString - show as introduction the first characters of the text
            if (($extractLength > 0) && !empty($text)) {
                $offset = ($extractLength < $textLength) ? $extractLength - 1 : $textLength - 1;
                $pos = min(mb_strpos($text, ' ', $offset), mb_strpos($text, '.', $offset));
                if ($pos) {
                    $intro = rtrim(mb_substr($text, 0, $pos), $trimchars) . $ellipsis;
                } else {
                    $intro = $text;
                }
            } else {
                $intro = '';
            }
            $phs = $this->cleanPlaceholders(array('extract' => $intro));

            return $this->processElementTags($this->parseTpl($tpl, $phs));
        }

        // get extracts
        $extracts = array();
        $extractLength2 = $extractLength / 2;
        $rank = 0;

        // foreach ($searchTerms as $s) {
        //     $s = trim($s);
        //     $x = preg_split('/\s/', $s);
        //     $searchTerms = array_merge($x);
        // }

        // search the position of all search terms
        foreach ($searchTerms as $searchTerm) {
            $rank++;
            // replace lucene wildcards by regexp wildcards
            $pattern = array('#\*#', '#\?#');
            $replacement = array('\w*', '\w');
            $searchTerm = preg_replace($pattern, $replacement, $searchTerm);
            $pattern = '#' . $searchTerm . '#i';
            $matches = array();
            $nbr = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            for ($i = 0; $i < $nbr && $i < $nbext; $i++) {
                $term = $matches[0][$i][0]; // term found even with wildcard
                $wordLength = mb_strlen($term);
                $wordLength2 = $wordLength / 2;
                $wordLeft = mb_strlen(mb_substr($text, 0, $matches[0][$i][1]));
                $wordRight = $wordLeft + $wordLength - 1;
                $left = (int) ($wordLeft - $extractLength2 + $wordLength2);
                $right = $left + $extractLength - 1;
                if ($left < 0) {
                    $left = 0;
                }
                if ($right > $textLength) {
                    $right = $textLength;
                }
                $extracts[] = array(
                    'searchTerm' => $term,
                    'wordLeft' => $wordLeft,
                    'wordRight' => $wordRight,
                    'rank' => $rank,
                    'left' => $left,
                    'right' => $right,
                    'etcLeft' => $ellipsis,
                    'etcRight' => $ellipsis
                );
            }
        }

        $nbext = count($extracts);
        if ($nbext > 1) {
            for ($i = 0; $i < $nbext; $i++) {
                $lft[$i] = $extracts[$i]['left'];
                $rght[$i] = $extracts[$i]['right'];
            }
            array_multisort($lft, SORT_ASC, $rght, SORT_ASC, $extracts);

            for ($i = 0; $i < $nbext; $i++) {
                $begin = mb_substr($text, 0, $extracts[$i]['left']);
                if ($begin != '') {
                    $extracts[$i]['left'] = (int) mb_strrpos($begin, ' ');
                }

                $end = mb_substr($text, $extracts[$i]['right'] + 1, $textLength - $extracts[$i]['right']);
                if ($end != '') {
                    $dr = (int) mb_strpos($end, ' ');
                }
                if (is_int($dr)) {
                    $extracts[$i]['right']+= $dr + 1;
                }
            }

            if ($extracts[0]['left'] == 0) {
                $extracts[0]['etcLeft'] = '';
            }
            for ($i = 1; $i < $nbext; $i++) {
                if ($extracts[$i]['left'] < $extracts[$i - 1]['wordRight']) {
                    $extracts[$i - 1]['right'] = $extracts[$i - 1]['wordRight'];
                    $extracts[$i]['left'] = $extracts[$i - 1]['right'] + 1;
                    $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                } else if ($extracts[$i]['left'] < $extracts[$i - 1]['right']) {
                    $extracts[$i - 1]['right'] = $extracts[$i]['left'];
                    $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                }
            }
        }

        $output = '';
        $highlightTag = $this->config['highlightTag'];
        $highlightClass = $this->config['highlightClass'];

        for ($i = 0; $i < $nbext; $i++) {
            $extract = mb_substr($text, $extracts[$i]['left'], $extracts[$i]['right'] - $extracts[$i]['left'] + 1);
            if ($this->config['highlightResults']) {
                $rank = $extracts[$i]['rank'];
                $searchTerm = $extracts[$i]['searchTerm'];
                $extract = $this->addHighlighting($extract, (array) $searchTerm, $highlightClass, $highlightTag, $rank);
            }
            $extractPh = array(
                'extract' => $extracts[$i]['etcLeft'] . $extract . $extracts[$i]['etcRight']
            );
            $extractPh = $this->cleanPlaceholders($extractPh);
            $output .= $this->processElementTags($this->parseTpl($tpl, $extractPh));
        }

        return $output;
    }

}
