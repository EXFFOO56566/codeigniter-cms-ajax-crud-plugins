<div class="table-footer">
<div class="row">
 <div class="col-sm-5">
  <div class="table-actions" style=" padding: 10px 0" id="<?php echo $pageModule;?>Filter">
   			<input type="hidden" name="page" value="<?php echo $param['page'];?>" />

   <form action='<?php echo site_url($pageModule.'/filter/') ?>' >
	   <?php  $pages = array(5,10,20,30,50)?>
	   <?php $orders = array('asc','desc') ?>
	<select name="rows" data-placeholder="Show" class="select-alt"  >
	  <option value=""> Page </option>
	  <?php foreach ($pages as $p): ?>
	  <option value="<?php echo  $p  ?>" 
		<?php if ($this->input->get('rows') == $p):  ?>
			selected="selected"
		<?php endif; 	 ?>
	  ><?php echo  $p  ?></option>
	  <?php endforeach;  ?>
	</select>
	<select name="sort" data-placeholder="Sort" class="select-alt"  >
	  <option value=""> Sort </option>	 
	  <?php foreach ($tableGrid as $field): ?>
	   <?php if ($field['view'] =='1' && $field['sortable'] =='1'):  ?>
		  <option value="<?php echo  $field['field']  ?>" 
			<?php if ($param['sort'] == $field['field']):  ?>
				selected="selected"
			<?php endif; 	 ?>
		  ><?php echo  $field['label']  ?></option>
		<?php endif; 	   ?>
	  <?php endforeach;  ?>
	 
	</select>	
	<select name="order" data-placeholder="Order" class="select-alt">
	  <option value=""> Order</option>
	   <?php foreach ($orders as $o): ?>
	  <option value="<?php echo  $o  ?>"
		<?php if ($param['order'] == $o): ?>
			selected="selected"
		<?php endif; 	 ?>
	  ><?php echo  ucwords($o)  ?></option>
	 <?php endforeach;  ?>
	</select>	
	<button type="button" class="btn  btn-primary btn-sm" onclick="ajaxFilter('#<?php echo $pageModule;?>','<?php echo $pageModule;?>/data')" style="float:left;"><i class="fa  fa-search"></i> GO</button>		
	</form>
	
  </div>					
  </div>
   <div class="col-sm-3">
	<p class="text-center" style=" padding: 25px 0">
	<?php echo  $this->lang->line('core.grid_displaying') ?> :  <b><?php //echo  $pagination  ?></b> 
	<?php echo  $this->lang->line('core.grid_to') ?> <b><?php //echo  $pagination->getTo()  ?></b> 
	<?php echo  $this->lang->line('core.grid_of') ?> <b><?php //echo  $pagination->getTotal()  ?></b>
	</p>
   </div>
	<div class="col-sm-4" id="<?php echo $pageModule;?>Paginate">			 
 	<?php echo  $pagination  ?>
 	 </div>
  </div>
</div>	

