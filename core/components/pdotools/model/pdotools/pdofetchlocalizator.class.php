<?php

if (!class_exists('pdoFetch')) {
    require_once dirname(__FILE__) . 'pdofetch.class.php';
}

class pdoFetchLocalizator extends pdoFetch
{
	public function addTVs()
    {
        parent::addTVs();

        if ($this->config['localizatorTVs'] && $this->config['tvsJoin']){
        	foreach ($this->config['localizatorTVs'] as $name){
                $name = strtolower($name);
                $alias = 'TV' . $name;
        		if (isset($this->config['tvsJoin'][$name])){
        			$this->config['tvsJoin'][$name]['class'] = 'locTemplateVarResource';
        			$this->config['tvsJoin'][$name]['on'] .= " AND `{$alias}`.`key` = '{$this->config['localizator_key']}'";
        		}
        	}
        }
    }
}