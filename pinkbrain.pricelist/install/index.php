<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class pinkbrain_pricelist extends CModule
{
    public function __construct()
    {

        if (file_exists(__DIR__ . "/version.php")) {

            $arModuleVersion = array();

            include_once(__DIR__ . "/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = Loc::getMessage("NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("DESCRIPTION");
            $this->PARTNER_NAME = Loc::getMessage("PARTNER_NAME");
            $this->PARTNER_URI = Loc::getMessage("PARTNER_URI");
        }

        return false;
    }

    public function DoInstall()
    {

        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion("main"), "14.00.00")) {

            $this->InstallDB();

            ModuleManager::registerModule($this->MODULE_ID);
        } else {

            $APPLICATION->ThrowException(
                Loc::getMessage("INSTALL_ERROR_VERSION")
            );
        }

        $format = DateTime::getFormat();
        $tomorrow = date($format, strtotime("+1 days"));
        $dateTime = new DateTime($tomorrow);
        $tomorrowFormat = $dateTime->format("d.m.Y")." 10:30:00";

        CAgent::AddAgent('\Pinkbrain\Pricelist::agentRunGenerate();', $this->MODULE_ID, 'N', 86400, '', 'Y', $tomorrowFormat);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("INSTALL_TITLE") . " \"" . Loc::getMessage("NAME") . "\"",
            __DIR__ . "/step.php"
        );

        return false;
    }

    public function InstallDB()
    {

        return false;
    }

    public function DoUninstall()
    {

        global $APPLICATION;

        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        CAgent::RemoveModuleAgents($this->MODULE_ID);


        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("UNINSTALL_TITLE") . " \"" . Loc::getMessage("NAME") . "\"",
            __DIR__ . "/unstep.php"
        );

        return false;
    }

    public function UnInstallDB()
    {

        Option::delete($this->MODULE_ID);

        return false;
    }


}