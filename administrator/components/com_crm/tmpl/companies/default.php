<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \Crm\Component\Crm\Administrator\View\Companies\HtmlView $this */
?>
<div class="crm-companies">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1>Компании CRM</h1>
	</div>

	<?php if (empty($this->items)): ?>
		<p>Нет компаний. Добавьте компанию через phpMyAdmin или SQL: INSERT INTO #__crm_companies (name, stage_code) VALUES ('Тестовая компания', 'C0');</p>
	<?php else: ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Название</th>
					<th>Стадия</th>
					<th>Обновлено</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->items as $company): ?>
					<tr>
						<td><?php echo (int) $company->id; ?></td>
						<td><?php echo htmlspecialchars($company->name); ?></td>
						<td><span class="badge bg-secondary"><?php echo htmlspecialchars($this->stageTitles[$company->stage_code] ?? $company->stage_code); ?></span></td>
						<td><?php echo htmlspecialchars($company->updated ?? ''); ?></td>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_crm&view=company&id=' . (int) $company->id); ?>" class="btn btn-sm btn-primary">Карточка</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
</div>
