<?php
/* @var $data NotifySubscription */
$ns = $data;
$item = $ns->item;
?>

<tr>
	<td>
	<?=$item ? CHtml::link($item->name, $item->getCommentableUrl()) : ''?>
	</td>
	<td>
	</td>
</tr>