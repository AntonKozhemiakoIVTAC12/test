<?php

declare(strict_types=1);

namespace Crm\Tests;

use Crm\Component\Crm\Administrator\Service\StageMachine;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты логики стадий CRM (Given/When/Then).
 * Покрытие: ограничения действий по стадиям, переходы по событиям.
 */
class StageMachineTest extends TestCase
{
    private StageMachine $machine;

    protected function setUp(): void
    {
        $this->machine = new StageMachine();
    }

    /** Given: компания в стадии Ice. When: запрос доступных действий. Then: только call (нельзя заявку/КП/демо). */
    public function test_ice_stage_only_call_allowed(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_ICE];
        $events = [];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertContains(StageMachine::ACTION_CALL, $allowed);
        $this->assertNotContains(StageMachine::ACTION_CREATE_LEAD, $allowed);
        $this->assertNotContains(StageMachine::ACTION_SEND_QUOTE, $allowed);
        $this->assertNotContains(StageMachine::ACTION_PLAN_DEMO, $allowed);
    }

    /** Given: Touched, нет события lpr_conversation. When: доступные действия. Then: нет discovery. */
    public function test_touched_without_lpr_no_discovery(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_TOUCHED];
        $events = [];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertNotContains(StageMachine::ACTION_DISCOVERY, $allowed);
    }

    /** Given: Touched, есть lpr_conversation. When: доступные действия. Then: discovery доступно. */
    public function test_touched_with_lpr_discovery_available(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_TOUCHED];
        $events = ['lpr_conversation'];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertContains(StageMachine::ACTION_DISCOVERY, $allowed);
    }

    /** Given: Aware. When: доступные действия. Then: discovery доступно, plan_demo — нет (пока не заполнен дискавери). */
    public function test_aware_discovery_allowed_plan_demo_not_until_filled(): void
    {
        $company = [
            'stage_code' => StageMachine::STAGE_AWARE,
            'discovery_filled_at' => null,
        ];
        $events = ['lpr_conversation'];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertContains(StageMachine::ACTION_DISCOVERY, $allowed);
        $this->assertNotContains(StageMachine::ACTION_PLAN_DEMO, $allowed);
    }

    /** Given: Interested (после дискавери), дата демо не назначена. When: доступные действия. Then: plan_demo доступно. */
    public function test_interested_after_discovery_plan_demo_allowed(): void
    {
        $company = [
            'stage_code' => StageMachine::STAGE_INTERESTED,
            'discovery_filled_at' => '2025-01-15 10:00:00',
            'demo_planned_at' => null,
        ];
        $events = ['discovery_filled'];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertContains(StageMachine::ACTION_PLAN_DEMO, $allowed);
    }

    /** Given: Touched. When: событие lpr_conversation. Then: переход в Aware. */
    public function test_touched_lpr_conversation_moves_to_aware(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_TOUCHED];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_TOUCHED, 'lpr_conversation', $company);
        $this->assertSame(StageMachine::STAGE_AWARE, $next);
    }

    /** Given: Aware. When: событие discovery_filled. Then: переход в Interested. */
    public function test_aware_discovery_filled_moves_to_interested(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_AWARE];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_AWARE, 'discovery_filled', $company);
        $this->assertSame(StageMachine::STAGE_INTERESTED, $next);
    }

    /** Given: Interested. When: событие demo_planned. Then: переход в demo_planned. */
    public function test_interested_demo_planned_moves_to_demo_planned(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_INTERESTED];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_INTERESTED, 'demo_planned', $company);
        $this->assertSame(StageMachine::STAGE_DEMO_PLANNED, $next);
    }

    /** Given: demo_planned. When: событие demo_done. Then: переход в Demo_done. */
    public function test_demo_planned_demo_done_moves_to_demo_done(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_DEMO_PLANNED];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_DEMO_PLANNED, 'demo_done', $company);
        $this->assertSame(StageMachine::STAGE_DEMO_DONE, $next);
    }

    /** Given: Demo_done. When: событие invoice_issued. Then: переход в Committed. */
    public function test_demo_done_invoice_moves_to_committed(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_DEMO_DONE];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_DEMO_DONE, 'invoice_issued', $company);
        $this->assertSame(StageMachine::STAGE_COMMITTED, $next);
    }

    /** Given: Committed. When: событие payment_received. Then: переход в Customer. */
    public function test_committed_payment_moves_to_customer(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_COMMITTED];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_COMMITTED, 'payment_received', $company);
        $this->assertSame(StageMachine::STAGE_CUSTOMER, $next);
    }

    /** Given: Customer. When: событие first_certificate. Then: переход в Activated. */
    public function test_customer_first_certificate_moves_to_activated(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_CUSTOMER];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_CUSTOMER, 'first_certificate', $company);
        $this->assertSame(StageMachine::STAGE_ACTIVATED, $next);
    }

    /** Given: Ice. When: событие lpr_conversation (не exit для Ice). Then: стадия не меняется. */
    public function test_ice_lpr_conversation_does_not_change_stage(): void
    {
        $company = ['stage_code' => StageMachine::STAGE_ICE];
        $next = $this->machine->resolveStageAfterEvent(StageMachine::STAGE_ICE, 'lpr_conversation', $company);
        $this->assertSame(StageMachine::STAGE_ICE, $next);
    }

    /** Given: Demo_done, демо было > 60 дней назад. When: доступные действия. Then: create_lead/send_quote недоступны. */
    public function test_demo_done_older_than_60_days_no_lead_quote(): void
    {
        $company = [
            'stage_code' => StageMachine::STAGE_DEMO_DONE,
            'demo_done_at' => date('Y-m-d H:i:s', time() - 61 * 86400),
        ];
        $events = [];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertNotContains(StageMachine::ACTION_CREATE_LEAD, $allowed);
        $this->assertNotContains(StageMachine::ACTION_SEND_QUOTE, $allowed);
    }

    /** Given: Demo_done, демо было недавно. When: доступные действия. Then: create_lead и send_quote доступны. */
    public function test_demo_done_recent_lead_quote_allowed(): void
    {
        $company = [
            'stage_code' => StageMachine::STAGE_DEMO_DONE,
            'demo_done_at' => date('Y-m-d H:i:s', time() - 1),
        ];
        $events = [];
        $allowed = $this->machine->getAllowedActions($company, $events);
        $this->assertContains(StageMachine::ACTION_CREATE_LEAD, $allowed);
        $this->assertContains(StageMachine::ACTION_SEND_QUOTE, $allowed);
    }

    public function test_get_stage_order(): void
    {
        $order = StageMachine::getStageOrder();
        $this->assertSame(StageMachine::STAGE_ICE, $order[0]);
        $this->assertSame(StageMachine::STAGE_ACTIVATED, $order[8]);
    }

    public function test_get_stage_title(): void
    {
        $this->assertSame('Touched', $this->machine->getStageTitle(StageMachine::STAGE_TOUCHED));
        $this->assertSame('Demo_done', $this->machine->getStageTitle(StageMachine::STAGE_DEMO_DONE));
    }
}
