<?php

class localizatorLanguageUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'localizatorLanguage';
    public $classKey = 'localizatorLanguage';
    public $languageTopics = array('localizator');
    //public $permission = 'save';


    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return bool|string
     */
    public function beforeSave()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $id = (int)$this->getProperty('id');
        if (empty($id)) {
            return $this->modx->lexicon('localizator_item_err_ns');
        }

       $key = trim($this->getProperty('key'));
        if (empty(key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => key))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_ae'));
        }

		$http_host = trim($this->getProperty('http_host'));
        if (empty(http_host)) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_item_err_http_host'));
        } elseif ($this->modx->getCount($this->classKey, array('http_host' => http_host))) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_item_err_ae'));
        }

        return parent::beforeSet();
    }
}

return 'localizatorLanguageUpdateProcessor';
