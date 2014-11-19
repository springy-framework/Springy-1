<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *  \copyright Copyright (c) 2007-2014 Fernando Val
 *
 *  \brief		Classe driver de tratamento de templates utilizando Smarty como mecanismo de renderização
 *  \see		http://www.smarty.net/
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.0.0
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \ingroup	framework
 */

namespace FW\Template;

use FW\Template_Static;
use FW\Configuration;
use FW\URI;

/**
 *  \brief Classe driver de tratamento de templates utilizando Smarty como mecanismo
 *
 *  \note Esta classe é um driver para a classe FW\Template e utiliza internamente a classe Smarty.
 *        Não utilize a classe Smarty diretamente.
 *        Não utilize esta classe diretamente em sua aplicação.
 *        Instancie a classe Template em sua aplicação.
 */
class SmartyDriver implements TemplateDriverInterface
{
	const TPL_NAME_SUFIX = '.tpl.html';
	
	private $tplObj = NULL;

	private $templateName = NULL;

	private $templateCacheId = NULL;
	private $templateCompileId = NULL;

	private $templateVars = array();
	private $templateFuncs = array();

	/**
	 *	\brief Inicializa a classe de template
	 */
	public function __construct($tpl=NULL)
	{
		$this->tplObj = new \Smarty;

		$this->tplObj->use_sub_dirs = Configuration::get('template', 'use_sub_dirs');

		$this->setCacheDir( Configuration::get('template', 'template_cached_path') );

		$this->setTemplateDir( Configuration::get('template', 'template_path') );
		$this->setCompileDir( Configuration::get('template', 'compiled_template_path') );
		$this->setConfigDir( Configuration::get('template', 'template_config_path') );

		if ($tpl) {
			$this->setTemplate($tpl);
		}

		// Iniciliza as variáveis com URLs padrão de template
		if (Configuration::get('uri', 'common_urls')) {
			if (!Configuration::get('uri', 'register_method_set_common_urls')) {
				foreach(Configuration::get('uri', 'common_urls') as $var => $value) {
					if (isset($value[2])) {
						$this->assign($var, URI::buildURL($value[0], $value[1], $value[2]));
					} else if (isset($value[1])) {
						$this->assign($var, URI::buildURL($value[0], $value[1]));
					} else {
						$this->assign($var, URI::buildURL($value[0]));
					}
				}
			} else if (Configuration::get('uri', 'register_method_set_common_urls')) {
				$toCall = Configuration::get('uri', 'register_method_set_common_urls');
				if ($toCall['static']) {
					if (!isset($toCall['method'])) {
						throw new Exception('You need to determine which method will be executed.', 500);
					}

					//$toCall['class']::$toCall['method'];
				} else {
					$obj = new $toCall['class'];
					if (isset($toCall['method']) && $toCall['method']) {
						$obj->$toCall['method'];
					}
				}
			}
		}

		return true;
	}

	/**
	 *  \brief Destrói o objeto
	 */
	public function __destruct()
	{
		unset($this->tplObj);
	}

	/**
	 *	\brief Define o local dos arquivos de template
	 */
	public function setTemplateDir($path)
	{
		$this->tplObj->setTemplateDir($path);
	}

	/**
	 *	\brief Define o local dos arquivos de template compilados
	 */
	public function setCompileDir($path)
	{
		$this->tplObj->setCompileDir($path);
	}

	/**
	 *	\brief Define o local dos arquivos .conf usados nas tpls
	 */
	public function setConfigDir($path)
	{
		$this->tplObj->setConfigDir($path);
	}

	/**
	 *	\brief Define o local dos arquivos de template cacheados
	 */
	public function setCacheDir($path)
	{
		$this->tplObj->setCacheDir($path);
	}

	/**
	 *	\brief Verifica o template ideal de acordo com a página
	 */
	private function setAutoTemplatePaths()
	{
		// Se o nome do template não foi informado, define como sendo a página atual
		if ($this->templateName === NULL) {
			// Pega o caminha relativo da página atual
			$relative_path_page = URI::relativePathPage(TRUE);

			$this->templateName = URI::getControllerClass();

			// Monta o caminho do diretório do arquivo de template
			$path = Configuration::get('template', 'template_path') . (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;

			// Verifica se existe o diretório e dentro dele um template com o nome da página e
			// havendo, usa como caminho relativo adicionao. Se não houver, limpa o caminho relativo.
			if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . $this->templateName . self::TPL_NAME_SUFIX)) {
				$relative_path = (empty($relative_path_page) ? '' : DIRECTORY_SEPARATOR) . $relative_path_page;
			} else {
				$relative_path = '';
			}

			// Ajusta os caminhos de template
			$this->setTemplateDir( Configuration::get('template', 'template_path') . $relative_path);
			$this->setCompileDir( Configuration::get('template', 'compiled_template_path') . $relative_path);
			$this->setConfigDir( Configuration::get('template', 'template_config_path'));
		}

		// Se o arquivo de template não existir, exibe erro 404
		if (!$this->templateExists($this->templateName)) {
			Errors::displayError(404, $this->templateName . self::TPL_NAME_SUFIX);
		}

