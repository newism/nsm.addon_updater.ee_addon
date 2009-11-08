<?= form_open('C=addons_extensions&M=extension_settings&file=&file=nsm_addon_updater', '',
		array(
			"file" => "nsm_addon_updater",
			"Nsm_addon_updater_ext[check_for_updates]" => 0,
			"Nsm_addon_updater_ext[member_groups][]" => FALSE
		))
	?>

<?php if(validation_errors()) : ?>
	<div class="mor alert error">
		<?= validation_errors() ?>
	</div>
<?php endif; ?>

<?php if($message) : ?>
	<div class="mor alert success">
		<p><?php print($message); ?></p>
	</div>
<?php endif; ?>

<div class="nsm tg">
	<h2><?= lang('enable_extension_title') ?></h2>
	<div class="info">
		<?= str_replace("{addon_name}", $addon_name, lang('enable_extension_info')); ?>
	</div>
	<table>
		<tbody>
			<tr class="even">
				<th scope="row">
					<?= lang('enable_extension_label', 'enabled') ?>
				</th>
				<td>
					<select name="Nsm_addon_updater_ext[enabled]" id='enabled' class='toggle'>
					<option value="1"<?= $settings['enabled'] ? "selected='selected'" : "" ?>><?=lang('yes')?></option>
					<option value="0"<?= !$settings['enabled'] ? "selected='selected'" : "" ?>><?=lang('no')?></option>
					</select>
				</td>
			</tr>
			<tr class="odd">
				<th scope="row">
					<?= lang('cache_expiration_label', 'cache_expiraton') ?>
				</th>
				<td<?= form_error('Nsm_addon_updater_ext[cache_expiration]') ? " class='error'" : ""?>>
					<?= form_error('Nsm_addon_updater_ext[cache_expiration]'); ?>
					<input
						type="text"
						name="Nsm_addon_updater_ext[cache_expiration]"
						id='cache_expiration'
						value='<?= form_prep($settings['cache_expiration']); ?>'
					>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="nsm tg">
	<h2><?= lang('extension_access_title') ?></h2>
	<table>
		<tbody>
			<tr class="even">
				<th><?= lang('member_group_access_label') ?></th>
				<td>
				<?php foreach($member_groups as $member_group) :?>
				<?= lang($member_group['group_title'], 'member_group-' . $member_group['group_id']) ?>
				<input
					type='checkbox'
					name='Nsm_addon_updater_ext[member_groups][<?= $member_group['group_id'] ?>][show_notification]'
					id='member_group-<?= $member_group['group_id'] ?>'
					value='1'
					<?= (isset($settings['member_groups'][$member_group['group_id']]['show_notification'])) ? "checked='checked'" : ""; ?>
				/>
				<?php endforeach; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<input type='submit' value='<?= lang('save_extension_settings'); ?>' />

<?= form_close(); ?>