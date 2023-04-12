<script type="text/javascript" src="<?php echo base_url('sximo/js/simpleclone.js') ?>"></script>
<div class="page-content row">  

	<div class="page-header"> <!-- Page header -->
	    <div class="page-title">
	        <h3> <i class="icon-database text-danger"></i> Database Tables  <small> Manage Database Tables </small></h3>
	    </div>    
	          
	  	<ul class="breadcrumb">
	  	    <li><a href="<?php echo site_url('dashboard') ?>"> <?php echo $this->lang->line('core.home'); ?> </a></li>
	  	    <li><a href="<?php echo site_url('sximo/module') ?>"> Module </a></li>
	  	    <li class="active"> Database Tables </li>
	  	</ul>      
	            
	</div><!-- /Page header -->

	<div class="page-content-wrapper m-t">
		<div class="sbox">

			<div class="sbox-title">
				<i class="icon-database"></i> All Tables  
				<span class="pull-right">
					<a href="<?php echo site_url('sximo/tables/tableConfig/') ?>" class="btn btn-xs btn-primary linkConfig"><i class="fa fa-plus"></i> New Table </a>
					<a href="<?php echo site_url('sximo/tables/mysqlEditor/') ?>" class="btn btn-xs btn-success linkConfig"><i class="fa fa-pencil"></i> MySQL Editor </a>
				</span>
			</div>

			<div class="sbox-content">
				<div class="row">
				  <div class="col-md-3">
				  		<form action="<?php echo site_url('sximo/tables/tableRemove/') ?>" class="form-horizontal" id="removeTable" method="post">
				  			<div class="table-responsive">
				  				<table class="table table-striped">
				  					<thead>
				  						<tr>
				  							<th width="30"> <input type="checkbox" class="checkall i-checks-all " /></th>
											<th> Table Name </th>
											<th width="50"> Action </th>
				  						</tr>
				  					</thead>				  			
				  					<tbody>
				  					<?php foreach($tables as $table): ?>   	  			
				  						<tr>
				  							<td><input type="checkbox" class="ids  i-checks" name="id[]" value="<?php echo $table; ?>" /> </td>
											<td><a href="<?php echo site_url('sximo/tables/tableConfig/'.$table)?>" class="linkConfig" > <?php echo $table; ?></a></td>
											<td>
												<a href="javascript:void(0)" onclick="droptable()" class="btn btn-xs btn-danger"><i class="fa fa-minus"></i></a>
											</td>
				  						</tr>
				  					<?php endforeach; ?>
				  					</tbody>
				  				</table>
				  			</div>
				  		</form>				  
				  </div><!-- /<div class="col-md-3"> -->


				   <div class="col-md-9">
				   		<div class="tableConfig" style="background:#fff; padding:10px; min-height:300px; border:solid 1px #ddd;">

				   		</div>
				  </div><!-- /<div class="col-md-9"> -->


				</div><!-- /<div class="row"> -->

			</div><!-- /sbox-content -->

		</div><!-- /sbox -->	
	</div><!-- page-content-wrapper -->

<script type="text/javascript">
$(document).ready(function(){

	$('.linkConfig').click(function(){
		$('.ajaxLoading').show();
		var url =  $(this).attr('href');
		$.get( url , function( data ) {
			$( ".tableConfig" ).html( data );
			$('.ajaxLoading').hide();
			
			
		});
		return false;
	});
});

function droptable()
{
	if(confirm('are you sure remove selected table(s) ?'))
	{
		$('#removeTable').submit();
	} else {
		return false;
	}
}

</script>
</div> <!-- /page-content-row -->