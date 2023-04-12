<div >
<form id="<?php echo $pageModule;?>Search">
<table class="table search-table table-striped">
	<tbody>
<?php foreach ($tableForm as $t):
	if($t['search'] =='1') : ?>
		<tr>
			<td><?php echo $t['label'];?> </td>
			<td><?php echo SiteHelpers::transForm($t['field'] , $tableForm) ;?></td>
		
		</tr>
	
	<?php endif;
	endforeach; ?>
		<tr>
			<td></td>
			<td><button type="button" name="search" class="doSearch btn btn-sm btn-primary"> Search </button></td>
		
		</tr>
	</tbody>     
	</table>
</form>	
</div>
<script>
jQuery(function(){
		$('.date').datepicker({format:'yyyy-mm-dd',autoClose:true})
		$('.datetime').datetimepicker({format: 'yyyy-mm-dd hh:ii:ss'}); 	
	$('.doSearch').click(function(){
		var attr = '';
		$( '#<?php echo $pageModule;?>Search :input').each(function() {
			if(this.value !=='' && this.name !='_token') { attr += this.name+':'+this.value+'|'; }
		});
		reloadData( '#<?php echo $pageModule;?>',"<?php echo $pageUrl;?>/data?search="+attr);	
		$('#sximo-modal').modal('hide');
	});
});

</script>
