<?php if($versions != FALSE) : ?>
<table id="nsm_au_updates" class="mainTable" style="clear:left; margin-left:3.3%; margin-right:3.3%; width:auto" cellspacing="0">
	<caption>The following addon updates are available</caption>
	<thead>
		<tr>
			<th scope="col">Addon</th>
			<th scope="col">Installed version</th>
			<th scope="col">Latest version</th>
			<th scope="col">&nbsp;</th>
			<th scope="col">&nbsp;</th>
			<th scope="col">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($versions as $count => $version) : $class = ($count%2) ? "even" : "odd"; ?>
		<tr class="<?=$class?>">
			<th scope="row"><?= $version['addon_name']; ?></th>
			<td><?= $version['installed_version']; ?></td>
			<td><?= $version['latest_version']; ?></td>
			<td>
				<?php if ($version['notes']) : ?>
					<a href="#" class="note-trigger">Version notes</a>
				<?php endif; ?>
			</td>
			<td>
				<?php if ($version['docs_url']) : ?>
					<a href="<?= $version['docs_url']; ?>" rel="external">Visit site</a></td>
				<?php endif; ?>
			</td>
			<td>
				<?php if ($version['download']) : ?>
					<a href="<?= $version['download']['url']; ?>" rel="external">Direct download</a>
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
<?php endif; ?>