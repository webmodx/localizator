<?php
class localizatorContent extends xPDOSimpleObject {

    protected $tvs = null;
    protected $TVKeys = null;
 
    public static function getTemplateVarCollection(localizatorContent &$content, $toArray = false) 
    {
        $c = $content->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$content));

        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns('modTemplateVar', 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns('modTemplateVarTemplate', 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
                '0 AS resourceId'
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
                $content->get('resource_id').' AS resourceId'
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin('locTemplateVarResource','tvc',array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank,modTemplateVar.rank');

        if ($toArray){
            $c->leftJoin('modCategory', 'Category', 'Category.id=modTemplateVar.category');
            $c->select(array(
                'IF(ISNULL(Category.id),0,Category.id) AS category_id, Category.category AS category_name',
            ));
            $data = array();
            if ($c->prepare() && $c->stmt->execute()) {
                while ($tv = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[$tv['id']] = $tv;
                }
            }
            return $data;
        }
        else{
            return $content->xpdo->getCollection('modTemplateVar', $c);
        }
    }



    public function getTemplateVars($toArray = false) 
    {
        return $this->xpdo->call('localizatorContent', 'getTemplateVarCollection', array(&$this, $toArray));
    }


    /**
     * @return xPDOQuery
     */
    public static function prepareTVListCriteria(localizatorContent &$content)
    {
        $resource = $content->getOne('Resource');
        $c = $content->xpdo->newQuery('modTemplateVar');
        $c->innerJoin('modTemplateVarTemplate','tvtpl',array(
            'tvtpl.tmplvarid = modTemplateVar.id',
            'tvtpl.templateid' => $resource->get('template'),
        ));
        $c->groupby('modTemplateVar.id');

        if ($fields = $content->xpdo->getOption('localizator_tv_fields', null, false, true)) {
            $fields = array_map('trim', explode(',', $fields));
            if (!empty($fields)){
                $c->where([
                    'modTemplateVar.name:IN' => $fields,
                ]);
            }
        }

        return $c;
    }


    /**
     * @return array
     */
    public static function _loadTVs(localizatorContent &$content)
    {
        $c = $content->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$content));

        $resource = $content->getOne('Resource');
        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns('modTemplateVar', 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns('modTemplateVarTemplate', 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin('locTemplateVarResource','tvc',array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank,modTemplateVar.rank');

        $data = array();
        if ($c->prepare() && $c->stmt->execute()) {
            while ($tv = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[$tv['name']] = $tv['value'];
            }
        }

        return $data;
    }

    public function loadTVs()
    {
        if ($this->tvs === null) {
            $this->tvs = $this->xpdo->call('localizatorContent', '_loadTVs', array(&$this));
        }
        return $this->tvs;
    }

    /**
     * @param bool $force
     *
     * @return array
     */
    public function getTVKeys($force = false)
    {
        if ($this->TVKeys === null || $force) {
            /** @var xPDOQuery $c */
            $c = $this->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$this));
            $c->select('modTemplateVar.id,modTemplateVar.name');

            $this->TVKeys = array();
            if ($c->prepare() && $c->stmt->execute()){
                while ($tv = $c->stmt->fetch(PDO::FETCH_ASSOC)){
                    $this->TVKeys[$tv['id']] = $tv['name'];
                }
            }
        }

        return $this->TVKeys;
    }

    /**
     * @param array|string $k
     * @param null $format
     * @param null $formatTemplate
     *
     * @return array|mixed|null|xPDOObject
     */
    public function get($k, $format = null, $formatTemplate = null)
    {

        if (is_array($k)) {
            $array = array();
            foreach ($k as $v) {
                $array[$v] = isset($this->_fieldMeta[$v])
                    ? parent::get($v, $format, $formatTemplate)
                    : $this->get($v, $format, $formatTemplate);
            }

            return $array;
        } elseif (isset($this->_fieldMeta[$k])) {
            return parent::get($k, $format, $formatTemplate);
        } elseif (in_array($k, $this->getTVKeys())) {
            if (isset($this->$k)) {
                return $this->$k;
            }
            $this->loadTVs();
            $value = isset($this->tvs[$k])
                ? $this->tvs[$k]
                : null;

            return $value;
        } else {
            return parent::get($k, $format, $formatTemplate);
        }
    }

    /**
     * All json fields of product are synchronized with msProduct Options
     *
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        $save = parent::save($cacheFlag);
        $this->saveProductTVs();

        return $save;
    }

    public function toArray($keyPrefix = '', $rawValues = false, $excludeLazy = false, $includeRelated = false)
    {
        $original = parent::toArray($keyPrefix, $rawValues, $excludeLazy, $includeRelated);
        $additional = $this->loadTVs();
        $intersect = array_keys(array_intersect_key($original, $additional));
        foreach ($intersect as $key) {
            unset($additional[$key]);
        }

        return array_merge($original, $additional);
    }

    /**
     *
     */
    protected function saveProductTVs()
    {
        foreach ($this->getTVKeys() as $tvid => $tvname){
            if (!$loctv = $this->xpdo->getObject('locTemplateVarResource', [
                    'key' => $this->get('key'), 
                    'contentid' => $this->get('resource_id'), 
                    'tmplvarid' => $tvid,
                ]
            )){
                $loctv = $this->xpdo->newObject('locTemplateVarResource');
                $loctv->fromArray([
                    'key' => $this->get('key'),
                    'contentid' => $this->get('resource_id'),
                    'tmplvarid' => $tvid,
                ]);
            }
            $loctv->set('value', $this->get($tvname));
            $loctv->save();
        }
    }

}
