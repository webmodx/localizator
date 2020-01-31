<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $corePath = $modx->getOption('localizator_core_path', null,
                $modx->getOption('core_path') . 'components/localizator/');
            $modelPath = $corePath . 'model/';
            $modx->addPackage('localizator', $modelPath);

            $lexiconPath = $corePath . 'lexicon/';

            if (file_exists($lexiconPath)) {

                $q = $modx->newQuery('localizatorLanguage')
                    ->groupby('cultureKey')
                    ->select('cultureKey');

                if ($q->prepare() && $q->stmt->execute()){
                    while ($dir = $q->stmt->fetchColumn()){
                        if (!file_exists($lexiconPath . $dir)){
                            mkdir($lexiconPath . $dir, 0755, true);
                        }
                    }
                }

                foreach (array_diff(scandir($lexiconPath), array('..', '.')) as $dir){
                    if (!is_dir($lexiconPath . $dir))
                        continue;

                    if (file_exists($lexiconPath . $dir . '/site.inc.php'))
                        continue;

                    file_put_contents($lexiconPath . $dir . '/site.inc.php', "<?php\n\n\$_lang['test'] = 'Test {$dir}';");
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:

            break;

    }
}
return true;