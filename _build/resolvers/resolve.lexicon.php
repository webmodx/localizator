<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $path = MODX_CORE_PATH . 'components/localizator/lexicon/';
            if (file_exists($path)) {
                foreach (array_diff(scandir($path), array('..', '.')) as $dir){
                    if (!is_dir($path . $dir))
                        continue;

                    if (file_exists($path . $dir . '/site.inc.php'))
                        continue;

                    file_put_contents($path . $dir . '/site.inc.php', "<?php\n\n\$_lang['test'] = 'Test';");
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:

            break;

    }
}
return true;