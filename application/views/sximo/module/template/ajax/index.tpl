<div class="page-content row">
  <!-- Begin Header & Breadcrumb -->
    <div class="page-header">
      <div class="page-title">
        <h3> <?php echo $pageTitle ;?> <small><?php echo $pageNote ;?></small></h3>
      </div>
      <ul class="breadcrumb">
        <li><a href="<?php echo site_url('dashboard') ;?>">Dashboard</a></li>
        <li class="active"><?php echo $pageTitle ;?></li>
      </ul>	  	  
    </div>
	<!-- End Header & Breadcrumb -->

	<!-- Begin Content -->
	<div class="page-content-wrapper m-t">
		<div class="resultData"></div>
		<div class="ajaxLoading"></div>
		<div id="{class}View"></div>			
		<div id="{class}Grid"></div>
	</div>	
	<!-- End Content -->  
</div>	
<script>
$(document).ready(function(){
	reloadData('#{class}','{class}/data');	
});	
</script>		