<?php
//!!! Добавить возможность добавления нескольких наборов данных в шаблон... 
//!!! Сделать преобразование в верхний входных ключей шаблонов
/*
	****** Методы класса ******

	- __construct($dir = '') - устанавливает дирректорию, устанавливает расширение .html
	- load_html($file_name = '') - Загружает подготовленный html-шаблон, разбивает его на фрагменты
	- get_tpl_html() - Возвращает необработанный html шаблон
	- get_parse_html($comment=false) - Возвращает обработанный html шаблон. Можно включать и отключать комментарии

*/

class Tpl_html extends Tpl{
	# ---- Свойства класса ----
	protected $html_load = '';		//---   Загруженный html шаблон
	protected $html_parse = '';		//--- Обработанный html шаблон
	protected $html_parse_tpl = array();
	
# Конструктор класса переопределяет рарасширение принимаемого файла на HTML и устанавливает дирректорию 

	function __construct($dir = ''){
		$this -> set_expansion('.html');	//--- Переопределяем в конструкторе расширение файла
		return $this -> change_dir($dir); 
	}
//----------------------------------
# Загрузка и обработка html шаблона

	function load_html($file_name = ''){

		$file_name = trim($file_name);	//--- на всякий случай убираем пробелы

		if(!empty($file_name) and is_string($file_name)){  //--- Проверяем корректность переданного пргумента
			//--- Формируем доступ к файлу
			$dir = $this -> get_dir();
			$exp = $this -> get_expansion();
			$file = $dir.$file_name.$exp;

			//--- Если файл существует сохраняем его в соответствующие поля.
			if(file_exists($file)){
				$this -> html_load = $this -> html_parse = file_get_contents($file);

				//--- Обрабатываем загруженный шаблон - разбиваем его на блоки соглассно фармату
				if($tpl_names = $this ->get_names_tpl($this -> html_load)){ 
					$this -> html_divide_by_name($tpl_names);
					
					return true;
				}
			}
		}
		return false;
	}
//----------------------------------

# Возвращает не обработанный html шаблон
	function get_tpl_html(){
		return $this -> html_load;
	}

//-----------------------------------

# Возвращает обработанный шаблон
	function get_parse_html($comment = false){
		if($comment === false){
			# Убираем все комментарии из страницы
			$html = $this -> parse_html();
			$reg = "/(\<\!-- *?\>\> *?[A-Z_\d]+ *?\( ?([\pL \d\-_]+)? *?\) *?--\>(\r\n)?)|(\<\!-- *?[A-Z_\d]+ ?\<\< *?--\>(\r\n)?)/u";
			$html = preg_replace($reg, '', $html);
			return $html;
		}else{
			return $this -> parse_html();
		}
	}

//--------------------------------------------------

	# Множественная обработка шаблона
	function multi_parse_tpl($name = '', $data = ''){
		if (!empty($name) and is_string($name) and is_array($data) and count($data)>0) {
			$tpl = $this -> get_tpl($name);
			$parse_tpl = '';
			foreach ($data as $input) {
				$parse_tpl .= $this -> parse_tpl($name, $input)['tpl'];
			}
			return $this -> html_parse_tpl[$name] = $parse_tpl;
		}
		return false;
	}

//--------*** Вспомогательные методы класса (Закрытые) ***-------------

# Создаёт шаблон из обработанных кусочков (создаёт web-страницу)
	private function parse_html($tpl_names = ''){
		if(empty($tpl_names)){
			$tpl_names = $this -> html_parse_tpl;
		}

		//--- Ищем и заменяем якоря на подготовленные шаблоны
		foreach($tpl_names as $name => $tpl){
			foreach($tpl_names as $n => $parse_tpl){

				if(strstr($parse_tpl, '{_'.$name.'_}'))
				$tpl_names[$n] = str_replace('{_'.$name.'_}', $tpl, $parse_tpl);
			}
		}
		//--- Заменяем якоря до тех пор пока они не закончатся
		if(preg_match("/\{_[A-Z_\d]+_\}/", end($tpl_names))){
			$this -> parse_html($tpl_names);
		}

		$this -> html_parse = end($tpl_names);
		return $this -> html_parse;
	}

//------------------------------------

# Возвращает массив с именами шаблонов на обрабатываемой странице
	private function get_names_tpl($html = ''){
		if(!empty($html) and is_string($html)){
			$tpl_names = array();

			//--- Ищем по регулярному выражению комментарии с именем шаблона.
			$reg_tpl_raw_name = "/\<\!-- ?\>\> [A-Z_\d]+/u"; 
			if(preg_match_all($reg_tpl_raw_name, $html, $arr)){
				foreach($arr[0] as $value){

					//--- Выбираем имена из комментариев, добавляем их в массив.
					if(preg_match("/[A-Z_\d]+/", $value, $tpl_name)){
						$tpl_names[] = trim($tpl_name[0]);
					}
				}
				//--- Возвращаем массив с именами шаблонов
				return $tpl_names;
			}
		}
		return false;
	}

//---------------------------------------

# Разделяет основной html шаблон по именам
	private function html_divide_by_name($names = ''){
		if(is_array($names) and count($names)>0){

			//--- Инициализируем переменные
			$names = array_reverse($names); //--- реверсируем массив для поиска шаблонов начиная с конца страницы. (это нужно для корректной установке якорей)
			$html = $this -> html_load;

			foreach ($names as $name) {

				//--- Распознаём блоки по регулярному выражению
				$reg_tpl = "/\t*?\<\!-- \>\> ?".$name."[\w\W\s\d]+".$name." ?\<\< --\>/um";
				if(preg_match($reg_tpl, $html, $tpl)){
					
					//--- выделяем описание шаблона
					$reg_tpl_description = "/".$name." ?\( ?([\w\s\d\>\<]+)? ?\)/u";
					if(preg_match($reg_tpl_description, $tpl[0], $desc))
						$desc = preg_replace("/[A-Z_\d_]+ ?/", '', $desc[0]);

					//--- В основном шаблоне устанавливаем якоря вместо выделенного блока.
					$html = str_replace($tpl[0], '{_'.$name.'_}', $html);

					//--- Отделяем шаблон от комментариев.
					 # Ищем начальный комментарий
					$reg_coment_1 = "/\t*?\<\!-- *?\>\> *?[A-Z_\d]+ *?\( ?([\pL \d\-_]+)? *?\) *?--\>(\r\n)?/u";
					if(preg_match($reg_coment_1, $tpl[0], $comment_1))
						$tpl[0] = preg_replace($reg_coment_1, '', $tpl[0]);

						# Ищем конечный комментарий
					$reg_coment_2 = "/\t*?\<\!-- *?[A-Z_\d]+ ?\<\< *?--\>(\r\n)?/u";
					if(preg_match($reg_coment_2, $tpl[0], $comment_2))
						$tpl[0] = preg_replace($reg_coment_2, '', $tpl[0]);
					
					//--- Сохраняем всё в массиве
					//$this -> templates[$name]['comment'] = $comment_1[0].'{template}'.$comment_2[0];
					$this -> templates[$name]['tpl'] = $tpl[0];
					$this -> templates[$name]['description'] = trim($desc, '() ');
					$this -> html_parse_tpl[$name]=$tpl[0];
				}
			}
		}
		return false;
	}

//-------------------------------------------
} 

?>