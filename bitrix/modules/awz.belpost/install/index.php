<?php
use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\EventManager,
    \Bitrix\Main\ModuleManager,
    \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class awz_belpost extends CModule {

    var $MODULE_ID = "awz.belpost";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    var $errors = false;

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("AWZ_BELPOST_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AWZ_BELPOST_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("AWZ_PARTNER_NAME");
        $this->PARTNER_URI = "https://zahalski.dev/";
    }

    function DoInstall()
    {
        global $APPLICATION, $step;

        $this->InstallFiles();
        $this->InstallDB();
        $this->checkOldInstallTables();
        $this->InstallEvents();
        $this->createAgents();

        ModuleManager::RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("AWZ_BELPOST_MODULE_NAME"),
            $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/solution.php'
        );

        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;

        $step = intval($step);
        if($step < 2) { //выводим предупреждение
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AWZ_BELPOST_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/unstep.php'
            );
        }
        elseif($step == 2) {
            if($_REQUEST['save'] != 'Y' && !isset($_REQUEST['save'])) {
                $this->UnInstallDB();
            }
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->deleteAgents();

            if($_REQUEST['saveopts'] != 'Y' && !isset($_REQUEST['saveopts'])) {
                \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
            }

            ModuleManager::UnRegisterModule($this->MODULE_ID);
            return true;
        }
    }

    function InstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $connection = \Bitrix\Main\Application::getConnection();
        $this->errors = false;
        if(!$this->errors && !$DB->TableExists('b_awz_belpost_pvz')) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/".$connection->getType()."/install.sql");
        }
        if(!$this->errors && !$DB->TableExists(implode('_', explode('.',$this->MODULE_ID)).'_permission')) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/".$connection->getType()."/access.sql");
        }
        if (!$this->errors) {
            return true;
        } else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
    }


    function UnInstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $connection = \Bitrix\Main\Application::getConnection();
        $this->errors = false;
        if (!$this->errors) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . $connection->getType() . "/uninstall.sql");
        }
        if (!$this->errors) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . $connection->getType() . "/unaccess.sql");
        }
        if (!$this->errors) {
            return true;
        }
        else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
    }


    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'sale', 'onSaleDeliveryHandlersClassNamesBuildList',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'registerHandler'
        );
        $eventManager->registerEventHandlerCompatible("sale", "OnSaleComponentOrderOneStepDelivery",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OrderDeliveryBuildList'
        );
        $eventManager->registerEventHandlerCompatible("sale", "OnSaleComponentOrderCreated",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnSaleComponentOrderCreated'
        );
        $eventManager->registerEventHandlerCompatible("main", "OnEndBufferContent",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnEndBufferContent'
        );
        $eventManager->registerEventHandlerCompatible("main", "OnAdminSaleOrderEditDraggable",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnAdminSaleOrderEditDraggable'
        );
        $eventManager->registerEventHandler("sale", "OnSaleOrderBeforeSaved",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', "OnSaleOrderBeforeSaved");
        $eventManager->registerEventHandlerCompatible("main", "OnAdminContextMenuShow",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', "OnAdminContextMenuShow");
        $eventManager->registerEventHandlerCompatible("main", "OnEpilog",
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', "OnEpilog");
        $eventManager->registerEventHandlerCompatible(
            'main', 'OnAfterUserUpdate',
            $this->MODULE_ID, '\\Awz\\Belpost\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        $eventManager->registerEventHandlerCompatible(
            'main', 'OnAfterUserAdd',
            $this->MODULE_ID, '\\Awz\\Belpost\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        return true;
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'sale', 'onSaleDeliveryHandlersClassNamesBuildList',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'registerHandler'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnSaleComponentOrderOneStepDelivery',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OrderDeliveryBuildList'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnSaleComponentOrderCreated',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnSaleComponentOrderCreated'
        );
        $eventManager->unRegisterEventHandler(
            'main', 'OnEndBufferContent',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnEndBufferContent'
        );
        $eventManager->unRegisterEventHandler(
            'main', 'OnAdminSaleOrderEditDraggable',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnAdminSaleOrderEditDraggable'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnSaleOrderBeforeSaved',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnSaleOrderBeforeSaved'
        );
        $eventManager->unRegisterEventHandler(
            'main', 'OnAdminContextMenuShow',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnAdminContextMenuShow'
        );
        $eventManager->unRegisterEventHandler(
            'main', 'OnEpilog',
            $this->MODULE_ID, '\Awz\Belpost\handlersBx', 'OnEpilog'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnAfterUserUpdate',
            $this->MODULE_ID, '\\Awz\\Belpost\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnAfterUserAdd',
            $this->MODULE_ID, '\\Awz\\Belpost\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin/", true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER['DOCUMENT_ROOT']."/bitrix/images/".$this->MODULE_ID, true);
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components/belpost.baloon/",
            $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/belpost.baloon",
            true, true
        );
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components/belpost.config.permissions/",
            $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/belpost.config.permissions",
            true, true
        );

        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/css/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/components/awz/belpost.baloon");
        DeleteDirFilesEx("/bitrix/components/awz/belpost.config.permissions");
        DeleteDirFiles(
            $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin",
            $_SERVER['DOCUMENT_ROOT']."/bitrix/admin"
        );
        return true;
    }



    function createAgents() {
        CAgent::AddAgent(
            "\\Awz\\Belpost\\Checker::agentGetPickpoints();",
            $this->MODULE_ID,
            "N",
            300);
        CAgent::AddAgent(
            "\\Awz\\Belpost\\Checker::agentGetTarifs();",
            $this->MODULE_ID,
            "N",
            86400*7);
    }

    function deleteAgents() {
        CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    function checkOldInstallTables()
    {
        return true;
    }

}