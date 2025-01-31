<?php
namespace AdvSearch\Drivers;

use MODX\Revolution\modX;
use AdvSearch\Drivers\Base;

class Custom extends Base {

    public function getResults($asContext) {
        if (empty($asContext['queryHook'])) {
            $msg = 'Missing query hook for engine: "Custom"';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
        $main = $asContext['queryHook']['main'];
        $this->mainClass = $main['class'];  // main class

        if (!class_exists($this->mainClass)) {
            $main['packagePath'] = $this->replacePropPhs($main['packagePath']);
            $tablePrefix = isset($main['tablePrefix']) ? $main['tablePrefix'] : '';
            $this->modx->addPackage($main['package'], $main['packagePath'], $tablePrefix); // add package
        }

        $this->primaryKey = $this->modx->getPK($this->mainClass); // get primary key

        $shortMainClass = end(explode('\\', $this->mainClass));

        // set query from main package
        $c = $this->modx->newQuery($this->mainClass);
        // add joined resources
        $c = $this->addJoinedResources($c, $asContext);
        $fields = array_merge((array) $asContext['mainFields'], (array)$asContext['joinedFields']);
        if (!in_array('id', $fields)) {
            $fields = array_merge(['id'], $fields);
        }
        // initialize and add main displayed fields
        $c->distinct();
        $c->select($this->modx->getSelectColumns($this->mainClass, $shortMainClass, '', $fields));

        // restrict search to specific keys ($lstIds)
        if (!empty($this->config['ids'])) {
            $c->andCondition(["{$this->mainClass}.{$this->primaryKey} IN (" . $this->config['ids'] . ")"]);
        }

        // restrict search with where condition
        if (!empty($main['where'])) {
            if (!is_array($main['where'])) {
                $c->andCondition([$main['where']]);
            } else {
                $c->andCondition($main['where']);
            }
        }

        //============================= add query conditions
        if (!empty($asContext['queryHook']['andConditions'])) {
            $c->andCondition($asContext['queryHook']['andConditions']);
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

        // debug mysql query
        if ($this->debug) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'SearchString: ' . $this->searchString, '', '_customSearch');
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Select before pagination: ' . $this->niceQuery($c), '', '_customSearch');
        }

        // get number of results before pagination
        $this->resultsCount = $this->getQueryCount($this->mainClass, $c);
        if ($this->debug) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Number of results before pagination: ' . $this->resultsCount, '', '_customSearch');
        }

        if ($this->resultsCount > 0) {
            //============================= add query limits
            $limit = $this->config['perPage'];
            $c->limit($limit, $this->offset);

            // debug mysql query
            if ($this->debug) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Final select: ' . $this->niceQuery($c), '', '_customSearch');
            }

            //============================= get results
            $collection = $this->modx->getCollection($this->mainClass, $c);
            if (!empty($collection)) {
                foreach ($collection as $resource) {
                    $pkValue = $resource->get($this->primaryKey);
                    $this->results["{$pkValue}"] = $resource->toArray('', false, true);
                    $this->idResults[] = $pkValue;
                }
            }
        }

        if ($this->debug) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "lstIdsResults:" . @implode(',', $this->idResults), '', '_customSearch');
        }

        return $this->results;
    }

}