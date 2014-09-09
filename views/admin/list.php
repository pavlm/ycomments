<h1>Управление комментариями</h1>
<?php
/* @var $this AdminController */
/* @var $behavior CommentableBehavior */

$model = $types[$typeSelected]['model'];
$behavior = $model->commentable;

$ts = Yii::createComponent('zii.widgets.jui.CJuiTabs');
$ts->init();
?>

<div id="tabs" style="display:none">
	<ul>
		<? foreach ($types as $type => $meta): ?>
		<li><a href="#<?=$type?>" data-href="<?=CHtml::normalizeUrl(array('', 'type' => $type, '#' => $type))?>"><?=$type?></a></li>
		<? endforeach; ?>
	</ul>
	<? foreach ($types as $type => $meta): ?>
		<div id="<?=$type?>">
		<? if ($type == $typeSelected): ?>
		<?
		$columns = array(
				'id', 
				'message', 
				array('name' => 'created_at', 'htmlOptions' => array('style' => 'white-space:nowrap;')),
				array('header' => YCommentsModule::t('Author'), 'name' => 'user_id', 'value' => '$data->user ? $data->user->uname ." [". $data->user_id ."]" : "[". $data->user_id ."]"'), 
		);
		if ($behavior->getCommentInstance() instanceof Review) {
			$columns[] = array('name' => 'rating');
		}
		if ($behavior->commentableUrl) {
			$columns[0] = array('name' => 'id', 'value' => 'CHtml::link($data->id, $data->getUrlData())', 'type' => 'html');
		}
		
		$this->widget('zii.widgets.grid.CGridView', array(
			'dataProvider' => $provider,
			'columns' => $columns,
		)); 
		?>
		<? endif; ?>
		</div>
	<? endforeach; ?>
</div>

<script type="text/javascript">
<!--
$(function(){
	var $types = $('#tabs').tabs({}).show();
	$types.on( "tabsbeforeactivate", function( event, ui ) {
		event.preventDefault();
		var href = ui.newTab.find('a').attr('data-href');
		document.location = href;
	});
});
//-->
</script>