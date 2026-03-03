<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Service;

/**
 * Логика стадий CRM: переходы и ограничения действий.
 * Given/When/Then — условия переходов реализованы здесь.
 * Не зависит от Joomla (только массивы/скаляры) для тестирования.
 */
final class StageMachine
{
    public const STAGE_ICE = 'C0';
    public const STAGE_TOUCHED = 'C1';
    public const STAGE_AWARE = 'C2';
    public const STAGE_INTERESTED = 'W1';
    public const STAGE_DEMO_PLANNED = 'W2';
    public const STAGE_DEMO_DONE = 'W3';
    public const STAGE_COMMITTED = 'H1';
    public const STAGE_CUSTOMER = 'H2';
    public const STAGE_ACTIVATED = 'A1';
    public const STAGE_NULL = 'N0';

    /** Действия, которые можно запретить на стадии */
    public const ACTION_CREATE_LEAD = 'create_lead';
    public const ACTION_SEND_QUOTE = 'send_quote';
    public const ACTION_PLAN_DEMO = 'plan_demo';
    public const ACTION_SHOW_DEMO = 'show_demo';
    public const ACTION_CALL = 'call';
    public const ACTION_COMMENT = 'comment';
    public const ACTION_DISCOVERY = 'discovery';
    public const ACTION_DEMO_LINK = 'demo_link';
    public const ACTION_INVOICE = 'invoice';
    public const ACTION_PAYMENT = 'payment';
    public const ACTION_CERTIFICATE = 'certificate';

    private const DEMO_VALID_DAYS = 60;

    /**
     * Правила стадий: код => [title, forbidden_actions, entry_condition, exit_condition]
     */
    private static function getStageRules(): array
    {
        return [
            self::STAGE_ICE => [
                'title' => 'Ice',
                'forbidden' => [
                    self::ACTION_COMMENT,
                    self::ACTION_DISCOVERY,
                    self::ACTION_PLAN_DEMO,
                    self::ACTION_DEMO_LINK,
                    self::ACTION_CREATE_LEAD,
                    self::ACTION_SEND_QUOTE,
                    self::ACTION_INVOICE,
                    self::ACTION_PAYMENT,
                    self::ACTION_CERTIFICATE,
                ],
                'entry' => null,
                'exit' => 'contact_attempt',
            ],
            self::STAGE_TOUCHED => [
                'title' => 'Touched',
                'forbidden' => [self::ACTION_CREATE_LEAD, self::ACTION_SEND_QUOTE, self::ACTION_PLAN_DEMO, self::ACTION_SHOW_DEMO],
                'entry' => 'no_lpr_conversation',
                'exit' => 'lpr_conversation',
            ],
            self::STAGE_AWARE => [
                'title' => 'Aware',
                'forbidden' => [self::ACTION_PLAN_DEMO, self::ACTION_SHOW_DEMO],
                'entry' => 'lpr_conversation',
                'exit' => 'discovery_filled',
            ],
            self::STAGE_INTERESTED => [
                'title' => 'Interested',
                'forbidden' => [self::ACTION_CREATE_LEAD, self::ACTION_SEND_QUOTE],
                'entry' => 'discovery_filled',
                'exit' => 'demo_planned',
            ],
            self::STAGE_DEMO_PLANNED => [
                'title' => 'demo_planned',
                'forbidden' => [self::ACTION_CREATE_LEAD, self::ACTION_SEND_QUOTE],
                'entry' => 'demo_planned',
                'exit' => 'demo_done',
            ],
            self::STAGE_DEMO_DONE => [
                'title' => 'Demo_done',
                'forbidden' => [],
                'entry' => 'demo_done_recent',
                'exit' => 'lead_or_invoice',
            ],
            self::STAGE_COMMITTED => [
                'title' => 'Committed',
                'forbidden' => [],
                'entry' => 'invoice',
                'exit' => null,
            ],
            self::STAGE_CUSTOMER => [
                'title' => 'Customer',
                'forbidden' => [],
                'entry' => 'payment',
                'exit' => null,
            ],
            self::STAGE_ACTIVATED => [
                'title' => 'Activated',
                'forbidden' => [],
                'entry' => 'first_certificate',
                'exit' => null,
            ],
            self::STAGE_NULL => [
                'title' => 'Null',
                'forbidden' => [],
                'entry' => null,
                'exit' => null,
            ],
        ];
    }

    /** @var list<string> */
    private static array $stageOrder;

    public static function getStageOrder(): array
    {
        if (!isset(self::$stageOrder)) {
            self::$stageOrder = [
                self::STAGE_ICE,
                self::STAGE_TOUCHED,
                self::STAGE_AWARE,
                self::STAGE_INTERESTED,
                self::STAGE_DEMO_PLANNED,
                self::STAGE_DEMO_DONE,
                self::STAGE_COMMITTED,
                self::STAGE_CUSTOMER,
                self::STAGE_ACTIVATED,
                self::STAGE_NULL,
            ];
        }
        return self::$stageOrder;
    }

    public function getAllowedActions(array $company, array $eventTypes): array
    {
        $stage = $company['stage_code'] ?? self::STAGE_ICE;
        $rules = self::getStageRules();
        if (!isset($rules[$stage])) {
            return [];
        }
        $forbidden = $rules[$stage]['forbidden'];
        $all = [
            self::ACTION_CALL,
            self::ACTION_COMMENT,
            self::ACTION_DISCOVERY,
            self::ACTION_PLAN_DEMO,
            self::ACTION_DEMO_LINK,
            self::ACTION_CREATE_LEAD,
            self::ACTION_SEND_QUOTE,
            self::ACTION_INVOICE,
            self::ACTION_PAYMENT,
            self::ACTION_CERTIFICATE,
        ];
        $allowed = [];
        foreach ($all as $action) {
            if (!\in_array($action, $forbidden, true) && $this->isActionAvailableForState($action, $company, $eventTypes)) {
                $allowed[] = $action;
            }
        }
        return $allowed;
    }

