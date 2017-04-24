<?php

class localizatorContentCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator');
    //public $permission = 'create';


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