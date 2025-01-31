<?php
namespace AdvSearch\Drivers;

use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use MODX\Revolution\modTemplateVar;
use xPDO\Om\xPDOQuery;
use AdvSearch\Drivers\Base;

class MySql extends Base {

    public function getResults($asContext) {
        $results = $this->_getResults($asContext);
        if (count($results) === 0) {
            // disable fulltext
            $results = $this->_getResults($asContext, false);
        }
        return $results;
    }

    private function _getResults($asContext, $fulltext = true) {
        $c = $this->modx->newQuery('modResource');
        //=============================  add selected modResource fields (docFields)
        $c->distinct();
        $c->select($this->modx->getSelectColumns('modResource', 'modResource', '', $asContext['mainFields']));

        // multiple parents support
        if (!empty($this->config['parents'])) {
            $parents = array_map('trim', @explode(',', $this->config['parents']));
            $c->where([
                'modResource.parent:IN' => $parents
            ]);
        }
        // multiple ids support
        if (!empty($this->config['ids'])) {
            $ids = array_map('trim', @explode(',', $this->config['ids']));
            $c->where([
                'modResource.id:IN' => $ids
            ]);
        }
        // hideLinks
        if ($this->config['hideLinks']) {
            $c->where([
                ['class_key:!=' => 'MODX\\Revolution\\modSymLink'],
                ['class_key:!=' => 'MODX\\Revolution\\modWebLink'],
            ]);
        }

        //if (!empty($asContext['searchString'])) {
        if (count($asContext['searchTerms']) > 0) {
            $fulltext = false;
            if ($fulltext) {
                // fulltext searching
                $mainFields = [];
                foreach ($asContext['mainWhereFields'] as $field) {
                    $mainFields[] = 'modResource.' . $field;
                }
                $mainFields = @implode(',', $mainFields);
                $c->select([
                    $this->modx->escape('mainFields_score') => "MATCH ($mainFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)"
                ]);
                $conditions = [];
                $conditions[] = "MATCH ($mainFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)";
                $having = "mainFields_score > 0";
                //=============================  add TV where the search should occur (&withTVs parameter)
                if (!empty($asContext['tvWhereFields']) && !empty($asContext['searchString'])) {
                    $tvWhereFields = [];
                    foreach ($asContext['tvWhereFields'] as $tv) {
                        $tvWhereFields[] = '`' . $tv . '_cv`.`value`';
                    }
                    $tvWhereFields = @implode(',', $tvWhereFields);
                    $c->select([
                        $this->modx->escape('tvWhereFields_score') => "MATCH ($tvWhereFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)"
                    ]);
                    $conditions[] = "MATCH ($tvWhereFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)";
                    $having = $having . " OR tvWhereFields_score > 0";
                }
                $c->orCondition([$conditions]);
                $c->having("($having)");
            } else {
                // textlike searching
                // $searchStrings = array_map('trim', @explode(' ', $asContext['searchString']));
                $conditions = [];
                foreach ($asContext['mainWhereFields'] as $field) {
                    if ($field === 'id' || $field === 'template') {
                        continue;
                    }
                    foreach ($asContext['searchTerms'] as $string) {
                        $conditions[] = [
                            'modResource.' . $field . ':LIKE' => "%$string%"
                        ];
                    }
                }

                //=============================  add TV where the search should occur (&withTVs parameter)
                if (!empty($asContext['tvWhereFields'])) { //&& !empty($asContext['searchString'])) {
                    foreach ($asContext['tvWhereFields'] as $tv) {
                        if (!empty($asContext['searchString'])) {
                            // $searchStrings = array_map('trim', @explode(' ', $asContext['searchString']));
                            foreach ($asContext['searchTerms'] as $string) {
                                $conditions[] = [
                                    $this->modx->escape($tv . '_cv.value') . ':LIKE' => "%$string%",
                                ];
                            }
                        }
                    }
                }

                $c->orCondition([$conditions]);
            }
        }

        if (!empty($asContext['tvWhereFields'])) {
            foreach ($asContext['tvWhereFields'] as $tv) {
                $etv = $this->modx->escape($tv);
                $tvcv = $tv . '_cv';
                $etvcv = $this->modx->escape($tvcv);
                $c->leftJoin('modTemplateVar', $tv, [
                    "{$etv}.`name` = '{$tv}'"
                ]);
                $c->leftJoin('modTemplateVarResource', $tv . '_cv', [
                    "{$etvcv}.`contentid` = `modResource`.`id`",
                    "{$etvcv}.`tmplvarid` = {$etv}.`id`"
                ]);
//                $c->select("IFNULL({$etvcv}.`value`, {$etv}.`default_text`) AS {$etv}");
                $c->select([
                    $etv => "IFNULL({$etvcv}.`value`, {$etv}.`default_text`)"
                ]);
            }
        }

        // add joined resources
        $c = $this->addJoinedResources($c, $asContext);

        //============================= add pre-conditions (published, searchable, undeleted, lstIds, hideMenu, hideContainers)
        // restrict search to published, searcheable and undeleted modResource resources
        $c->andCondition(['published' => '1', 'searchable' => '1', 'deleted' => '0']);

        // hideMenu
        if ($this->config['hideMenu'] == 0) {
            $c->andCondition(['hidemenu' => '0']);
        } elseif ($this->config['hideMenu'] == 1) {
            $c->andCondition(['hidemenu' => '1']);
        } else {
            // if &hideMenu=2 or anything else, ignore hideMenu option
        }

        // hideContainers
        if ($this->config['hideContainers']) {
            $c->andCondition(['isfolder' => '0']);
        }

        // multiple context support
        $contexts = [];
        if (!empty($this->config['contexts'])) {
            $contextArray = explode(',', $this->config['contexts']);
            foreach ($contextArray as $ctx) {
                $contexts[] = $ctx;
            }
        } else {
            $contexts[] = $this->modx->context->get('key');
        }
        $c->where([
            'modResource.context_key:IN' => $contexts
        ]);

        //============================= add query conditions
        if (!empty($asContext['queryHook']['andConditions'])) {
            $c->andCondition($asContext['queryHook']['andConditions']);
        }

        if (!empty($asContext['queryHook']['orConditions'])) {
            $c->orCondition($asContext['queryHook']['orConditions']);
        }

        //=============================  add an orderby clause for selected fields
        if (!empty($asContext['sortby'])) {
            foreach ($asContext['sortby'] as $field => $dir) {
                $classFieldX = array_map('trim', explode('.', $field));
                foreach ($classFieldX as $k => $v) {
                    $classFieldX[$k] = $this->modx->escape($v);
                }
                $field = @implode('.', $classFieldX);
                $c->sortby($field, $dir);
            }
        }

        if (empty($asContext['queryHook']['stmt'])) {
            // debug mysql query
            $this->ifDebug('SearchString: ' . $asContext['searchString'], __METHOD__, __FILE__, __LINE__);
            if ($fulltext) {
                $this->ifDebug('FULLTEXT Query : ');
            } else {
                $this->ifDebug('LIKE Query : ');
            }
            $this->ifDebug('Select before pagination: ' . $this->niceQuery($c), __METHOD__, __FILE__, __LINE__);

            // get number of results before pagination
            $this->resultsCount = $this->getQueryCount('modResource', $c);
            $this->ifDebug('Number of results before pagination ' . ($fulltext ? '(FULLTEXT) : ' : '(LIKE) : ') . $this->resultsCount, __METHOD__, __FILE__, __LINE__);

            if ($this->resultsCount > 0) {
                $this->setPage($asContext['page']);
                $c->limit($this->config['perPage'], ($asContext['page'] - 1) * $this->config['perPage']);
                // debug mysql query
                if ($fulltext) {
                    $this->ifDebug('FULLTEXT Query : ');
                } else {
                    $this->ifDebug('LIKE Query : ');
                }
                $this->ifDebug('Final select after pagination: ' . $this->niceQuery($c), __METHOD__, __FILE__, __LINE__);

                //============================= get results
                $collection = $this->modx->getCollection(modResource::class, $c);

                //============================= append & render tv fields (includeTVs, withTVs)
                $this->results = $this->_appendTVsFields($collection, $asContext);
                foreach ($this->results as $result) {
                    $this->idResults[] = $result['id'];
                }
            }
        } else {
            // run a new statement
            //============================= get results, append & render tv fields (includeTVs, withTVs)
            $this->results = $this->_runStmt($c, $asContext);
            // get number of results before pagination
            $this->resultsCount = $this->_countStmt($c, $asContext);
            $this->ifDebug('Number of results before pagination: ' . $this->resultsCount, __METHOD__, __FILE__, __LINE__);

            //============================= set a subset (offset, perPage)
            if (empty($this->config['postHook'])) {
                $this->results = array_slice($this->results, ($asContext['page'] - 1) * $this->config['perPage'], $this->config['perPage']);
            }

            //============================= prepare final results
            $this->results = $this->_prepareResults($this->results);
        }

        return $this->results;
    }

