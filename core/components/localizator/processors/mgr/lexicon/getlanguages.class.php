<?php

class localizatorLexiconGetLanguagesProcessor extends modProcessor {

    public function process() {
		$list = array();
		$languages = $this->modx->getIterator('localizatorLanguage');
		foreach($languages as $language) {
			$list[$language->cultureKey] = array(
				'name' => ($language->cultureKey ?: $language->key),
			);
		}

		return $this->outputArray(array_values($list), count($list));
    }

}

return 'localizatorLexiconGetLanguagesProcessor';