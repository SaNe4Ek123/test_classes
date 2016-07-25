<?php
/*
	***** Описание класса *****
	Назначение: Обработка шаблонов вёрстки
	Методы: 
		- __construct ($dir), change_dir ($dir) - устанавливает директорию из которой будут браться шаблоны

		- get_dir() 				- возвращает текущую установленную директорию
		- set_expansion($exp = '') 	- устанавливает разширение файла-шаблона
		- load_tpl($name='', $description='') - загружает шаблон по имени файла (без указания расширения)
		- get_tpl($name = '')		- возвращает шаблон по переданному имени
		- parse_tpl($name = '', 
					$parse_data = array()) - обрабатывает шаблон, подставляет данные в шаблон
		- del_tpl($name = '')		- удаляет шаблон по переданному имени
		- get_tpl_names()			- Возвращает имена всех загруженных шаблонов с их описаниями
		- get_parse_tpl_names()		- Возвращает имена обработанных шаблонов с их описаниями
		- get_key_tpl($name='')		- Возвращает список всех ключевых слов в запрашиваемом шаблоне
*/

/*
	***** Что нужно изменить *****
	- Загрузка нескольких шаблонов (вдруг понадобится :) )
	- Хранение ключей в верхнем регистре. При запросе шаблона преобразовывать запрашиваемое имя.
*/
class Tpl{ # Назначение класса - работа с шаблонами, загрузка данных в подготовленные шаблоны

	private $dir = ''; 						/* Переменная для хранения директории из которой будут
											 	загружаться шаблоны */
	private $expansion = '.tpl'; 			// текущее расширение для файла-шаблона
	protected $templates = array(); 			/* массив загруженных шаблонов (!добавить некоторые 
												элементы в массив с шаблоном: описание, сам шаблон) */
	protected $parse_templates = array(); 	// массив обработанных шаблонов

	function __construct($dir=''){
		# Установка дирректории из которой будем загружать шаблоны
		# $dir - имя директории в которой хранятся шаблоны
		return $this -> change_dir($dir);
	}
//-------------------------
	function change_dir($dir=''){
		# Изменение рабочей директории
		$dir = trim($dir);
		if(!empty($dir) and is_string($dir)){
			$dir = rtrim($dir, '/').'/'; 		//--- нормализуем количество слэшей в конце указания дирректории

			$this -> dir = $dir; 				//--- записываем директорию в свойство объекта
			return true;
		}else{
			return false;
		}
	}
//---------------------------------
	function get_dir(){
		# Возвращает текущую директорию
		return $this -> dir;
	}
//---------------------------------
	function set_expansion($exp = ''){
		# устанавливает расширение используемых шаблонов
		if(!empty($exp) and is_string($exp)){
			$this -> expansion = $exp;
			return true;
		}else{
			return false;
		}
	}
//----------------------------------

	function get_expansion(){
		# Возвращает установленное расширение
		return $this -> expansion;
	}
//----------------------------------

	function load_tpl($name='', $description=''){
		# Загрузка шаблона в массив $templates
		$name = trim($name);
		if(!empty($name) and is_string($name)){
			$tpl = $this -> dir.$name.$this -> expansion; 	// формируем путь к файлу

			if (file_exists($tpl)) {						//--- Проверяем существует ли файл
				$content = file_get_contents($tpl); 		//-- если существует получаем его содержимое
				$this -> templates[$name]['tpl'] = $content; 		//--- добавляем шаблон в массив
				$this -> templates[$name]['description'] = $description;
				return true;
			}
		}
		return false;
	}
//---------------------------------
	function get_tpl($name = ''){
		# Возвращает шаблон по имени
		if(!empty($name) and is_string($name)){
			if(array_key_exists($name, $this -> templates))
				return $this -> templates[$name];
		}
		return false;
	}
//-------------------------------------
	function parse_tpl($name = '', $parse_data = array()){
		# Обрабатывает шаблон
		if(!empty($name) and is_string($name)){ 				//--- проверка имени шаблона
			if(array_key_exists($name, $this -> templates)){ 	//--- проверка наличия шаблона

				if(count($parse_data)>0){ 						//--- проверка наличия данных для замены
					$parse_tpl = $this -> templates[$name];		//--- копирование шаблона в переменную

					foreach ($parse_data as $find => $replace){
						$find = '{'.trim(strtoupper($find)).'}'; //--- форматируем ключи
						$parse_tpl = str_replace($find, $replace, $parse_tpl); //--- обрабатываем шаблон
					}
					return $this -> parse_templates[$name] = $parse_tpl; //--- возвращаем результат. Сохраняем обработанный шаблон в массив обработанных шаблонов. (шаблон перезаписывается, если он был использован более одного раза)
				}
			}
		}
		return false;
	}
//--------------------------------------
	function del_tpl($name = ''){
		# Удаляет загруженный ранее шаблон вместе с его обработанной версией.
		if(!empty($name) and is_string($name)){ 			//--- проверка имени шаблона
			if(array_key_exists($name, $this -> templates))
				unset($this -> templates[$name]);

			if(array_key_exists($name, $this -> parse_templates))
				unset($this -> parse_templates[$name]);

			return true;
		}
		return false;
	}
//---------------------------------------
//*****  Функция возврата имён всех загруженных шаблонов c их описаниями *****
	function get_tpl_names(){
		return $this -> templates;
	}

//---------------------------------------
//******* Функция возврата имён всех обработанных шаблонов ******
	function get_parse_tpl_names(){
		$list = array();
		foreach($this -> parse_templates as $name => $tpl){
			$list[$name]['tpl'] = $tpl;
			$list[$name]['description'] = $this -> templates[$name]['description'];
		}
		return $list;
	}

//---------------------------------------
//  ***** Функция возврата ключевых слов загруженного шаблона *****
	function get_key_tpl($name=''){
		if(!empty($name) and is_string($name)){
			if(array_key_exists($name, $this -> templates)){
				$key_tpl = array();

				$tpl = $this -> templates[$name]['tpl'];
				$reg = "/\{ ?[A-Z\d_]+ ?\}/";

				if (preg_match_all($reg, $tpl, $key_tpl));
				return $key_tpl;
			}
		}
		return false;
	}
}
?>