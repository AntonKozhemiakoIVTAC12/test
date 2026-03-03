<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\View\Company;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $item;

    protected $state;

    public function display($tpl = null): void
    {
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (!$this->item) {
            Factory::getApplication()->enqueueMessage('Компания не найдена', 'error');
            return;
        }

        $machine = new \Crm\Component\Crm\Administrator\Service\StageMachine();
        $eventTypes = array_column($this->item->events ?? [], 'event_type');
        $companyArray = [
            'stage_code' => $this->item->stage_code,
            'discovery_filled_at' => $this->item->discovery_filled_at ?? null,
            'demo_planned_at' => $this->item->demo_planned_at ?? null,
            'demo_done_at' => $this->item->demo_done_at ?? null,
            'invoice_at' => $this->item->invoice_at ?? null,
            'payment_at' => $this->item->payment_at ?? null,
            'first_certificate_at' => $this->item->first_certificate_at ?? null,
        ];
        $this->allowedActions = $machine->getAllowedActions($companyArray, $eventTypes);
        $this->stageTitle = $machine->getStageTitle($this->item->stage_code);
        $this->script = $machine->getScriptForStage($this->item->stage_code);
        $this->actionLabels = $this->getActionLabels();

        parent::display($tpl);
    }

    private function getActionLabels(): array
    {
        return [
            'call' => 'Позвонить',
            'comment' => 'Комментарий после звонка',
            'discovery' => 'Заполнить дискавери',
            'plan_demo' => 'Запланировать демо',
            'demo_link' => 'Провести демо (ссылка)',
            'create_lead' => 'Завести заявку',
            'send_quote' => 'Отправить КП',
            'invoice' => 'Счёт выставлен',
            'payment' => 'Оплата получена',
            'certificate' => 'Первое удостоверение выдано',
        ];
    }

    public function actionToEventType(string $action): ?string
    {
        $map = [
            'call' => 'contact_attempt',
            'comment' => 'lpr_conversation',
            'discovery' => 'discovery_filled',
            'plan_demo' => 'demo_planned',
            'demo_link' => 'demo_done',
            'create_lead' => 'lead_created',
            'send_quote' => null,
            'invoice' => 'invoice_issued',
            'payment' => 'payment_received',
            'certificate' => 'first_certificate',
        ];
        return $map[$action] ?? null;
    }

    public function eventTypeLabel(string $type): string
    {
        $labels = [
            'contact_attempt' => 'Попытка контакта',
            'lpr_conversation' => 'Разговор с ЛПР + комментарий',
            'discovery_filled' => 'Заполнение дискавери',
            'demo_planned' => 'Планирование демо',
            'demo_done' => 'Демо проведено',
            'lead_created' => 'Заявка создана',
            'invoice_issued' => 'Счёт выставлен',
            'payment_received' => 'Оплата получена',
            'first_certificate' => 'Первое удостоверение выдано',
        ];
        return $labels[$type] ?? $type;
    }

    public function getActionModal(string $action, int $companyId, string $modalId, string $eventType, string $label): string
    {
        $token = \Joomla\CMS\Factory::getApplication()->getSession()->getToken();
        $actionUrl = \Joomla\CMS\Router\Route::_('index.php?option=com_crm&task=company.recordEvent');
        $payloadName = $action === 'plan_demo' ? 'scheduled_at' : 'comment';
        $inputType = $action === 'plan_demo' ? 'datetime-local' : 'textarea';
        $inputLabel = $action === 'plan_demo' ? 'Дата и время демо' : 'Комментарий';
        ob_start();
        ?>
        <div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="<?php echo $actionUrl; ?>" method="post">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo htmlspecialchars($label); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="<?php echo $token; ?>" value="1" />
                            <input type="hidden" name="id" value="<?php echo $companyId; ?>" />
                            <input type="hidden" name="event_type" value="<?php echo htmlspecialchars($eventType); ?>" />
                            <div class="mb-2">
                                <label class="form-label"><?php echo $inputLabel; ?></label>
                                <?php if ($action === 'plan_demo'): ?>
                                    <input type="datetime-local" name="payload[scheduled_at]" class="form-control" required />
                                <?php else: ?>
                                    <textarea name="payload[comment]" class="form-control" rows="3"></textarea>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