    private function isActionAvailableForState(string $action, array $company, array $eventTypes): bool
    {
        switch ($action) {
            case self::ACTION_DISCOVERY:
                return \in_array('lpr_conversation', $eventTypes, true) && empty($company['discovery_filled_at']);
            case self::ACTION_PLAN_DEMO:
                return !empty($company['discovery_filled_at']) && empty($company['demo_planned_at']);
            case self::ACTION_DEMO_LINK:
                return !empty($company['demo_planned_at']) && empty($company['demo_done_at']);
            case self::ACTION_CREATE_LEAD:
            case self::ACTION_SEND_QUOTE:
                return $this->isDemoDoneRecent($company);
            case self::ACTION_INVOICE:
                return !empty($company['demo_done_at']) && empty($company['invoice_at']);
            case self::ACTION_PAYMENT:
                return !empty($company['invoice_at']) && empty($company['payment_at']);
            case self::ACTION_CERTIFICATE:
                return !empty($company['payment_at']) && empty($company['first_certificate_at']);
            case self::ACTION_CALL:
            case self::ACTION_COMMENT:
                return true;
            default:
                return true;
        }
    }

    public function canPerformAction(string $action, array $company, array $eventTypes): bool
    {
        $allowed = $this->getAllowedActions($company, $eventTypes);
        return \in_array($action, $allowed, true);
    }

    public function resolveStageAfterEvent(string $currentStage, string $eventType, array $company): string
    {
        $order = self::getStageOrder();
        $idx = array_search($currentStage, $order, true);
        if ($idx === false) {
            return $currentStage;
        }

        $rules = self::getStageRules();
        $rule = $rules[$currentStage] ?? null;
        if (!$rule) {
            return $currentStage;
        }

        $nextStage = null;
        switch ($eventType) {
            case 'contact_attempt':
                if ($currentStage === self::STAGE_ICE && $rule['exit'] === 'contact_attempt') {
                    $nextStage = self::STAGE_TOUCHED;
                }
                break;
            case 'lpr_conversation':
                if ($rule['exit'] === 'lpr_conversation') {
                    $nextStage = self::STAGE_AWARE;
                }
                break;
            case 'discovery_filled':
                if ($rule['exit'] === 'discovery_filled') {
                    $nextStage = self::STAGE_INTERESTED;
                }
                break;
            case 'demo_planned':
                if ($rule['exit'] === 'demo_planned') {
                    $nextStage = self::STAGE_DEMO_PLANNED;
                }
                break;
            case 'demo_done':
                if ($rule['exit'] === 'demo_done') {
                    $nextStage = self::STAGE_DEMO_DONE;
                }
                break;
            case 'invoice_issued':
            case 'lead_created':
                if ($currentStage === self::STAGE_DEMO_DONE && $rule['exit'] === 'lead_or_invoice') {
                    $nextStage = self::STAGE_COMMITTED;
                }
                break;
            case 'payment_received':
                if ($currentStage === self::STAGE_COMMITTED) {
                    $nextStage = self::STAGE_CUSTOMER;
                }
                break;
            case 'first_certificate':
                if ($currentStage === self::STAGE_CUSTOMER) {
                    $nextStage = self::STAGE_ACTIVATED;
                }
                break;
        }

        if ($nextStage !== null) {
            $nextIdx = array_search($nextStage, $order, true);
            if ($nextIdx !== false && $nextIdx > $idx) {
                return $nextStage;
            }
        }
        return $currentStage;
    }

    public function getStageTitle(string $code): string
    {
        $rules = self::getStageRules();
        return $rules[$code]['title'] ?? $code;
    }

    public function getScriptForStage(string $stageCode): string
    {
        $scripts = [
            self::STAGE_ICE => 'Позвоните компании. Цель: установить контакт с ЛПР.',
            self::STAGE_TOUCHED => 'После звонка: заполните комментарий и форму дискавери (если был разговор с ЛПР).',
            self::STAGE_AWARE => 'Заполните форму дискавери. Не планируйте демо до заполнения.',
            self::STAGE_INTERESTED => 'Назначьте дату и время демонстрации.',
            self::STAGE_DEMO_PLANNED => 'Проведите демо по ссылке. После проведения нажмите кнопку «Демо проведено».',
            self::STAGE_DEMO_DONE => 'Заведите заявку и/или отправьте КП. После выставления счёта стадия изменится.',
            self::STAGE_COMMITTED => 'Ожидание оплаты. После получения оплаты зафиксируйте событие.',
            self::STAGE_CUSTOMER => 'После выдачи первого удостоверения зафиксируйте событие.',
            self::STAGE_ACTIVATED => 'Клиент активирован. Дальнейшая поддержка по процессу.',
            self::STAGE_NULL => '—',
        ];
        return $scripts[$stageCode] ?? '—';
    }

    private function isDemoDoneRecent(array $company): bool
    {
        $done = $company['demo_done_at'] ?? null;
        if (!$done) {
            return false;
        }
        $ts = $done instanceof \DateTimeInterface ? $done->getTimestamp() : strtotime($done);
        return $ts && (time() - $ts) < (self::DEMO_VALID_DAYS * 86400);
    }
}
