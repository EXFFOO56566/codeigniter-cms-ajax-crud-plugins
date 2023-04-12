	<?php if($setting['form-method'] =='native') : ?>
	<div class="sbox">

	<div class="sbox-title">  <h4> <i class="fa fa-table"></i> <?php echo $pageTitle ;?> <small><?php echo  $pageNote ;?></small> 

	<a href="javascript:void(0)" class="collapse-close pull-right" onclick="ajaxViewClose('#{class}')"><i class="fa fa fa-times"></i></a>
	</h4>
	 </div>

	<div class="sbox-content"> 
	<?php endif;?>	


		 <form action="<?php echo site_url('{class}/save/'.$row['{key}']); ?>" class='form-{form_display}'  id="{class}FormAjax"
		 parsley-validate='true' novalidate='true' method="post" enctype="multipart/form-data" > 
		 
{form_entry}
		
			<div style="clear:both"></div>	
				
 		<div class="toolbar-line text-center">		
			<input type="submit" name="submit" class="btn btn-primary btn-sm" value="<?php echo $this->lang->line('core.sb_submit'); ?>" />
			<a href="javascript:void(0)" class="btn-sm btn btn-warning" onclick="ajaxViewClose('#{class}')"><?php echo $this->lang->line('core.sb_cancel'); ?></a>
 		</div>
			  		
		</form>

	</div>	
		<?php foreach($subgrid as $md) : ?>
		<div  id="<?php echo  $md['module'] ;?>">
			<h4><i class="fa fa-table"></i> <?php echo  $md['title'] ;?></h4>
			<div id="<?php echo  $md['module'] ;?>View"></div>
			<div class="table-responsive">
				<div id="<?php echo  $md['module'] ;?>Grid"></div>
			</div>	
		</div>
		<?php endforeach;?>	
	
	<?php if($setting['form-method'] =='native'): ?>
		</div>	
	</div>	
	<?php endif;?>	
<script>
$(document).ready(function(){
<?php foreach($subgrid as $md) : ?>
	$.post( '<?php echo site_url($md['module'].'/detailview/form?md='.$md['master'].'+'.$md['master_key'].'+'.$md['module'].'+'.$md['key'].'+'.$id) ;?>' ,function( data ) {
		$( '#<?php echo $md['module'] ;?>Grid' ).html( data );
	});		
<?php endforeach ?>
});
</script>				 
<script type="text/javascript">
$(document).ready(function() { 
	{form_javascript} 
	$('.previewImage').fancybox();	
	$('.tips').tooltip();	
	$(".select2").select2({ width:"98%"});	
	$('.date').datepicker({format:'yyyy-mm-dd',autoClose:true})
	$('.datetime').datetimepicker({format: 'yyyy-mm-dd hh:ii:ss'}); 
	$('.markItUp').markItUp(mySettings );	

	var form = $('#{class}FormAjax'); 
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
	$('.formLoading').show();	
}  
function showResponse(data)  {		
	
	if(data.status == 'success')
	{
		if(data.action =='submit')
		{
			ajaxViewClose('#{class}');	
		}	
		
		ajaxFilter('#{class}','{class}/data');
		notyMessage(data.message);		
	} else {	
		var n = noty({				
			text: data.message,
			type: 'error',
			layout: 'topRight',
		});	
		return false;
	}	
}	
</script>		 