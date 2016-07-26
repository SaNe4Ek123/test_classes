<?php 
header('Content-Type: text/html; charset= utf-8');
require_once ('classes/Tpl.class.php');
require_once ('classes/Tpl_html.class.php');

$menu = array(
	'Главная' => '#',
	'Цены' => '#',
	'Партнёры' => '#',
	'Контакты' => '#',
	'Популярные бренды' => '#',
	'Избранное' => '#');
$data = array();

$tpl = new Tpl_html('html/');
$a = $tpl -> load_html('test_2');

foreach($menu as $item => $link){
	$data[] = array(
		'ITEM' => $item,
		'LINK' => $link);
}
$tpl -> multi_parse_tpl('header_menu', $data);
echo $tpl -> get_parse_html();
?>