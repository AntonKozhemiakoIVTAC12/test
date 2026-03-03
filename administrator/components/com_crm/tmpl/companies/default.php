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

	<section class="card mb-3">
		<div class="card-header">Новая карточка компании</div>
		<div class="card-body">
			<form action="<?php echo Route::_('index.php'); ?>" method="post" class="row g-2 align-items-end">
				<input type="hidden" name="option" value="com_crm" />
				<input type="hidden" name="task" value="companies.createCard" />
				<?php echo HTMLHelper::_('form.token'); ?>
				<div class="col-md-6">
					<label class="form-label">Название компании</label>
					<input type="text" name="name" class="form-control" placeholder="Например, Demo Company 2" required />
				</div>
				<div class="col-md-3">
					<label class="form-label">Стартовая стадия</label>
					<select name="stage_code" class="form-select">
						<?php foreach ($this->stageTitles as $code => $title): ?>
							<option value="<?php echo htmlspecialchars($code); ?>" <?php echo $code === 'C0' ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($title); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3">
					<button type="submit" class="btn btn-success w-100">Создать карточку</button>
				</div>
			</form>
		</div>
	</section>

	<?php if (empty($this->items)): ?>
		<p>Пока нет компаний. Создайте первую карточку через форму выше.</p>
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
							<div class="d-flex gap-1">
								<a href="<?php echo Route::_('index.php?option=com_crm&view=company&id=' . (int) $company->id); ?>" class="btn btn-sm btn-primary">Карточка</a>
								<form action="<?php echo Route::_('index.php'); ?>" method="post" onsubmit="return confirm('Удалить карточку компании?');">
									<input type="hidden" name="option" value="com_crm" />
									<input type="hidden" name="task" value="companies.deleteCard" />
									<input type="hidden" name="id" value="<?php echo (int) $company->id; ?>" />
									<?php echo HTMLHelper::_('form.token'); ?>
									<button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
</div>
