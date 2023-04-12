<form action="<?php echo site_url('sximo/tables/tableFieldSave/'.$table) ?>" class="form-horizontal" id="columnTable" method="post">
<input type="hidden" value="<?php echo isset($field) ? $field : ''?>" name="currentfield">
	<div class="form-group">
		<label class="col-md-4">Column Name </label>
		<div class="col-md-8">
			<input type="text" name="field" value="<?php echo isset($field) ? $field : ''?>" class="form-control">
		</div>
	</div>
	<div class="form-group">
		<label class="col-md-4"> DataType </label>
		<div class="col-md-8">
	        <select name="type" class="form-control" >
				<?php foreach($tbtypes as $t): ?>
				 	<option value="<?php echo $t; ?>" <?php if(isset($type) && $type ==$t): ?> selected="selected" <?php endif; ?>><?php echo $t; ?></option>
				<?php endforeach; ?>
	        </select>	
        </div>
	</div>
	<div class="form-group">
		<label class="col-md-4">Lenght/Values </label>
		<div class="col-md-8">
			<input type="text" name="lenght" value="<?php echo isset($lenght) ? $lenght : ''?>" class="form-control">
		</div>	
	</div>
	<div class="form-group">
		<label class="col-md-4"> Default </label>
		<div class="col-md-8">
			<input type="text" name="default" value="<?php echo isset($default) ? $default : ''?>" class="form-control">
		</div>	
	</div>		

	<div class="form-group">
		<label class="col-md-4"> Option  </label>
		<div class="col-md-8">			
			<label class="checkbox"><input type="checkbox" name="null" value="1" <?php if(isset($notnull) && $notnull =='NO'): ?> checked="checked" <?php endif; ?>/> Not Null ?</label>
			<label class="checkbox"><input type="checkbox" name="key" value="1"  <?php if(isset($key) && $key =='PRI'): ?> checked="checked" <?php endif; ?>/> Primary Key  ?</label>
			<label class="checkbox"><input type="checkbox" name="ai" value="1" <?php if(isset($ai) && $ai =='auto_increment'): ?>checked="checked" <?php endif; ?> /> Autoincrement </label>
		</div>	
		
		
	</div>	

	<div class="form-group">
		<label class="col-md-4">  </label>
		<div class="col-md-8">
			<button type="submit" class="btn btn-sm btn-primary"> Save Column</button>
		</div>	
	</div>
</form>


  <script type="text/javascript">
 $(document).ready(function(){
 		var form = $('#columnTable');
		form.parsley();
		form.submit(function(){
			
			if(form.parsley('isValid') == true){			
				var options = { 
					dataType:      'json', 
					beforeSubmit :  showRequest,
					success:       showResponse  
				}  
				$(this).ajaxSubmit(options); 
				return false;
							
			} else {
				return false;
			}		
		
		});	
 });
function showRequest()
{
	$('.ajaxLoading').show();
}  
function showResponse(data)  {		
	
	if(data.status == 'success')
	{
		url = "<?php echo site_url('sximo/tables/tableConfig/'.$table) ?>";	
		$.get( url , function( data ) {
			$('#sximo-modal').modal('hide');
			$( ".tableConfig" ).html( data );
			$('.ajaxLoading').hide();
			
				
		});
	
	} else {
		alert(data.message);
	}	
	$('.ajaxLoading').hide();
} 

</script>	