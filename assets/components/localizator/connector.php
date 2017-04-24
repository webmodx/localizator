<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
}
else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var localizator $localizator */
$localizator = $modx->getService('localizator', 'localizator', $modx->getOption('localizator_core_path', null,
        $modx->getOption('core_path') . 'components/localizator/') . 'model/localizator/'
);
$modx->lexicon->load('localizator:default');

// handle request
$corePath = $modx->getOption('localizator_core_path', null, $modx->getOption('core_path') . 'components/localizator/');
$path = $modx->getOption('processorsPath', $localizator->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));