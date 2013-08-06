<?php

date_default_timezone_set('Europe/Paris');
//error_reporting(0);

global $_CONFIG;
$_CONFIG['data'] = 'data';
$_CONFIG['database'] = $_CONFIG['data'].'/movies.php';
$_CONFIG['settings'] = $_CONFIG['data'].'/settings.php';
$_CONFIG['images'] = $_CONFIG['data'].'/images';
$_CONFIG['cache'] = 'cache';
$_CONFIG['title'] = 'Movies';
$_CONFIG['url_rewriting'] = FALSE;
$_CONFIG['flags'] = array(
	'USA' => 'us',
	'France' => 'fr'
);

define('PHPPREFIX','<?php /* '); 
define('PHPSUFFIX',' */ ?>');
define('MOVIES_VERSION', '0.1 bêta');
define('INACTIVITY_TIMEOUT', 3600);

// Force cookie path (but do not change lifetime)
$cookie = session_get_cookie_params();
$cookiedir = ''; if(dirname($_SERVER['SCRIPT_NAME'])!='/') $cookiedir=dirname($_SERVER["SCRIPT_NAME"]).'/';
session_set_cookie_params($cookie['lifetime'],$cookiedir,$_SERVER['HTTP_HOST']);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', FALSE);


if (!is_dir($_CONFIG['data'])) { mkdir($_CONFIG['data'],0705); chmod($_CONFIG['data'],0705); }
if (!is_dir($_CONFIG['cache'])) { mkdir($_CONFIG['cache'],0705); chmod($_CONFIG['cache'],0705); }
if (!is_dir($_CONFIG['images'])) { mkdir($_CONFIG['images'],0705); chmod($_CONFIG['images'],0705); }

//ob_start();
$tpl = new RainTPL();

if (!is_file($_CONFIG['settings'])) {define('TITLE', $_CONFIG['title']); install($tpl);}
require($_CONFIG['settings']);
define('TITLE', $_CONFIG['title']);

/**
 * Rain class
 * @version 2.7.2
 */
