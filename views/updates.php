<?php if($versions != FALSE) : ?>
<table id="nsm_au_updates" class="mainTable" cellspacing="0">
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
			<td scope="row"><?php print $version['addon_name']; ?></td>
			<td><?php print $version['installed_version']; ?></td>
			<td><?php print $version['latest_version']; ?></td>
			<td>
				<?php if ($version['notes']) : ?>
					<a href="#" class="note-trigger">Release notes</a>
				<?php endif; ?>
			</td>
			<td>
				<?php if ($version['docs_url']) : ?>
					<a href="<?php print $version['docs_url']; ?>" rel="external">Visit site</a></td>
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
			<td colspan="6">
				<h2><?php print $version['title']; ?></h2>
				<p>Published: <?php print $version['created_at']; ?></p>
				<?php print $version['notes']; ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php endforeach; ?>
	</tbody>
</table>

<?php else: ?>


<p>All extensions are up-to-date</p>

<?php endif; ?>
