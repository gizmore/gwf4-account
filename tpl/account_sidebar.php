<md-toolbar class="md-theme-indigo"layout-align="right">
<h1 class="md-toolbar-tools"><?php echo $lang->lang('side_title'); ?></h1>
	<md-content layout-margin ng-controller="GWFCtrl" class="gwf-account-bar">

		<div><?php echo $info_text; ?></div>
		
		<md-button href="<?php echo $href_settings; ?>"><?php echo $lang->lang('side_btn'); ?></md-button>

	</md-content>
</md-toolbar>
