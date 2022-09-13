<?php
use xPDO\xPDO;
use AdvSearch\AdvSearchForm;
/**
 * AdvSearchForm
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearchForm to display a filter & search form
 *
 * @category    Third Party Component
 * @since       1.0.0 pl
 * @version     dev
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 *
 * @author      Coroico <coroico@wangba.fr>
 *              goldsky <goldsky@virtudraft.com>
 * @date        23/11/2013
 *
 * -----------------------------------------------------------------------------
 */
try {
    $searchForm = new AdvSearchForm($modx, $scriptProperties);
    $output = $searchForm->output();
    return $output;
}
catch (\Exception $e) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());
}
return '';