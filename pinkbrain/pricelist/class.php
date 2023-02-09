<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Event;

class PinkbrainPricelist extends CBitrixComponent {

    private const IBLOCK_CODE = 'aspro_optimus_catalog';
    private $iblockId;

    private function _checkModules() {
        if (!Loader::includeModule('iblock')) {
            $this->arResult['ERROR'][] = Loc::getMessage("ERROR_MODULE");
        }

        if (!Loader::includeModule('pinkbrain.pricelist')) {
            $this->arResult['ERROR'][] = Loc::getMessage("ERROR_MODULE_PINKBRAIN");
        }

        return true;
    }

    private function _checkIblock()
    {
        $this->$iblockId = IblockTable::getList(
            [
                'select' => ['ID'],
                'filter' => ['=CODE' => self::IBLOCK_CODE],
                'cache' => ['ttl' => 86400]
            ]
        )->fetch()['ID'];

        if(empty($this->$iblockId)){
            $this->arResult['ERROR'][] = Loc::getMessage("ERROR_IBLOCK_AVAILABLE", ['#IBLOCK_CODE#' => self::IBLOCK_CODE]);
        }
    }

    public function executeComponent() {
        $this->_checkModules();
        $this->_checkIblock();

        $this->arResult['PATH_TO_PRICELIST'] = \Pinkbrain\PriceList::getPathFile()['PATH_TO_FILE'];

        $this->includeComponentTemplate();
    }
}