class RainTPL {static $tpl_dir="templates/";static $cache_dir="cache/";static $base_url=null;static $tpl_ext="rain";static $path_replace=false;static $path_replace_list=array('a','img','link','script','input');static $black_list=array('\$this','raintpl::','self::','_SESSION','_SERVER','_ENV','eval','exec','unlink','rmdir');static $check_template_update=true;static $php_enabled=false;static $debug=false;public $var=array();protected $tpl=array(),$cache=false,$cache_id=null;protected static $config_name_sum=array();const CACHE_EXPIRE_TIME=3600;function assign($variable,$value=null){if(is_array($variable))$this->var+=$variable;else $this->var[$variable]=$value;}function draw($tpl_name,$return_string=false){try{$this->check_template($tpl_name);}catch(RainTpl_Exception $e){$output=$this->printDebug($e);die($output);}if(!$this->cache&&!$return_string){extract($this->var);include $this->tpl['compiled_filename'];unset($this->tpl);}else{ob_start();extract($this->var);include $this->tpl['compiled_filename'];$raintpl_contents=ob_get_clean();if($this->cache)file_put_contents($this->tpl['cache_filename'],"<?php if(!class_exists('raintpl')){exit;}?>".$raintpl_contents);unset($this->tpl);if($return_string)return $raintpl_contents;else echo $raintpl_contents;}}function cache($tpl_name,$expire_time=self::CACHE_EXPIRE_TIME,$cache_id=null){$this->cache_id=$cache_id;if(!$this->check_template($tpl_name)&&file_exists($this->tpl['cache_filename'])&&(time()-filemtime($this->tpl['cache_filename'])<$expire_time))return substr(file_get_contents($this->tpl['cache_filename']),43);else{if(file_exists($this->tpl['cache_filename']))unlink($this->tpl['cache_filename']);$this->cache=true;}}static function configure($setting,$value=null){if(is_array($setting))foreach($setting as $key=>$value)self::configure($key,$value);else if(property_exists(__CLASS__,$setting)){self::$$setting=$value;self::$config_name_sum[$setting]=$value;}}protected function check_template($tpl_name){if(!isset($this->tpl['checked'])){$tpl_basename=basename($tpl_name);$tpl_basedir=strpos($tpl_name,"/")?dirname($tpl_name).'/':null;$tpl_dir=self::$tpl_dir.$tpl_basedir;$this->tpl['tpl_filename']=$tpl_dir.$tpl_basename.'.'.self::$tpl_ext;$temp_compiled_filename=self::$cache_dir.$tpl_basename.".".md5($tpl_dir.serialize(self::$config_name_sum));$this->tpl['compiled_filename']=$temp_compiled_filename.'.rtpl.php';$this->tpl['cache_filename']=$temp_compiled_filename.'.s_'.$this->cache_id.'.rtpl.php';if(self::$check_template_update&&!file_exists($this->tpl['tpl_filename'])){$e=new RainTpl_NotFoundException('Template '.$tpl_basename.' not found!');throw $e->setTemplateFile($this->tpl['tpl_filename']);}if(!file_exists($this->tpl['compiled_filename'])||(self::$check_template_update&&filemtime($this->tpl['compiled_filename'])<filemtime($this->tpl['tpl_filename']))){$this->compileFile($tpl_basename,$tpl_basedir,$this->tpl['tpl_filename'],self::$cache_dir,$this->tpl['compiled_filename']);return true;}$this->tpl['checked']=true;}}protected function xml_reSubstitution($capture){return "<?php echo '<?xml ".stripslashes($capture[1])." ?>'; ?>";}protected function compileFile($tpl_basename,$tpl_basedir,$tpl_filename,$cache_dir,$compiled_filename){$this->tpl['source']=$template_code=file_get_contents($tpl_filename);$template_code=preg_replace("/<\?xml(.*?)\?>/s","##XML\\1XML##",$template_code);if(!self::$php_enabled)$template_code=str_replace(array("<?","?>"),array("&lt;?","?&gt;"),$template_code);$template_code=preg_replace_callback("/##XML(.*?)XML##/s",array($this,'xml_reSubstitution'),$template_code);$template_compiled="<?php if(!class_exists('raintpl')){exit;}?>".$this->compileTemplate($template_code,$tpl_basedir);$template_compiled=str_replace("?>\n","?>\n\n",$template_compiled);if(!is_dir($cache_dir))mkdir($cache_dir,0755,true);if(!is_writable($cache_dir))throw new RainTpl_Exception('Cache directory '.$cache_dir.'doesn\'t have write permission. Set write permission or set RAINTPL_CHECK_TEMPLATE_UPDATE to false. More details on http://www.raintpl.com/Documentation/Documentation-for-PHP-developers/Configuration/');file_put_contents($compiled_filename,$template_compiled);}protected function compileTemplate($template_code,$tpl_basedir){$tag_regexp=array('loop'=>'(\{loop(?: name){0,1}="\${0,1}[^"]*"\})','loop_close'=>'(\{\/loop\})','if'=>'(\{if(?: condition){0,1}="[^"]*"\})','elseif'=>'(\{elseif(?: condition){0,1}="[^"]*"\})','else'=>'(\{else\})','if_close'=>'(\{\/if\})','function'=>'(\{function="[^"]*"\})','noparse'=>'(\{noparse\})','noparse_close'=>'(\{\/noparse\})','ignore'=>'(\{ignore\}|\{\*)','ignore_close'=>'(\{\/ignore\}|\*\})','include'=>'(\{include="[^"]*"(?: cache="[^"]*")?\})','template_info'=>'(\{\$template_info\})','function'=>'(\{function="(\w*?)(?:.*?)"\})');$tag_regexp="/".join("|",$tag_regexp)."/";$template_code=preg_split($tag_regexp,$template_code,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);$template_code=$this->path_replace($template_code,$tpl_basedir);$compiled_code=$this->compileCode($template_code);return $compiled_code;}protected function compileCode($parsed_code){$compiled_code=$open_if=$comment_is_open=$ignore_is_open=null;$loop_level=0;while($html=array_shift($parsed_code)){if(!$comment_is_open&&(strpos($html,'{/ignore}')!==FALSE||strpos($html,'*}')!==FALSE))$ignore_is_open=false;elseif($ignore_is_open){}elseif(strpos($html,'{/noparse}')!==FALSE)$comment_is_open=false;elseif($comment_is_open)$compiled_code.=$html;elseif(strpos($html,'{ignore}')!==FALSE||strpos($html,'{*')!==FALSE)$ignore_is_open=true;elseif(strpos($html,'{noparse}')!==FALSE)$comment_is_open=true;elseif(preg_match('/\{include="([^"]*)"(?: cache="([^"]*)"){0,1}\}/',$html,$code)){$include_var=$this->var_replace($code[1],$left_delimiter=null,$right_delimiter=null,$php_left_delimiter='".',$php_right_delimiter='."',$loop_level);if(isset($code[2])){$compiled_code.='<?php $tpl = new '.get_class($this).';'.'if( $cache = $tpl->cache( $template = basename("'.$include_var.'") ) )'.'	echo $cache;'.'else{'.'	$tpl_dir_temp = self::$tpl_dir;'.'	$tpl->assign( $this->var );'.(!$loop_level?null:'$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );').'	$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'") );'.'} ?>';}else{$compiled_code.='<?php $tpl = new '.get_class($this).';'.'$tpl_dir_temp = self::$tpl_dir;'.'$tpl->assign( $this->var );'.(!$loop_level?null:'$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );').'$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'") );'.'?>';}}elseif(preg_match('/\{loop(?: name){0,1}="\${0,1}([^"]*)"\}/',$html,$code)){$loop_level++;$var=$this->var_replace('$'.$code[1],$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level-1);$counter="\$counter$loop_level";$key="\$key$loop_level";$value="\$value$loop_level";$compiled_code.="<?php $counter=-1; if( isset($var) && is_array($var) && sizeof($var) ) foreach( $var as $key => $value ){ $counter++; ?>";}elseif(strpos($html,'{/loop}')!==FALSE){$counter="\$counter$loop_level";$loop_level--;$compiled_code.="<?php } ?>";}elseif(preg_match('/\{if(?: condition){0,1}="([^"]*)"\}/',$html,$code)){$open_if++;$tag=$code[0];$condition=$code[1];$this->function_check($tag);$parsed_condition=$this->var_replace($condition,$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php if( $parsed_condition ){ ?>";}elseif(preg_match('/\{elseif(?: condition){0,1}="([^"]*)"\}/',$html,$code)){$tag=$code[0];$condition=$code[1];$parsed_condition=$this->var_replace($condition,$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php }elseif( $parsed_condition ){ ?>";}elseif(strpos($html,'{else}')!==FALSE){$compiled_code.='<?php }else{ ?>';}elseif(strpos($html,'{/if}')!==FALSE){$open_if--;$compiled_code.='<?php } ?>';}elseif(preg_match('/\{function="(\w*)(.*?)"\}/',$html,$code)){$tag=$code[0];$function=$code[1];$this->function_check($tag);if(empty($code[2]))$parsed_function=$function."()";else $parsed_function=$function.$this->var_replace($code[2],$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php echo $parsed_function; ?>";}elseif(strpos($html,'{$template_info}')!==FALSE){$tag='{$template_info}';$compiled_code.='<?php echo "<pre>"; print_r( $this->var ); echo "</pre>"; ?>';}else{$html=$this->var_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);$html=$this->const_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);$compiled_code.=$this->func_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);}}if($open_if>0){$e=new RainTpl_SyntaxException('Error! You need to close an {if} tag in '.$this->tpl['tpl_filename'].' template');throw $e->setTemplateFile($this->tpl['tpl_filename']);}return $compiled_code;}protected function reduce_path($path){$path=str_replace("://","@not_replace@",$path);$path=str_replace("//","/",$path);$path=str_replace("@not_replace@","://",$path);return preg_replace('/\w+\/\.\.\//','',$path);}protected function path_replace($html,$tpl_basedir){if(self::$path_replace){$tpl_dir=self::$base_url.self::$tpl_dir.$tpl_basedir;$path=$this->reduce_path($tpl_dir);$exp=$sub=array();if(in_array("img",self::$path_replace_list)){$exp=array('/<img(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<img(.*?)src=(?:")([^"]+?)#(?:")/i','/<img(.*?)src="(.*?)"/','/<img(.*?)src=(?:\@)([^"]+?)(?:\@)/i');$sub=array('<img$1src=@$2://$3@','<img$1src=@$2@','<img$1src="'.$path.'$2"','<img$1src="$2"');}if(in_array("script",self::$path_replace_list)){$exp=array_merge($exp,array('/<script(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<script(.*?)src=(?:")([^"]+?)#(?:")/i','/<script(.*?)src="(.*?)"/','/<script(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<script$1src=@$2://$3@','<script$1src=@$2@','<script$1src="'.$path.'$2"','<script$1src="$2"'));}if(in_array("link",self::$path_replace_list)){$exp=array_merge($exp,array('/<link(.*?)href=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<link(.*?)href=(?:")([^"]+?)#(?:")/i','/<link(.*?)href="(.*?)"/','/<link(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<link$1href=@$2://$3@','<link$1href=@$2@','<link$1href="'.$path.'$2"','<link$1href="$2"'));}if(in_array("a",self::$path_replace_list)){$exp=array_merge($exp,array('/<a(.*?)href=(?:")(http\:\/\/|https\:\/\/|javascript:)([^"]+?)(?:")/i','/<a(.*?)href="(.*?)"/','/<a(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<a$1href=@$2$3@','<a$1href="'.self::$base_url.'$2"','<a$1href="$2"'));}if(in_array("input",self::$path_replace_list)){$exp=array_merge($exp,array('/<input(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<input(.*?)src=(?:")([^"]+?)#(?:")/i','/<input(.*?)src="(.*?)"/','/<input(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<input$1src=@$2://$3@','<input$1src=@$2@','<input$1src="'.$path.'$2"','<input$1src="$2"'));}return preg_replace($exp,$sub,$html);}else return $html;}function const_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){return preg_replace('/\{\#(\w+)\#{0,1}\}/',$php_left_delimiter.($echo?" echo ":null).'\\1'.$php_right_delimiter,$html);}function func_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){preg_match_all('/'.'\{\#{0,1}(\"{0,1}.*?\"{0,1})(\|\w.*?)\#{0,1}\}'.'/',$html,$matches);for($i=0,$n=count($matches[0]);$i<$n;$i++){$tag=$matches[0][$i];$var=$matches[1][$i];$extra_var=$matches[2][$i];$this->function_check($tag);$extra_var=$this->var_replace($extra_var,null,null,null,null,$loop_level);$is_init_variable=preg_match("/^(\s*?)\=[^=](.*?)$/",$extra_var);$function_var=($extra_var and $extra_var[0]=='|')?substr($extra_var,1):null;$temp=preg_split("/\.|\[|\-\>/",$var);$var_name=$temp[0];$variable_path=substr($var,strlen($var_name));$variable_path=str_replace('[','["',$variable_path);$variable_path=str_replace(']','"]',$variable_path);$variable_path=preg_replace('/\.\$(\w+)/','["$\\1"]',$variable_path);$variable_path=preg_replace('/\.(\w+)/','["\\1"]',$variable_path);if($function_var){$function_var=str_replace("::","@double_dot@",$function_var);if($dot_position=strpos($function_var,":")){$function=substr($function_var,0,$dot_position);$params=substr($function_var,$dot_position+1);}else{$function=str_replace("@double_dot@","::",$function_var);$params=null;}$function=str_replace("@double_dot@","::",$function);$params=str_replace("@double_dot@","::",$params);}else $function=$params=null;$php_var=$var_name.$variable_path;if(isset($function)){if($php_var)$php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $php_var, $params ) )":"$function( $php_var )").$php_right_delimiter;else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $params ) )":"$function()").$php_right_delimiter;}else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).$php_var.$extra_var.$php_right_delimiter;$html=str_replace($tag,$php_var,$html);}return $html;}function var_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){if(preg_match_all('/'.$tag_left_delimiter.'\$(\w+(?:\.\${0,1}[A-Za-z0-9_]+)*(?:(?:\[\${0,1}[A-Za-z0-9_]+\])|(?:\-\>\${0,1}[A-Za-z0-9_]+))*)(.*?)'.$tag_right_delimiter.'/',$html,$matches)){for($parsed=array(),$i=0,$n=count($matches[0]);$i<$n;$i++)$parsed[$matches[0][$i]]=array('var'=>$matches[1][$i],'extra_var'=>$matches[2][$i]);foreach($parsed as $tag=>$array){$var=$array['var'];$extra_var=$array['extra_var'];$this->function_check($tag);$extra_var=$this->var_replace($extra_var,null,null,null,null,$loop_level);$is_init_variable=preg_match("/^[a-z_A-Z\.\[\](\-\>)]*=[^=]*$/",$extra_var);$function_var=($extra_var and $extra_var[0]=='|')?substr($extra_var,1):null;$temp=preg_split("/\.|\[|\-\>/",$var);$var_name=$temp[0];$variable_path=substr($var,strlen($var_name));$variable_path=str_replace('[','["',$variable_path);$variable_path=str_replace(']','"]',$variable_path);$variable_path=preg_replace('/\.(\${0,1}\w+)/','["\\1"]',$variable_path);if($is_init_variable)$extra_var="=\$this->var['{$var_name}']{$variable_path}".$extra_var;if($function_var){$function_var=str_replace("::","@double_dot@",$function_var);if($dot_position=strpos($function_var,":")){$function=substr($function_var,0,$dot_position);$params=substr($function_var,$dot_position+1);}else{$function=str_replace("@double_dot@","::",$function_var);$params=null;}$function=str_replace("@double_dot@","::",$function);$params=str_replace("@double_dot@","::",$params);}else $function=$params=null;if($loop_level){if($var_name=='key')$php_var='$key'.$loop_level;elseif($var_name=='value')$php_var='$value'.$loop_level.$variable_path;elseif($var_name=='counter')$php_var='$counter'.$loop_level;else $php_var='$'.$var_name.$variable_path;}else $php_var='$'.$var_name.$variable_path;if(isset($function))$php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $php_var, $params ) )":"$function( $php_var )").$php_right_delimiter;else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).$php_var.$extra_var.$php_right_delimiter;$html=str_replace($tag,$php_var,$html);}}return $html;}protected function function_check($code){$preg='#(\W|\s)'.implode('(\W|\s)|(\W|\s)',self::$black_list).'(\W|\s)#';if(count(self::$black_list)&&preg_match($preg,$code,$match)){$line=0;$rows=explode("\n",$this->tpl['source']);while(!strpos($rows[$line],$code))$line++;$e=new RainTpl_SyntaxException('Unallowed syntax in '.$this->tpl['tpl_filename'].' template');throw $e->setTemplateFile($this->tpl['tpl_filename'])->setTag($code)->setTemplateLine($line);}}protected function printDebug(RainTpl_Exception $e){if(!self::$debug){throw $e;}$output=sprintf('<h2>Exception: %s</h2><h3>%s</h3><p>template: %s</p>',get_class($e),$e->getMessage(),$e->getTemplateFile());if($e instanceof RainTpl_SyntaxException){if(null!=$e->getTemplateLine()){$output.='<p>line: '.$e->getTemplateLine().'</p>';}if(null!=$e->getTag()){$output.='<p>in tag: '.htmlspecialchars($e->getTag()).'</p>';}if(null!=$e->getTemplateLine()&&null!=$e->getTag()){$rows=explode("\n",htmlspecialchars($this->tpl['source']));$rows[$e->getTemplateLine()]='<font color=red>'.$rows[$e->getTemplateLine()].'</font>';$output.='<h3>template code</h3>'.implode('<br />',$rows).'</pre>';}}$output.=sprintf('<h3>trace</h3><p>In %s on line %d</p><pre>%s</pre>',$e->getFile(),$e->getLine(),nl2br(htmlspecialchars($e->getTraceAsString())));return $output;}}class RainTpl_Exception extends Exception{protected $templateFile='';public function getTemplateFile(){return $this->templateFile;}public function setTemplateFile($templateFile){$this->templateFile=(string) $templateFile;return $this;}}class RainTpl_NotFoundException extends RainTpl_Exception{}class RainTpl_SyntaxException extends RainTpl_Exception{protected $templateLine=null;protected $tag=null;public function getTemplateLine(){return $this->templateLine;}public function setTemplateLine($templateLine){$this->templateLine=(int) $templateLine;return $this;}public function getTag(){return $this->tag;}public function setTag($tag){$this->tag=(string) $tag;return $this;}}

/**
 * Movie class
 */
class Movie {
	const SEEN = 1;
	const TO_SEE = 2;
	const UNKNOWN = NULL;
}

/**
 * Database class
 */
class Movies implements Iterator, Countable, ArrayAccess {
	private $data;
	private $keys;
	private $current; 
	private $logged;

	function __construct($logged = FALSE) {
		$this->logged = $logged;
		$this->check();
		$this->read();
	}

	// Countable interface implementation
	public function count() { return count($this->data); }

	// ArrayAccess interface implementation
	public function offsetSet($offset, $value)
	{
		if (!$this->loggedin) die('You are not authorized to add a movie.');
		if (empty($value['id'])) die('Internal Error: A movie should always have a date id.');
		if (empty($offset)) die('You must specify a key.');
		$this->data[$offset] = $value;
	}
	public function offsetExists($offset) { return array_key_exists($offset,$this->data); }
	public function offsetUnset($offset)
	{
		if (!$this->loggedin) die('You are not authorized to delete a movie.');
		unset($this->data[$offset]);
	}
	public function offsetGet($offset) { return isset($this->data[$offset]) ? $this->data[$offset] : null; }

	// Iterator interface implementation
	function rewind() { $this->keys=array_keys($this->data); rsort($this->keys); $this->current=0; } 
	function key() { return $this->keys[$this->current]; } 
	function current() { return $this->data[$this->keys[$this->current]]; } 
	function next() { ++$this->current; } 
	function valid() { return isset($this->keys[$this->current]); } 

	// Check if db directory and file exists
	private function check() {
		global $_CONFIG;
		if (!file_exists($_CONFIG['database']))  {
			$this->data = array();
			$movie = array('id' => 1375621919,'title' => 'Moi, moche et méchant','original_title' => 'Despicable me','date' => '2010-10-06','country' => 'USA','kind' => 'animation, comédie, famille','duration' => 95,'description' => 'Dans un charmant quartier résidentiel délimité par des clôtures de bois blanc et orné de rosiers fleurissants se dresse une bâtisse noire entourée d’une pelouse en friche. Cette façade sinistre cache un secret : Gru, un méchant vilain, entouré d’une myriade de sous-fifres et armé jusqu’aux dents, qui, à l’insu du voisinage, complote le plus gros casse de tous les temps : voler la lune (Oui, la lune !)...<br />Gru affectionne toutes sortes de sales joujoux. Il possède une multitude de véhicules de combat aérien et terrestre et un arsenal de rayons immobilisants et rétrécissants avec lesquels il anéantit tous ceux qui osent lui barrer la route... jusqu’au jour où il tombe nez à nez avec trois petites orphelines qui voient en lui quelqu’un de tout à fait différent : un papa.<br />Le plus grand vilain de tous les temps se retrouve confronté à sa plus dure épreuve : trois fillettes prénommées Margo, Edith et Agnes','links' => array('image' => NULL,'more' => 'http://www.allocine.fr/film/fichefilm_gen_cfilm=140623.html'),'status' => Movie::SEEN,'note' => 4.5, 'acquired' => TRUE);
			$this->data[$movie['id']] = $movie;
			$movie = array('id' => 1375621921,'title' => 'Mission Impossible: Protocole Fantôme','original_title' => NULL,'date' => '2012-03-31','country' => 'USA','kind' => 'action','duration' => 75,'description' => 'En reposa dans une prison russe, le gentil bonhomme va devoir parer à de nouvelles avantures, mais cette fois ci, sans aide de sa direction !','links' => array('image' => NULL,'more' => 'http://www.more.fr/film/fichefilm_gen_cfilm=190299.html'),'status' => Movie::TO_SEE,'note' => NULL, 'acquired' => TRUE);
			$this->data[$movie['id']] = $movie;
			$movie = array('id' => 1375621920,'title' => 'Moi, moche et méchant 2','original_title' => 'Despicable me 2','date' => '2013-06-26','country' => 'USA','kind' => 'animation','duration' => 98,'description' => 'Ayant abandonné la super-criminalité et mis de côté ses activités funestes pour se consacrer à la paternité et élever Margo, Édith et Agnès, Gru, et avec lui, le Professeur Néfario et les Minions, doivent se trouver de nouvelles occupations. Alors qu’il commence à peine à s’adapter à sa nouvelle vie tranquille de père de famille, une organisation ultrasecrète, menant une lutte acharnée contre le Mal à l’échelle planétaire, vient frapper à sa porte. Soudain, c’est à Gru, et à sa nouvelle coéquipière Lucy, que revient la responsabilité de résoudre une série de méfaits spectaculaires. Après tout, qui mieux que l’ex plus méchant méchant de tous les temps, pourrait attraper celui qui rivalise pour lui voler la place qu’il occupait encore récemment.<br />Rejoignant nos héros, on découvre : Floyd, le propriétaire du salon Eagle Postiche Club pour hommes et suspect numéro 1 du crime le plus abject jamais perpétré depuis le départ de Gru à la retraite ; Silas de Lamolefès, le super-espion à la tête de l’Agence Vigilance de Lynx, patron de Lucy, dont le nom de famille est une source inépuisable d’amusement pour les Minions ; Antonio, le si mielleux objet de l’affection naissante de Margo, et Eduardo Perez, le père d’Antonio, propriétaire du restaurant Salsa & Salsa et l’homme qui se cache peut-être derrière le masque d’El Macho, le plus impitoyable et, comme son nom l’indique, méchant macho que la terre ait jamais porté.','links' => array('image' => 'data/images/1375621920.jpg','more' => 'http://www.allocine.fr/film/fichefilm_gen_cfilm=190299.html'),'status' => Movie::SEEN,'note' => 4, 'acquired' => FALSE);
			$this->data[$movie['id']] = $movie;
			$movie = array('id' => 1375621923,'title' => 'De fleurs et d’ombres : retour sur la grande Avira','original_title' => NULL,'date' => '2012-03-31','country' => 'France','kind' => 'action','duration' => 75,'description' => 'En reposa dans une prison russe, le gentil bonhomme va devoir parer à de nouvelles avantures, mais cette fois ci, sans aide de sa direction !','links' => array('image' => NULL,'more' => 'http://www.more.fr/film/fichefilm_gen_cfilm=190299.html'),'status' => Movie::TO_SEE,'note' => NULL, 'acquired' => FALSE);
			$this->data[$movie['id']] = $movie;            
			file_put_contents($_CONFIG['database'], PHPPREFIX.base64_encode(gzdeflate(serialize($this->data))).PHPSUFFIX);
		}
	}

	// Read database from disk to memory
	private function read() {
		global $_CONFIG;
		$this->data=(file_exists($_CONFIG['database']) ? unserialize(gzinflate(base64_decode(substr(file_get_contents($_CONFIG['database']),strlen(PHPPREFIX),-strlen(PHPSUFFIX))))) : array() );
	}

	// Save database from memory to disk
	public function save() {
		global $_CONFIG;
		if (!$this->loggedin) die('You are not authorized to change the database.');
		file_put_contents($_CONFIG['database'], PHPPREFIX.base64_encode(gzdeflate(serialize($this->data))).PHPSUFFIX);
	}

	// last movies inserted
	public function lastMovies() {
		return $this->data;
	}
}

/**
 * Get link to a page
 */
abstract class Path {
	private static function url($url, $name, $tpl = FALSE) {
		global $_CONFIG;
		$result = '';
		if ($tpl) {
			$result .= '<li';
			if ($url == $tpl) {$result .= ' class="active"';}
			$result .= '>';
		}
		$prefix = $_CONFIG['url_rewriting'] ? '/' : '/?';
		$result .= '<a href=".';
		switch ($url) {
			case 'home':
				break;
			case 'favorite':
				$result .= $prefix.'favorite';
				break;
			case 'soon':
				$result .= $prefix.'soon';
				break;
			default:
				$result .= '#';
		}
		return $result.'">'.$name."</a>".($tpl ? '</li>' : NULL);
	}
	static function menu($active) {
		return self::url('home', 'Home', $active).self::url('favorite', 'Favorites', $active).self::url('soon', 'Soon', $active).'<li class="rss"><a href="./movies.rss"><i class="icon-rss"></i></a></li>'.PHP_EOL;
	}
	static function movie($id) {
		global $_CONFIG;
		return '.'.($_CONFIG['url_rewriting'] ? '/movie/' : '/?movie=').$id;
	}
	static function admin() {
		global $_CONFIG;
		return '.'.($_CONFIG['url_rewriting'] ? '/admin' : '/?admin');	
	}
}

/**
 * Toolbox functions
 */
// save settings of users (ID, password, title)
function writeSettings() {
	global $_CONFIG;
	if (is_file($_CONFIG['settings']) && !isLogged()) die('You are not authorized to change config.');
	$file  = '<?php'.PHP_EOL;
	$file .= '$_CONFIG[\'login\']='.var_export($_CONFIG['login'],true).'; ';
	$file .= '$_CONFIG[\'hash\']='.var_export($_CONFIG['hash'],true).'; ';
	$file .= '$_CONFIG[\'salt\']='.var_export($_CONFIG['salt'],true).'; ';
	$file .= '$_CONFIG[\'title\']='.var_export($_CONFIG['title'],true).'; ';
	$file .= PHP_EOL.'?>';
	if (!file_put_contents($_CONFIG['settings'], $file)) die('Impossible to write the configuration file. Please verify the webapplication has rights to write.');
}

// log actions into file
function writeLog($message) {
	$t = strval(date('Y/m/d H:i:s')).' - '.$_SERVER["REMOTE_ADDR"].' - '.strval($message)."\n";
	file_put_contents($GLOBALS['config']['DATADIR'].'/log.txt',$t,FILE_APPEND);
}

/**
 * Session managment (thanks to Sébastien Sauvage with Shaarli!)
 */
// Get state if user is logged in or not
function isLogged() {
	global $_CONFIG;
	if (!isset($_CONFIG['login'])) { return false; }
	// If session does not exist on server side, or IP address has changed, or session has expired, logout.
	if (empty($_SESSION['uid']) || $_SESSION['ip'] != currentIP() || time() >= $_SESSION['expires_on']) {
		logout();
		return false;
	}
	$_SESSION['expires_on'] = time() + INACTIVITY_TIMEOUT;
	return true;
}

// Logout user
function logout() { if(isset($_SESSION)) { unset($_SESSION['uid']); unset($_SESSION['ip']); unset($_SESSION['expires_on']); } }

// Returns the IP address of the client (Used to prevent session cookie hijacking.)
function currentIP() {
    $ip = $_SERVER["REMOTE_ADDR"];
    // Then we use more HTTP headers to prevent session hijacking from users behind the same proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip=$ip.'_'.$_SERVER['HTTP_X_FORWARDED_FOR']; }
    if (isset($_SERVER['HTTP_CLIENT_IP'])) { $ip=$ip.'_'.$_SERVER['HTTP_CLIENT_IP']; }
    return $ip;
}

// Check that user/password is correct.
function check_auth($login, $password) {
	global $_CONFIG;
	$hash = sha1($login.$password.$_CONFIG['salt']);
	if ($login == $_CONFIG['login'] && $hash == $_CONFIG['hash']) {
		$_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand());
		$_SESSION['ip'] = cuurentIP();
		$_SESSION['expires_on'] = time()+INACTIVITY_TIMEOUT;
		writeLog('Login successful');
		return True;
	}
	writeLog('Login failed for user '.$login);
	return False;
}


/**
 * Display functions
 */
// kinks in <li></li>
function displayKinds($kinds) {
	$kind = explode(",", $kinds);
	$result = '';
	foreach ($kind as $value)
		$result .= '<li><i class="icon-tag"></i> '.mb_convert_case($value, MB_CASE_TITLE, "UTF-8").'</li>';
	return $result.PHP_EOL;
}
// shortcut description with <span class="more"></span>
function displayDescription($description, $size = 400) {
	if (strlen($description) > $size) {
		$begin = substr($description, 0, $size);
		$end = substr($description, $size);
		return $begin.'<span class="more"><span class="more-dots" style="display:none;">[...]</span><span class="more-content">'.$end.'</span><div class="more-button btn btn-default btn-mini" style="display:none;">More</div></span>';
	}
	return $description;
}
// Convert note into stars
function displayNote($note) {
	$stars = $note % 5;
	$half_star = ceil($note) - $stars;
	$result = '<div class="stars stars-'.ceil($note).'">';
	for ($i=0; $i<$stars; $i++)
		$result .= '<i class="icon-star"></i>';
	if ($half_star != 0)
		$result .= '<i class="icon-star-half"></i>';
	return $result.'</div>'.PHP_EOL;
}
// remplace status by icon
function displayStatus($status) {
	$result = '<span class="tip" data-title="';
	switch($status) {
		case Movie::SEEN:
			$result .= 'Movie seen';
			break;
		case Movie::TO_SEE:
			$result .= 'Movie to see';
			break;
		default:
			$result .= 'Unknown status';
	}
	$result .= '" data-placement="bottom"><i class="icon-';
	switch($status) {
		case Movie::SEEN:
			$result .= 'ok-sign';
			break;
		case Movie::TO_SEE:
			$result .= 'circle-blank';
			break;
		default:
			$result .= 'question-sign';
	}
	return $result.'"></i></span>';
}
// remplace country name by a flag
function displayFlag($country) {
	global $_CONFIG;
	return '<span class="tip" data-title="'.$country.'" data-placement="bottom"><span class="flag flag-'.$_CONFIG['flags'][$country].'" width="16" height="11"></span></span>';
}

/**
 * Script begin here
 */

// installation: get user's ID
function install($tpl) {
	// get informations to save
	if (!empty($_POST['login']) && !empty($_POST['password'])) {
		global $_CONFIG;
		$_CONFIG['login'] = htmlspecialchars($_POST['login']);
		$_CONFIG['salt'] = sha1(uniqid('',true).'_'.mt_rand());
		$_CONFIG['hash'] = sha1($_CONFIG['login'].$_POST['password'].$_CONFIG['salt']);
		$_CONFIG['title'] = empty($_POST['title']) ? 'Movies' : htmlspecialchars($_POST['title']);
		writeSettings();
		header('Location: '.$_SERVER['REQUEST_URI']);
		exit();
	}

	$tpl->assign('page_title', 'Installation');	
	$tpl->assign('menu_links', NULL);
	$tpl->draw('install');
	exit();
}

// administration pages
function administration() {

}

$movies = new Movies();
$tpl->assign('movie', $movies->lastMovies());
$tpl->assign('page_title', 'Home');

$tpl->assign('menu_links', Path::menu('home'));
$tpl->draw('list');

?>