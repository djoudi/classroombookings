<div class="grid_12">

	<?php $this->load->view('default/groups/index/filter') ?>

	<?php if ($groups): ?>

	<table class="list groups" id="groups">
		
		<thead>
			<th><?php echo lang('name') ?></th>
			<th><?php echo lang('groups_num_users') ?></th>
			<th></th>
		</thead>
		
		<tbody>
			
			<?php foreach ($groups as $g): ?>
			
			<tr>
				
				<td class="title">
					<?php echo anchor('groups/set/' . $g['g_id'], $g['g_name']) ?>
				</td>
				
				<td><?php echo $g['user_count'] ?></td>
				
				<td class="text-right"><?php echo group_delete($g) ?></td>
				
			</tr>
			
			<?php endforeach; ?>
			
		</tbody>
		
	</table>
	
	<div class="pagination-wrapper">
		<?php echo $this->pagination->create_links() ?>
	</div>

	<?php else: ?>

	<p class="no-results"><?php echo lang('groups_none') ?></p>
		
	<?php endif; ?>

</div>