<?php if($versions != FALSE) : ?>
<table id="nsm_au_updates">
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
		<?php $count = 0; foreach ($versions as $version) : $class = ($count%2) ? "alt" : ""; $count++; ?>
		<tr class="<?=$class?>" >
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
				<?php if($version['download'] !== FALSE) : ?>
				<a href="<?= $version['download']['url'] ?>" rel="external">Download</a>
				<?php endif; ?>
			</td>
		</tr>
		<?php if ($version['notes']) : ?>
		<tr class="<?=$class?>" style="display:none">
			<td colspan="5">
				<h2><?= $version['title']; ?></h2>
				<p>Published: <?= $version['created_at']; ?></p>
				<?= $version['notes']; ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php endforeach; ?>
	</tbody>
</table>

<?php else: ?>

<p>All extensions are up-to-date</p>

<?php endif; ?>
