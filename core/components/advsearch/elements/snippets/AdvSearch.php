<?php
use xPDO\xPDO;
use AdvSearch\AdvSearchRequest;
/**
 * AdvSearch
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearch to display search results on a landing page
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
    $searchRequest = new AdvSearchRequest($modx, $scriptProperties);
    $output = $searchRequest->output();
    return $output;
}
catch (\Exception $e) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());
}
return '';