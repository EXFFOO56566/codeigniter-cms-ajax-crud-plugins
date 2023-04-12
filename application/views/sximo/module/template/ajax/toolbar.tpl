    <div class="toolbar-line row">
    	<div class="col-md-6">
    		<div class="btn-group">		
				<?php if($this->access['is_add'] ==1) :
					echo AjaxHelpers::buttonActionCreate($pageModule,$setting);
				   endif;
				  
				if($this->access['is_remove'] ==1) : ?>		
				<a href="javascript:void(0);"  onclick="ajaxRemove('#{class}','{class}');" class="tips btn btn-xs btn-danger" title="Remove">
				<i class="fa fa-trash-o"></i>&nbsp;<?php echo $this->lang->line('core.btn_remove'); ?> </a>
				<?php endif; ?>
				<a href="{class}/flysearch" onclick="SximoModal(this.href,'Advance Search'); return false;" class="tips btn btn-xs btn-info"  title=" Search ">
				<i class="fa fa-search"></i> Search </a>
				<a href="javascript:void(0)" class="tips btn btn-xs btn-default" 
				onclick="reloadData('#{class}','{class}/data')"  title="Reload Data"><i class="fa fa-refresh"></i> Reload </a>	
			</div>
		</div>	
    	<div class="col-md-6">
    		<div class="btn-group pull-right">				
			<?php  if($access['is_excel'] ==1) :?>
				<div class="btn-group">				
				   <button type="button" class="btn btn-primary btn-xs dropdown-toggle tips"  title=" Download "
					  data-toggle="dropdown">
					  <i class="fa fa-download"></i> <?php echo $this->lang->line('core.btn_download'); ?>&nbsp; <span class="caret"></span>
				   </button>
				   <ul class="dropdown-menu" role="menu">
					  <li><a href="<?php echo site_url( '{class}/export/excel') ;?>" title="Export to Excel" > Export Excel </a></li>
					  <li><a href="<?php echo site_url( '{class}/export/word');?>"  title="Export to Word"> Export Word </a></li>
					  <li><a href="<?php echo site_url( '{class}/export/csv');?>'"   title="Export to CSV"> Export CSV</a></li>
				   </ul>

				</div> 			
			<?php endif;?>		
				<a href="<?php echo site_url( '{class}/export/print') ;?>" onclick="ajaxPopupStatic(this.href); return false;" class="tips btn btn-xs btn-info"  title=" Print ">
				<i class="fa fa-print"></i> Print </a>	

				<?php 
				if($this->session->userdata('gid') ==1) : ?>	
				<a href="<?php echo site_url('sximo/module/config/{class}') ?>" class="tips btn btn-xs btn-default"  title="Configuration">
				<i class="fa fa-cog"></i>&nbsp;<?php echo $this->lang->line('core.btn_config'); ?></a>
				<?php endif; ?>	
			</div>
		</div>
	</div>