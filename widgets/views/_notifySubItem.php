<?php
/* @var $data NotifySubscription */
$ns = $data;
?>

<tr>
	<td>
	<?=$ns->item ? CHtml::link($ns->item->name, $ns->item->getSpecUrl()) : ''?>
	</td>
	<td>
	</td>
</tr>