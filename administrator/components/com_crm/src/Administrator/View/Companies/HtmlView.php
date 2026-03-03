<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\View\Companies;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $items;

    protected $pagination;

    protected $state;

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        $machine = new \Crm\Component\Crm\Administrator\Service\StageMachine();
        $this->stageTitles = [];
        foreach (\Crm\Component\Crm\Administrator\Service\StageMachine::getStageOrder() as $code) {
            $this->stageTitles[$code] = $machine->getStageTitle($code);
        }

        parent::display($tpl);
    }
}