    private function _countStmt(xPDOQuery $c, $asContext) {
        return count($this->results);
    }

    /**
     * Run a statement & append rendered tv fields (includeTVs, withTVs)
     *
     * @access private
     * @return array Returns an array of results
     */
    private function _runStmt(xPDOQuery $c, $asContext) {
        $results = [];
        $allowedTvNames = array_merge($asContext['tvWhereFields'], $asContext['tvFields']);
        $c->prepare();
        $sql = $c->toSQL();
        $patterns = ['{sql}'];
        $replacements = [$sql];
        $sql = str_replace($patterns, $replacements, $asContext['queryHook']['stmt']['execute']);
        $this->ifDebug('sql: ' . $sql, __METHOD__, __FILE__, __LINE__);
        unset($c);
        $c = new xPDOCriteria($this->modx, $sql);
        if (!empty($asContext['queryHook']['stmt']['prepare'])) {
            $c->bind($asContext['queryHook']['stmt']['prepare']);
        }
        $c->prepare();
        if ($c->stmt) {
            $exec = $c->stmt->execute();
            if ($exec) {
                if (count($allowedTvNames)) {
                    while ($row = $c->stmt->fetch(\PDO::FETCH_ASSOC)) {
                        // Append & render tv fields (includeTVs, withTVs)
                        $tvs = [];
                        $templateVars = $this->modx->getCollection(modTemplateVar::class, ['name:IN' => $allowedTvNames]);
                        foreach ($templateVars as $tv) {
                            $tvs[$tv->get('name')] = $tv->renderOutput($row['id']);
                        }
                        $results[] = array_merge($row, $tvs);
                    }
                } else {
                    $results = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                }
                $c->stmt->closeCursor();
            }
        }
        unset($c);
        $this->ifDebug('Results of _runStmt: ' . print_r($results, true), __METHOD__, __FILE__, __LINE__);

        return $results;
    }

