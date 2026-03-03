<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class CompanyController extends FormController
{
    protected $view_list = 'companies';

    public function recordEvent(): bool
    {
        $this->checkToken();
        $id = (int) $this->input->get('id', 0);
        $eventType = $this->input->getString('event_type', '');
        $payload = $this->input->get('payload', [], 'array');

        if (!$id || !$eventType) {
            $this->setMessage('Не указана компания или тип события', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=company&id=' . $id, false));
            return false;
        }

        /** @var \Crm\Component\Crm\Administrator\Model\CompanyModel $model */
        $model = $this->getModel('Company');
        $company = $model->getItem($id);
        if (!$company) {
            $this->setMessage('Компания не найдена', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
            return false;
        }
        $machine = new \Crm\Component\Crm\Administrator\Service\StageMachine();
        $eventTypes = array_column($company->events ?? [], 'event_type');
        $companyArray = [
            'stage_code' => $company->stage_code,
            'discovery_filled_at' => $company->discovery_filled_at ?? null,
            'demo_planned_at' => $company->demo_planned_at ?? null,
            'demo_done_at' => $company->demo_done_at ?? null,
            'invoice_at' => $company->invoice_at ?? null,
            'payment_at' => $company->payment_at ?? null,
            'first_certificate_at' => $company->first_certificate_at ?? null,
        ];
        $action = $this->eventTypeToAction($eventType);
        if ($action !== null && !$machine->canPerformAction($action, $companyArray, $eventTypes)) {
            $this->setMessage('Действие недоступно на текущей стадии. Нельзя перепрыгивать вперёд.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=company&id=' . $id, false));
            return false;
        }
        $model->recordEvent($id, $eventType, $payload);

        $this->setMessage('Событие зарегистрировано');
        $this->setRedirect(Route::_('index.php?option=com_crm&view=company&id=' . $id, false));
        return true;
    }

    private function eventTypeToAction(string $eventType): ?string
    {
        $map = [
            'contact_attempt' => 'call',
            'lpr_conversation' => 'comment',
            'discovery_filled' => 'discovery',
            'demo_planned' => 'plan_demo',
            'demo_done' => 'demo_link',
            'lead_created' => 'create_lead',
            'invoice_issued' => 'invoice',
            'payment_received' => 'payment',
            'first_certificate' => 'certificate',
        ];
        return $map[$eventType] ?? null;
    }
}
