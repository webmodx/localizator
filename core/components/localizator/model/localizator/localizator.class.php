<?php

class localizator
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('localizator_core_path', $config,
            $this->modx->getOption('core_path') . 'components/localizator/'
        );
        $assetsUrl = $this->modx->getOption('localizator_assets_url', $config,
            $this->modx->getOption('assets_url') . 'components/localizator/'
        );
        $connectorUrl = $assetsUrl . 'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
        ), $config);

        $this->modx->addPackage('localizator', $this->config['modelPath']);
        $this->modx->lexicon->load('localizator:default');
    }

	// prepare text for curl request
	function translator_prepare($text, $limit = 2000) {
	    if ($limit > 0) {
	        $ret = array();
	        $limiten = mb_strlen($text, "UTF-8");
	        for ($i = 0; $i < $limiten; $i += $limit) {
	            $ret[] = mb_substr($text, $i, $limit, "UTF-8");
	        }
	        return $ret;
	    }
	    return preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY);
	}

	// https://tech.yandex.ru/translate/doc/dg/concepts/About-docpage/
	function translator_Yandex($text, $from, $to) {
		if(!$text) return;
		$output = '';
		$data = array(
			'key' => $this->modx->getOption('localizator_key_yandex'),
		    'lang' => $from . '-' . $to,
		    'format' => 'html',
		);

		$text = $this->translator_prepare($text);
		foreach($text as $part) {
			$data['text'] = $part;
			$ch = curl_init('https://translate.yandex.net/api/v1.5/tr.json/translate');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data,'','&'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			$response = json_decode($response, true);
			if($response['code'] == 200) {
				$output .= implode('', $response['text']);
			} else {
				$this->modx->log(1, 'localizator: yandex error - ' . $response['code'] .', see https://tech.yandex.ru/translate/doc/dg/reference/translate-docpage/');
			}
		}

		return $output;
	}



}