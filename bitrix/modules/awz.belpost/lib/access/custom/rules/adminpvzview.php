<?php
namespace Awz\Belpost\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Belpost\Access\Custom\PermissionDictionary;
use Awz\Belpost\Access\Custom\Helper;

class Adminpvzview extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin())
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::ADMIN_PVZVIEW))
        {
            return true;
        }
        return false;
    }

}