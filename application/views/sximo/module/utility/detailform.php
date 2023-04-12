 <form action="<?php echo site_url($pageModule.'/quicksave/'); ?>" class='form-horizontal'  id="<?php echo $pageModule.'Form';?>"
		 parsley-validate='true' novalidate='true' method="post" > 

 <div class="table-responsive">
 <?php if(count($rowData)>=1) : ?>
    <table class="table table-striped  " id="<?php echo $pageModule ;?>Table">
        <thead>
			<tr>
				<th> No </th>				
				<?php foreach ($tableGrid as $t) :
					if($t['view'] =='1'):
						echo '<th align="'.$t['align'].'">'.SiteHelpers::activeLang($t['label'],(isset($t['language'])? $t['language'] : array())).'</th>';
					endif;
				endforeach; ?>
				<th></th>
			  </tr>
        </thead>
        <tbody>
			<?php if($access['is_add'] ==1) :?>
			<tr>
				<td> # </td>
				<?php foreach ($tableGrid as $t) :
					if($t['view'] =='1') : ?>
					<td>						
						<?php echo SiteHelpers::transForm($t['field'] , $tableForm) ;?>								
					</td>
					<?php endif; 
				endforeach ?>
				<td style="width:130px;">
					<input type="hidden" name="<?php echo $primarykey;?>" value="">
					<button type="button"  class=" btn btn-sm btn-info" rel="#<?php echo $pageModule ;?>Form" 
					onclick="ajaxInlineSave('#<?php echo $pageModule ;?>','<?php echo site_url($pageModule.'/quicksave') ;?>','<?php echo site_url($pageModule.'/detailview/form?md='.$filtermd) ;?>')">
					<i class="fa fa-check-circle"></i> Save </button>
				</td>
			  </tr>	
			<?php endif; ?>

				
           		<?php $i=0; foreach ($rowData as $row) : $n = ++$i; ?>
                <tr id="<?php echo $pageModule .'-'.$n;?>">
					<td width="50"> <?php echo $n;?>  </td>
											
					 <?php foreach ($tableGrid as $field) :
						 if($field['view'] =='1') : ?>
						 <td align="<?php echo $field['align'];?>">					 
							<?php 
							$conn = (isset($field['conn']) ? $field['conn'] : array() );
							echo AjaxHelpers::gridFormater($row->$field['field'], $row , $field['attribute'],$conn);?>	
							<span style="display:none;"><?php echo $field['field'].':'.$row->$field['field'];?></span>						 
						 </td>
						 <?php endif;					 
						endforeach; 
					  ?>
					  <td>
					  	<span style="display:none;"><?php echo $primarykey.':'.$row->$primarykey;?></span>
					  	<?php if($access['is_add'] ==1) :?>
					  	<a href="javascript:void(0)" class=" btn btn-xs btn-info" onclick="ajaxInlineEdit('#<?php echo $pageModule ;?>-<?php echo $n;?>');" 	><i class="fa fa-pencil"></i></a>
					  	<?php endif;
					  	if($access['is_remove'] ==1) :?>					  	
					  	<a href="javascript:void(0)" onclick="ajaxInlineRemove('#<?php echo $pageModule .'-'.$n ;?>','<?php echo site_url($pageModule."/removerow/".$row->$primarykey) ;?>')" class=" quickRemove btn btn-xs btn-danger" ><i class="fa fa-minus"></i></a>
					  	<?php endif; ?>
					  </td>			 
                </tr>
				
            <?php endforeach;?>
              
        </tbody>
      
    </table>
 	<?php else : ?>

	<div style="margin:100px 0; text-align:center;">
	
		<p> Please fill all master fields form </p>
	</div>
	
	<?php endif; ?>   
    </div>
</form>
	
<script type="text/javascript">
jQuery(function(){
	$('#<?php echo $pageModule;?>Form input[name="<?php echo $foreignKey;?>"]').val('<?php echo $foreignVal;?>'); $('input[name="<?php echo $foreignKey;?>"]').attr('readonly','1');

})

</script>
