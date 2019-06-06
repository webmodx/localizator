<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('localizator_core_path', null,
                    $modx->getOption('core_path') . 'components/localizator/') . 'model/';
            $modx->addPackage('localizator', $modelPath);

            $keys = array();
            $c = $modx->newQuery('localizatorLanguage')->select('key');
            if ($c->prepare() && $c->stmt->execute()) {
                $keys = $c->stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            /** @var modAccessPolicy $policy */
            if ($policy = $modx->getObject('modAccessPolicy', array('name' => 'LocalizatorManagerPolicy'))) {
                $modx->log(xPDO::LOG_LEVEL_INFO, '[Localizator] Found LocalizatorManagerPolicy Access Policy!');

                if ($template = $modx->getObject('modAccessPolicyTemplate',
                    array('name' => 'LocalizatorManagerPolicyTemplate'))
                ) {
                    $modx->log(xPDO::LOG_LEVEL_INFO, '[Localizator] Found LocalizatorManagerPolicyTemplate Template!');
                    $modx->loadClass('localizatorLanguage');
                    $data = $policy->get('data');
                    foreach ($keys as $key){
                        foreach (localizatorLanguage::$permissions as $tmp){
                            if (!$permission = $modx->getObject('modAccessPermission', array('name' => "localizatorcontent_{$tmp}_{$key}"))){
                                $permission = $modx->newObject('modAccessPermission');
                                $permission->fromArray(array(
                                    'template' => $template->get('id'),
                                    'name' => "localizatorcontent_{$tmp}_{$key}",
                                    'description' => "localizatorcontent_{$tmp}",
                                    'value' => 1,
                                ));
                                $permission->save();
                            }

                            if (!isset($data["localizatorcontent_{$tmp}_{$key}"])){
                                $data["localizatorcontent_{$tmp}_{$key}"] = true;
                            }
                        }
                    }
                    $policy->set('data', $data);

                    $policy->set('template', $template->get('id'));
                    $policy->save();
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR,
                        '[Localizator] Could not find LocalizatorManagerPolicyTemplate Access Policy Template!');
                }
                /** @var modUserGroup $adminGroup */
                if ($adminGroup = $modx->getObject('modUserGroup', array('name' => 'Administrator'))) {
                    $properties = array(
                        'target' => 'mgr',
                        'principal_class' => 'modUserGroup',
                        'principal' => $adminGroup->get('id'),
                        'authority' => 9999,
                        'policy' => $policy->get('id'),
                    );
                    if (!$modx->getObject('modAccessContext', $properties)) {
                        $access = $modx->newObject('modAccessContext');
                        $access->fromArray($properties);
                        $access->save();
                    }
                }
                break;
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[Localizator] Could not find LocalizatorManagerPolicy Access Policy!');
            }
            break;
    }
}
return true;