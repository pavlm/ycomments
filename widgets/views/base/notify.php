<?php
/* @var $this CommentsWidget */
/* @var $user CommentUser */
/* @var $items CActiveRecord[] */
/* @var $comments Comment[] */
/* @var $groups [] */
extract($this->options);

?>
<? 
foreach ($groups as $iid => $cids):

	$item = $items[$iid];
	echo "<p>\n";
	echo CHtml::link($item->name, $item->getUrlData()), "\n";
	echo "</p>\n";

	foreach ($cids as $cid):
		/* @var $c Comment */
		echo "<p>\n";
		$c = $comments[$cid];
		echo CHtml::tag('span', array('style' => 'color:gray'), sprintf("%s, %s:  ", 
			Yii::app()->dateFormatter->format( 'HH:mm dd MMM', $c->created_at), $c->getUserName()));
		echo $c->message;
		echo "</p>", "\n\n";
	endforeach;
	
	echo "\n\n";
	
endforeach; 
?>

<p>
Отписаться от рассылки вы можете на странице своего профиля.
</p>
