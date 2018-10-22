<?php

class localizatorContentUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator');
    //public $permission = 'save';

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
            return $this->modx->lexicon('localizator_error_no_id');
        }

		$key = trim($this->getProperty('key'));
        $resource_id = $this->getProperty('resource_id');
        if (empty($key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'resource_id' => $resource_id, 'id:!=' => $id))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_item_err_ae'));
        }


        return parent::beforeSet();
    }
}

return 'localizatorContentUpdateProcessor';
