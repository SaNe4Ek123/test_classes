<?php 
header('Content-Type: text/html; charset= utf-8');
require_once ('classes/Tpl.class.php');
require_once ('classes/Tpl_html.class.php');

$tpl = new Tpl_html('html/');
$a = $tpl -> load_html('test_html');
echo $tpl -> get_parse_html(true);
print_r($tpl -> get_tpl('FOOTER'));

?>