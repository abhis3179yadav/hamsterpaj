<?php
	$options['output'] .= '<form action="/quicksearch.php">' . "\n";
	$options['output'] .= '	<input type="text" id="ui_multisearch" value="Sök tjockis..." name="search" />' . "\n";
	$options['output'] .= '	<input type="hidden" name="type" value="user" />' . "\n";
	$options['output'] .= '	<input type="submit" value="" class="button_magnifier" />' . "\n";
	$options['output'] .= '</form>' . "\n";
?>