<?php

class localizatorContentRemoveProcessor extends modObjectProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator');
    public $permission = '';

    protected $loc_permission;

    /**
     * @return array|string
     */
    public function process()
    {
        $this->loc_permission = $this->modx->getOption('localizator_check_permissions', null, false, true);

        $ids = $this->modx->fromJSON($this->getProperty('ids'));
        if (empty($ids)) {
            return $this->failure($this->modx->lexicon('localizator_item_err_ns'));
        }

        foreach ($ids as $id) {
            /** @var localizatorContent $object */
            if (!$object = $this->modx->getObject($this->classKey, $id)) {
                return $this->failure($this->modx->lexicon('localizator_item_err_nf'));
            }

            if ($this->loc_permission && 
                !$this->modx->hasPermission("localizatorcontent_save_" . $object->key)
            ){
                return $this->failure($this->modx->lexicon('access_denied'));
            }

            $object->remove();
        }

        return $this->success();
    }

}

return 'localizatorContentRemoveProcessor';