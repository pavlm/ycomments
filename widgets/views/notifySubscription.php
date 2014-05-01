<?php
/* @var $this NotifySubscriptionWidget */
if (!Yii::app()->user->id) 
	return;
$wid = $this->id.'-'.time();
?>

<?
echo CHtml::openTag('div', array('id' => $wid));
/* @var $form TbActiveFormExt */
$form = $this->beginWidget('TbActiveFormExt', array(
	'id' => 'user-subs',
	'action'=>array('/comment/notify/userSettings'),
)); 
	echo $form->hiddenField($this->notifyUser, 'commentable_type');
	
	//echo $form->errorSummary($this->notifyUser);
	if (Yii::app()->user->hasFlash('user-notify'))
		echo CHtml::tag('div', array('class' => 'alert alert-info'), Yii::app()->user->getFlash('user-notify'));
	
?>
<div>
	<?/*?>
	<h3>Настройки подписки</h3>
	<?*/?>
	
	<?
    $ajax = CHtml::ajax([
        'type' => 'post',
        'url' => new CJavaScriptExpression('jQuery(this).parents("form").attr("action")'),
        'update' => '#'.$wid
    ]);

    if (Yii::app()->user->checkAccess('admin'))
		echo $form->checkBoxRow($this->notifyUser, 'notify_all', array(
            'id' => 'na-'.$wid,
            'onclick' => $ajax
        ));
	?>
	<? echo $form->checkBoxRow($this->notifyUser, 'notify_reply', array(
        'id' => 'nr-'.$wid,
        'onclick' => $ajax
    )); ?>
	<br><br>
	
	<?
	NotifySubscription::$commentableType = $this->commentableType; // respect dynamic relation
	$itemCount = $this->subProvider->getTotalItemCount();
	?>
	<? if ($itemCount): ?>
		<h3>Подписки на материалы</h3>
		<? 
		$this->widget('zii.widgets.CListView', 
			[
				'id' => 'subs-items-'.$this->commentableType,
				'dataProvider' => $this->subProvider, 
				'itemView' => '_notifySubItem',
				'emptyText' => 'нет подписок',
				'template' => "<table> {items}\n </table> {pager}\n{summary}",
			]);
		?>
	<? endif; ?> 
</div>

<?
$this->endWidget();
echo CHtml::closeTag('div'); 
?>