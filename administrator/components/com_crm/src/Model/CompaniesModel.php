<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;

class CompaniesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('c.*')
            ->from($db->quoteName('#__crm_companies', 'c'))
            ->order($db->quoteName('c.updated') . ' DESC');
        return $query;
    }
}
