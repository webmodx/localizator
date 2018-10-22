<?php

$properties = array();

$tmp = array(
    'element' => array(
        'type' => 'textfield',
        'value' => 'pdoResources',
    ),
    'class' => array(
        'type' => 'textfield',
        'value' => 'modResource',
    ),
);

foreach ($tmp as $k => $v) {
    $properties[] = array_merge(
        array(
            'name' => $k,
            'desc' => PKG_NAME_LOWER . '_prop_' . $k,
            'lexicon' => PKG_NAME_LOWER . ':properties',
        ), $v
    );
}

return $properties;