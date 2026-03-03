<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$item = $this->item;
$id = (int) $item->id;
?>
<div class="crm-company-card">
	<div class="crm-header d-flex justify-content-between align-items-center mb-3">
		<h1><?php echo htmlspecialchars($item->name); ?></h1>
		<a href="<?php echo Route::_('index.php?option=com_crm&view=companies'); ?>" class="btn btn-outline-secondary">← К списку</a>
	</div>

	<div class="crm-stage mb-3">
		<strong>Текущая стадия:</strong>
		<span class="badge bg-primary"><?php echo htmlspecialchars($this->stageTitle); ?></span>
	</div>

	<section class="crm-script card mb-3">
		<div class="card-header">Инструкция / скрипт</div>
		<div class="card-body">
			<p class="mb-0"><?php echo nl2br(htmlspecialchars($this->script)); ?></p>
		</div>
	</section>

	<section class="crm-actions card mb-3">
		<div class="card-header">Доступные действия</div>
		<div class="card-body">
			<?php if (empty($this->allowedActions)): ?>
				<p class="text-muted">Нет доступных действий для этой стадии.</p>
			<?php else: ?>
				<div class="d-flex flex-wrap gap-2">
					<?php foreach ($this->allowedActions as $action): ?>
						<?php
						$label = $this->actionLabels[$action] ?? $action;
						$eventType = $this->actionToEventType($action);
						if (in_array($action, ['comment', 'discovery', 'plan_demo'], true) && $eventType):
							$modalId = 'modal-' . $action . '-' . $id;
						?>
							<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
								<?php echo htmlspecialchars($label); ?>
							</button>
							<?php echo $this->getActionModal($action, $id, $modalId, $eventType, $label); ?>
						<?php elseif ($action === 'demo_link'): ?>
							<form action="<?php echo Route::_('index.php'); ?>" method="post" class="d-inline">
								<?php echo HTMLHelper::_('form.token'); ?>
								<input type="hidden" name="option" value="com_crm" />
								<input type="hidden" name="task" value="company.recordEvent" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<input type="hidden" name="event_type" value="demo_done" />
								<button type="submit" class="btn btn-primary">Демо проведено (зафиксировать)</button>
							</form>
						<?php elseif ($eventType): ?>
							<form action="<?php echo Route::_('index.php'); ?>" method="post" class="d-inline">
								<?php echo HTMLHelper::_('form.token'); ?>
								<input type="hidden" name="option" value="com_crm" />
								<input type="hidden" name="task" value="company.recordEvent" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<input type="hidden" name="event_type" value="<?php echo htmlspecialchars($eventType); ?>" />
								<button type="submit" class="btn btn-outline-primary"><?php echo htmlspecialchars($label); ?></button>
							</form>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="crm-history card">
		<div class="card-header">История событий</div>
		<div class="card-body">
			<?php if (empty($item->events)): ?>
				<p class="text-muted">Событий пока нет.</p>
			<?php else: ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($item->events as $ev): ?>
						<li class="list-group-item d-flex justify-content-between">
							<span><?php echo htmlspecialchars($this->eventTypeLabel($ev->event_type)); ?></span>
							<small class="text-muted"><?php echo htmlspecialchars($ev->created); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
</div>
