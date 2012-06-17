<div id="nsm_addon_updater_ajax_return">
	<?php if($versions != false) : ?>
	<table class="data col-sortable">
		<thead>
			<tr>
				<th scope="col">Addon</th>
				<th scope="col">Installed</th>
				<th scope="col">Latest</th>
				<th scope="col">&nbsp;</th>
				<th scope="col">&nbsp;</th>
				<th scope="col">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php $count = 0; foreach ($versions as $version) : $class = ($count%2) ? "odd" : "even"; $count++; ?>
			
			<?php if($version['error']) : ?>
				<tr class="<?=$class?> alert <?= $version['row_class'] ?>">
					<th scope="row"><?= $version['addon_name']; ?></th>
					<td><?= $version['installed_version']; ?></td>
					<td colspan="4"><?= $version['error'] ?></td>
				</tr>
			<?php else : ?>

				<tr class="<?=$class?> alert <?= $version['row_class'] ?>">
					<th scope="row"><?= $version['addon_name']; ?></th>
					<td><?= $version['installed_version']; ?></td>
					<td><?= $version['latest_version']; ?></td>
					<td>
						<?php if ($version['notes']) : ?>
							<a href="#" class="note-trigger">Release notes</a>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($version['docs_url']) : ?>
							<a href="<?= $version['docs_url']; ?>" rel="external">Visit site</a></td>
						<?php endif; ?>
					</td>
					<td>
						<?php if($version['download'] !== false) : ?>
						<a href="<?= $version['download']['url'] ?>" rel="external">Download</a>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ($version['notes']) : ?>
				<tr class="<?=$class?>" style="display:none">
					<td colspan="5">
						<h3><?= $version['title']; ?></h3>
						<p>Published: <?= $version['created_at']; ?></p>
						<?= $version['notes']; ?>
					</td>
				</tr>
				<?php endif; ?>

			<?php endif; ?>
			
			
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else: ?>
		<div class="alert success">All extensions are up-to-date</div>
	<?php endif; ?>
</div>