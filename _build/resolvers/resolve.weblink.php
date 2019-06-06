<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            break;
        case xPDOTransport::ACTION_UPGRADE:
            $upd = $modx->prepare("UPDATE ".$modx->getTableName('localizatorContent')." SET `content` = ? WHERE `resource_id` = ?");
            $q = $modx->newQuery('modResource')
                ->innerJoin('localizatorContent','localizatorContent','localizatorContent.resource_id = modResource.id')
                ->where(array(
                    'modResource.class_key:IN' => array('modStaticResource', 'modSymLink', 'modWebLink')
                ))
                ->select('modResource.id,modResource.content');
            if ($q->prepare() && $q->stmt->execute()) {
                while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)){
                    $upd->execute(array($row['content'], $row['id']));
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;