		return true;
	}

	/**
	 *	\brief Verifica se o template está cacheado
	 *
	 * @return boolean
	 */
	public function isCached()
	{
		return $this->tplObj->isCached($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId);
	}

	/**
	 *	\brief Define o cacheamento dos templates
	 *
	 * @
	 */
	public function setCaching($value='current')
	{
		$this->tplObj->setCaching( $value != 'current' ? \Smarty::CACHING_LIFETIME_SAVED : \Smarty::CACHING_LIFETIME_CURRENT);
	}

	public function setCacheLifetime($seconds)
	{
		$this->tplObj->setCacheLifetime($seconds);
	}

	/**
	 *	\brief Retorna a página montada
	 */
	public function fetch()
	{
		$this->setAutoTemplatePaths();

		// Alimenta as variáveis CONSTANTES
		$this->tplObj->assign('HOST', URI::buildURL());
		$this->tplObj->assign('CURRENT_PAGE_URI', URI::currentPageURI());
		$this->tplObj->assign('SYSTEM_NAME', $GLOBALS['SYSTEM']['SYSTEM_NAME']);
		$this->tplObj->assign('SYSTEM_VERSION', $GLOBALS['SYSTEM']['SYSTEM_VERSION']);
		$this->tplObj->assign('ACTIVE_ENVIRONMENT', $GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT']);

		// Alimenta as variáveis padrão da aplicação
		foreach (Template_Static::getDefaultVars() as $name => $value) {
			$this->tplObj->assign($name, $value);
		}

		// Alimenta as variáveis do template
		foreach ($this->templateVars as $name => $data) {
			$this->tplObj->assign($name, $data['value'], $data['nocache']);
		}

		// Inicializa a função padrão assetFile
		$this->tplObj->registerPlugin('function', 'assetFile', array($this, 'assetFile'));

		// Inicializa as funções personalizadas padrão
		foreach(Template_Static::getDefaultPlugins() as $func) {
			$this->tplObj->registerPlugin($func[0], $func[1], $func[2], $func[3], $func[4]);
		}

		// Inicializa as funções personalizadas do template
		foreach($this->templateFuncs as $func) {
			$this->tplObj->registerPlugin($func[0], $func[1], $func[2], $func[3], $func[4]);
		}

		return $this->tplObj->fetch($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId);
	}

	/**
	 *	\brief Define o arquivos de template
	 * @param String $tpl Nome do template, sem extenção do arquivo
	 */
	public function setTemplate($tpl)
	{
		$this->templateName = ((is_array($tpl)) ? join(DIRECTORY_SEPARATOR, $tpl) : $tpl);

		$compile = '';
		if (!is_null($tpl)) {
			$compile = is_array($tpl) ? implode('/', $tpl) : $tpl;
			$compile = substr($compile, 0, strrpos('/', $compile));
		}

		$this->setCompileDir( Configuration::get('template', 'compiled_template_path') . $compile );
	}

	/**
	 *	\brief Define o id do cache
	 */
	public function setCacheId($id)
	{
		$this->templateCacheId = $id;
	}

	/**
	 *	\brief Define o id da compilação
	 */
	public function setCompileId($id)
	{
		$this->templateCompileId = $id;
	}

	/**
	 *	\brief Define uma variável do template
	 */
	public function assign($var, $value=null, $nocache=false)
	{
		if (is_array($var)) {
			foreach ($var as $name => $value) {
				$this->assign($name, $value);
			}
		} else {
			$this->templateVars[$var] = array('value' => $value, 'nocache' => $nocache);
		}
	}

	/**
	 *	\brief Método statico que define um pluguin para todas as instancias da Template
	 */
	public function registerPlugin($type, $name, $callback, $cacheable=NULL, $cache_attrs=NULL)
	{
		$this->templateFuncs[] = array($type, $name, $callback, $cacheable, $cache_attrs);
	}

	/**
	 *	\brief Limpa uma variável do template
	 */
	public function clearAssign($var)
	{
		unset($this->tplVars[$var]);
	}

	/**
	 *	\brief clears the entire template cache
	 *
	 *	As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
	 */
	public function clearAllCache($expire_time)
	{
		$this->tplObj->clearAllCache($expire_time);
	}

	/**
	 *	\brief Limpa o cache para o template corrente
	 */
	public function clearCache($expireTime=NULL)
	{
		$this->tplObj->clearCache($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId, $expireTime);
	}

	/**
	 *	\brief Limpa a versão compilada do template atual
	 */
	public function clearCompiled($expTime)
	{
		$this->tplObj->clearCompiledTemplate($this->templateName . self::TPL_NAME_SUFIX, $this->templateCompileId, $expTime);
	}

	/**
	 *	\brief Limpa variável de config definida
	 */
	public function clearConfig($var)
	{
		$this->tplObj->clearConfig($var);
	}

	/**
	 *	\brief Verifica se um arquivo de template existe
	 */
	public function templateExists($tplName)
	{
		return $this->tplObj->templateExists($tplName . self::TPL_NAME_SUFIX);
	}

	/**
	 *  \brief Mascara nome de arquivo estático para evitar cache do navegador
	 *
	 *  Este método é inserido como função de template para utilização na criação da URI
	 *  de arquivos estáticos de CSS e JavaScript com objetivo de evitar que o cache
	 *  do navegador utilize versões desatualizadas deles.
	 */
	function assetFile($params, $smarty)
	{
		if ($params['type'] == 'js') {
			$filePath = Configuration::get('system', 'js_path') . DIRECTORY_SEPARATOR . $params['file'] . '.js';
			$fileURI = Configuration::get('uri', 'js_dir');
		} elseif ($params['type'] == 'css') {
			$filePath = Configuration::get('system', 'css_path') . DIRECTORY_SEPARATOR . $params['file'] . '.css';
			$fileURI = Configuration::get('uri', 'css_dir');
		} else {
			return '#';
		}

		if (file_exists($filePath)) {
			$fileURI .= '/' . $params['file'] . '__' . filemtime($filePath) . '.' . $params['type'];
		} else {
			$fileURI .= '/' . $params['file'] . '.' . $params['type'];
		}

		return URI::buildURL(explode('/', $fileURI), array(), isset($_SERVER['HTTPS']), 'static', false);
	}
}