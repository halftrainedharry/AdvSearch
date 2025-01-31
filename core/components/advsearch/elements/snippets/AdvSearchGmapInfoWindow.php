<?php
use MODX\Revolution\modX;
use MODX\Revolution\modResource;

$gets = $modx->sanitize($_GET);
$withTVs = $modx->getOption('withTVs', $scriptProperties);
$tpl = $modx->getOption('tpl', $scriptProperties);
if (!isset($gets['urlID']) || empty($gets['urlID']) || empty($tpl)) {
    return;
}
$output = '';

$c = $modx->newQuery(modResource::class);
$c->select([
    'modResource.*',
]);
if (!empty($withTVs)) {
    $withTVsXpld = array_map('trim', @explode(',', $withTVs));
    foreach ($withTVsXpld as $tv) {
        $etv = $modx->escape($tv);
        $tvcv = $tv . '_cv';
        $etvcv = $modx->escape($tvcv);
        $c->leftJoin('modTemplateVar', $tv, [
            "{$etv}.`name` = '{$tv}'"
        ]);
        $c->leftJoin('modTemplateVarResource', $tv . '_cv', [
            "{$etvcv}.`contentid` = `modResource`.`id`",
            "{$etvcv}.`tmplvarid` = {$etv}.`id`"
        ]);
        $c->select("IFNULL({$etvcv}.`value`, {$etv}.`default_text`) AS {$etv}");
    }
}
$c->where([
    'modResource.id' => $gets['urlID']
]);

$resource = $modx->getObject(modResource::class, $c);
if (!$resource) {
    $output = 'No info';
    $c->prepare();
    $sql = $c->toSQL();
    $modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearchGmapInfoWindow] ' . $output . ' :' . $sql);
    return $output;
}
$phs = $resource->toArray();
$output = $modx->getChunk($tpl, $phs);
return $output;