<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class CompanyModel extends BaseDatabaseModel
{
    public function getItem($pk = null)
    {
        if (!$pk) {
            $pk = (int) $this->getState('company.id');
        }
        if (!$pk) {
            $pk = (int) Factory::getApplication()->input->getInt('id');
        }
        if (!$pk) {
            return null;
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__crm_companies'))
            ->where($db->quoteName('id') . ' = ' . (int) $pk);
        $db->setQuery($query);
        $item = $db->loadObject();
        if ($item) {
            $item->events = $this->getEvents($pk);
        }
        return $item;
    }

    public function getEvents(int $companyId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('id, event_type, payload, created, created_by')
            ->from($db->quoteName('#__crm_events'))
            ->where($db->quoteName('company_id') . ' = ' . (int) $companyId)
            ->order($db->quoteName('created') . ' DESC');
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    /**
     * Регистрирует событие и при необходимости обновляет стадию компании.
     */
    public function recordEvent(int $companyId, string $eventType, array $payload = []): void
    {
        $company = $this->getCompanyRow($companyId);
        if (!$company) {
            return;
        }

        $machine = new \Crm\Component\Crm\Administrator\Service\StageMachine();
        $newStage = $machine->resolveStageAfterEvent(
            $company->stage_code,
            $eventType,
            (array) $company
        );

        $db = $this->getDatabase();
        $eventRow = (object) [
            'company_id' => $companyId,
            'event_type' => $eventType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_by' => Factory::getApplication()->getIdentity()->id ?? null,
        ];
        $db->insertObject('#__crm_events', $eventRow);

        $update = (object) [
            'id' => $companyId,
            'stage_code' => $newStage,
            'updated' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        if ($eventType === 'discovery_filled') {
            $update->discovery_filled_at = $update->updated;
        }
        if ($eventType === 'demo_planned' && isset($payload['scheduled_at'])) {
            $update->demo_planned_at = $payload['scheduled_at'];
        }
        if ($eventType === 'demo_done') {
            $update->demo_done_at = $update->updated;
        }
        if ($eventType === 'invoice_issued') {
            $update->invoice_at = $update->updated;
        }
        if ($eventType === 'payment_received') {
            $update->payment_at = $update->updated;
        }
        if ($eventType === 'first_certificate') {
            $update->first_certificate_at = $update->updated;
        }
        $db->updateObject('#__crm_companies', $update, ['id']);
    }

    private function getCompanyRow(int $id): ?object
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__crm_companies'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        return $db->loadObject();
    }

}
