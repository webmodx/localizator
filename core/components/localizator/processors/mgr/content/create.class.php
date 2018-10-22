<?php

ini_set("display_errors",1);
error_reporting(E_ALL);
class localizatorContentCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator');
    //public $permission = 'create';

    function __construct(modX & $modx,array $properties = array()) {
        parent::__construct($modx, $properties);
        $data = $this->getProperties();
        foreach ($data as $key => $value){
            if (strpos($key, 'tvlocalizator_') !== false){
                $this->setProperty(substr($key, 14), $value);
                $this->unsetProperty($key);
            }
        }
    }
    /**
     * @return bool
     */
    public function beforeSet()
    {
        $key = trim($this->getProperty('key'));
        $resource_id = $this->getProperty('resource_id');
        if (empty($key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'resource_id' => $resource_id))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_ae'));
        }

        return parent::beforeSet();
    }

}

return 'localizatorContentCreateProcessor';