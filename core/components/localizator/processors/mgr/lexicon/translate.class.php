<?php

class localizatorLexiconTranslateProcessor extends modProcessor {

    public function process() {
		$this->localizator = $this->modx->getService('localizator');

		if (!$default_language = $this->modx->getOption('localizator_default_language')) {
			return $this->failure('Не указана опция localizator_default_language, невозможно определить исходный языка для перевода');
		}

		$languages = array();
		$_languages = $this->modx->getIterator('localizatorLanguage');
		foreach($_languages as $language) {
			$key = $language->cultureKey ?: $language->key;
			if($key != $default_language) {
				$languages[] = $key;
			}
		}


        $time = time();
        $time_limit = @ini_get('max_execution_time') - 20;
        if ($time_limit <= 5) {
            $time_limit = 5;
        }
        $start = $this->getProperty('start', 0);


        $c = $this->modx->newQuery('modLexiconEntry');
        if ($start == 0) {
            //$this->cleanTables();
        } else {
            $c->limit(1000000, $start);
        }
		$c->where(array(
			'namespace' => 'localizator',
			'topic' => 'site',
			'language' => $default_language
		));

		$entries = $this->modx->getIterator('modLexiconEntry', $c);
		foreach ($entries as $entry) {
			foreach($languages as $language) {
				$tmp = $this->modx->getObject('modLexiconEntry', array(
					'namespace' => 'localizator',
					'topic' => 'site',
					'language' => $language,
					'name' => $entry->name,
				));

				if(!$tmp) {
					$tmp = $this->modx->newObject('modLexiconEntry');
					$tmp->fromArray(array(
						'namespace' => 'localizator',
						'topic' => 'site',
						'language' => $language,
						'name' => $entry->name,
					));
				}

				$translation = $this->localizator->translator_Yandex($entry->value, $default_language, $language);
				if(!$translation) continue;

				$tmp->set('value', $translation);
				$tmp->save();
			}

			$start++;
			if ((time() - $time) >= $time_limit) {
                return $this->cleanup($start);
            }
		}

		return $this->cleanup($start);
    }


    public function cleanup($processed = 0)
    {
		$default_language = $this->modx->getOption('localizator_default_language');
		$c = $this->modx->newQuery('modLexiconEntry');
		$c->where(array('language' => $default_language));
		$total = $this->modx->getCount('modLexiconEntry', $c);

        return $this->success('', array(
            'total' => $total,
            'processed' => $processed,
        ));
    }

}

return 'localizatorLexiconTranslateProcessor';