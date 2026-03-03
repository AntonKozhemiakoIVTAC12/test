<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Model;

use Joomla\CMS\Factory;
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

    public function createCompany(string $name, string $stageCode = 'C0'): int
    {
        $db = $this->getDatabase();

        $row = (object) [
            'name' => $name,
            'stage_code' => $stageCode,
            'created_by' => Factory::getApplication()->getIdentity()->id ?? null,
        ];

        $ok = $db->insertObject('#__crm_companies', $row, 'id');

        if (!$ok || empty($row->id)) {
            return 0;
        }

        return (int) $row->id;
    }

    public function deleteCompany(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__crm_companies'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);

        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