    /**
     * Prepare results
     *
     * @access private
     * @param array $results array of results
     * @return xPDOQuery Returns the modified query
     */
    private function _prepareResults($results) {
        // return search results as an associative array with id as key
        $searchResults = [];
        foreach ($results as $result) {
            $index = 'as' . $result['id'];
            $searchResults[$index] = $result;
            $this->idResults[] = $result['id'];
        }

        // set lstIdResults
        $lstIdResults = implode(',', $this->idResults);

        $this->ifDebug('lstIdsResults: ' . $lstIdResults, __METHOD__, __FILE__, __LINE__);

        return $searchResults;
    }

    /**
     * Append & render tv fields (includeTVs, withTVs)
     *
     * @access private
     * @param array of xPDOObjects $collection collection of search results
     * @return array Returns an array of results
     */
    private function _appendTVsFields($collection, $asContext) {
        $results = [];
        $displayedFields = array_unique(array_merge($asContext['mainFields'], $asContext['joinedFields']));
        $allowedTvNames = $asContext['tvFields']; //array_unique(array_merge($asContext['tvWhereFields'], $asContext['tvFields']));

        if (count($allowedTvNames)) {
            foreach ($collection as $resourceId => $resource) {
                $result = $resource->get($displayedFields);
                $tvs = [];
                $templateVars = $this->modx->getCollection(modTemplateVar::class, ['name:IN' => $allowedTvNames]);
                foreach ($templateVars as $tv) {
                    $tvs[$tv->get('name')] = $tv->renderOutput($result['id']);
                }
                $results[] = array_merge($result, $tvs);
            }
        } else {
            foreach ($collection as $resourceId => $resource) {
                $results[] = $resource->get($displayedFields);
            }
        }

        return $results;
    }

}
