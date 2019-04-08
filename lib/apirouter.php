<?php

namespace Webpractik\Api;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use \Bitrix\Main\Loader;

/**
 * Class ApiRouter
 * @package Webpractik\Api
 */
abstract class ApiRouter extends \CBitrixComponent
{
	/**
	 * @var array
	 */

	public $sefVariables = [];

	/**
	 * Массив маршрутов в формате:
	 * '\\ПутьКласса\\ИмяКласса' => 'адрес/маршрута/'
	 * где адрес/маршрута без приставки /api/
	 *
	 * Пример:
	 * '\MySite\Lk\Response\Resubmit' => 'application/resubmit/',
	 * @var array
	 */
	public $arUrlTemplates = [];

	/**
	 * @var array
	 */
	public $arComponentVariables = [];

	/**
	 * Список модулей необходимых к загрузке
	 * @var array
	 * @todo придумать более элегантное решение
	 */
	public $arLoadModules = [];

	/**
	 * Execution component
	 */
	public function executeComponent() {
        $this->expansionOptions();
		Loader::includeModule('webpractik.api');
		foreach ($this->arLoadModules as $loadModule) {
			Loader::includeModule($loadModule);
		}
		$this->router();
	}

	/**
	 * Маршрутизация
	 */
	private function router() {
		$engine    = new \CComponentEngine($this);
		$className = $engine->ParseComponentPath(
			$this->arParams['SEF_FOLDER'],
			$this->arUrlTemplates,
			$this->sefVariables
		);

		if (!$className || strlen($className) <= 0) {
			$className = '\\Webpractik\\Api\\Error';
		}

		if (!class_exists($className)) {
			$response = new \Webpractik\Api\NotFoundRoute($this->sefVariables);
		} else {
			$response = new $className($this->sefVariables);
		}

		$response->validateMethod();
		if ($response->validate()) {
			$response->handler();
		} else {
			$response->response->sendFail('Невалидный запрос');
		}

		$response->response->send();
	}
    
    /**
     * Метод, который забирает роуты и модули, указанные в соотв. классе метода рейтинга webpractik.rating https://github.com/webpractik/webpractik.rating
     */
    private function expansionOptions()
    {
        $event = new \Bitrix\Main\Event('webpractik.rating', 'OnWebpractikRouterCall');
        $event->send();
        if ($event->getResults()){
            foreach ($event->getResults() as $eventResult)
            {
                $this->arUrlTemplates += $eventResult->getParameters()['ROUTES'];
                $this->arLoadModules += $eventResult->getParameters()['MODULES'];
            }
        }
    }
}
