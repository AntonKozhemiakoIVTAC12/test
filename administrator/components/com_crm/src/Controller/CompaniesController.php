<?php

declare(strict_types=1);

namespace Crm\Component\Crm\Administrator\Controller;

use Crm\Component\Crm\Administrator\Service\StageMachine;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class CompaniesController extends BaseController
{
    protected $default_view = 'companies';

    public function createCard(): bool
    {
        $this->checkToken();

        $name = trim((string) $this->input->getString('name', ''));
        $stageCode = (string) $this->input->getString('stage_code', StageMachine::STAGE_ICE);
        $allowedStages = StageMachine::getStageOrder();

        if ($name === '') {
            $this->setMessage('Введите название компании', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
            return false;
        }

        if (!in_array($stageCode, $allowedStages, true)) {
            $stageCode = StageMachine::STAGE_ICE;
        }

        /** @var \Crm\Component\Crm\Administrator\Model\CompaniesModel $model */
        $model = $this->getModel('Companies');
        $newId = $model->createCompany($name, $stageCode);

        if ($newId <= 0) {
            $this->setMessage('Не удалось создать карточку компании', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
            return false;
        }

        $this->setMessage(Text::sprintf('Карточка компании "%s" создана', $name));
        $this->setRedirect(Route::_('index.php?option=com_crm&view=company&id=' . $newId, false));
        return true;
    }

    public function deleteCard(): bool
    {
        $this->checkToken();

        $id = (int) $this->input->getInt('id');
        if ($id <= 0) {
            $this->setMessage('Некорректный ID компании', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
            return false;
        }

        /** @var \Crm\Component\Crm\Administrator\Model\CompaniesModel $model */
        $model = $this->getModel('Companies');
        $ok = $model->deleteCompany($id);

        if (!$ok) {
            $this->setMessage('Не удалось удалить карточку компании', 'error');
            $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
            return false;
        }

        $this->setMessage('Карточка компании удалена');
        $this->setRedirect(Route::_('index.php?option=com_crm&view=companies', false));
        return true;
    }
}
