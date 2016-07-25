<?php
//***** ВАЖНО *****
//	Необходимо задокументировать класс, пока он не оброс до неузнаваемости.

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
	
	function __construct($dir = ''){
		$this -> set_expansion('.html');	//--- Переопределяем в конструкторе расширение файла
		return $this -> change_dir($dir);
	}
//----------------------------------

	function load_html($file_name = ''){
		# Загрузка и обработка html шаблона

		$file_name = trim($file_name);
		if(!empty($file_name) and is_string($file_name)){
			$dir = $this -> get_dir();
			$exp = $this -> get_expansion();
			$file = $dir.$file_name.$exp;

			if(file_exists($file)){
				$this -> html_load = $this -> html_parse = file_get_contents($file);

				if($tpl_names = $this ->get_names_tpl($this -> html_load)){
					$this -> html_divide_by_name($tpl_names);
					
					return true;
				}
			}
		}
		return false;
	}
//----------------------------------

	function get_tpl_html(){
		# Возвращает не обработанный html шаблон
		return $this -> html_load;
	}

//-----------------------------------
	function get_parse_html($comment = false){
		# Возвращает обработанный шаблон
		if($comment === false){
			# Убираем все комментарии из страницы
			$html = $this -> parse_html();
			$reg = "/(\<\!-- *?\>\> *?[A-Z_\d]+ *?\( ?[\pL \d\-_]+ *?\) *?--\>(\r\n)?)|(\<\!-- *?[A-Z_\d]+ ?\<\< *?--\>(\r\n)?)/u";
			$html = preg_replace($reg, '', $html);
			return $html;
		}else{
			return $this -> parse_html();
		}
	}

//-----------------------------------
	// !!! Необходимо другое решение данной задачи.
	private function parse_html($tpl_names = ''){
		# Создаёт шаблон из обработанных кусочков
		if(empty($tpl_names)){
			$tpl_names = $this -> get_tpl_names();
		}

		foreach($tpl_names as $name => $tpl){
			foreach($tpl_names as $n => $parse_tpl){
				if(strstr($parse_tpl['tpl'], '{_'.$name.'_}'))
				$tpl_names[$n]['tpl'] = str_replace('{_'.$name.'_}', $tpl['tpl'], $parse_tpl['tpl']);
			}
		}
		if(preg_match("/\{_[A-Z_\d]+_\}/", end($tpl_names)['tpl'])){
			$this -> html_parse = $this -> parse_html($tpl_names);
		}
		return $this -> html_parse;
	}

//------------------------------------
	private function get_names_tpl($html = ''){
		# Возвращает массив с именами шаблонов.
		if(!empty($html) and is_string($html)){
			$tpl_names = array();
			$reg_tpl_raw_name = "/\<\!-- ?\>\> [A-Z_\d]+/u";

			if(preg_match_all($reg_tpl_raw_name, $html, $arr)){
				foreach($arr[0] as $value){
					if(preg_match("/[A-Z_\d]+/", $value, $tpl_name)){
						$tpl_names[] = trim($tpl_name[0]);
					}
				}
				return $tpl_names;
			}
		}
		return false;
	}

//---------------------------------------
	private function html_divide_by_name($names = ''){
		# Разделяет основной html шаблон по именам
		if(is_array($names) and count($names)>0){
			$names = array_reverse($names);
			$html = $this -> html_load;

			foreach ($names as $name) {

				$reg_tpl = "/\t*?\<\!-- \>\> ?".$name."[\w\W\s\d]+".$name." ?\<\< --\>/um";

				if(preg_match($reg_tpl, $html, $result)){
					
					$reg_tpl_description = "/".$name." ?\( ?[\w\s\d\>\<]+ ?\)/u";
					preg_match($reg_tpl_description, $result[0], $desc);

					$desc = preg_replace("/[A-Z_\d_]+ ?/", '', $desc[0]);

					$html = str_replace($result[0], '{_'.$name.'_}', $html);

					$this -> templates[$name]['tpl'] = $result[0];
					$this -> templates[$name]['description'] = trim($desc, '() ');
				}
			}
		}
		return false;
	}

} 
	/*
	class Tpl_from_html - класс наследник от класса Tpl
	*** назначение - обработка цельных html шаблонов, специально отформатированных под текущий класс
		- Цель данного класса устранить нагромождение файлов шаблонов, ограничиваясь лишь шаблонами конкретных страниц, таких как например, главная страница или страница с товарами или ещё какая другая страница. 
		- Также ожидается, что в файловой системе станет больше порядка, а html страницы останутся цельными и читабельными, что по идее должно облегчить их редактирование (структура и т.п.)

		- класс инициализирует шаблоны на лету и хранит их в массиве объекта.
		- шаблоны могут вкладываться в шаблоны, для этого предусмотренны специальные метки.
		- есть возможность посмотреть всю страницу на любом этапе её формирования (даже если в шаблон внесены не все данные)
		- Каждый отдельный шаблон - это объект, хранящийся в массиве с ключём имени шаблона. T. e. редактируя шаблон, мы имеем дело с объектом свойствами которого являются ключи для вставки данных + поле 'description' - с описанием шаблона.

		*** Задачи ***
	- продумать взаимодействие шаблонов при перелинковке страниц.
	- продумать метки для форматирования html файлов.
	- учесть вероятность подгрузки контента без перезагрузки страницы. (возможно найдётся способ реализовать и эту функцию используя этот класс)


	*/
?>