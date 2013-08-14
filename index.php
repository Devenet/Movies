<?php

date_default_timezone_set('Europe/Paris');
//error_reporting(0);

global $_CONFIG;
$_CONFIG['data'] = 'data';
$_CONFIG['lib'] = 'lib';
$_CONFIG['scraper'] = 'lib/scraper';
$_CONFIG['scraper_imdb'] = 'lib/scraper/imdb.php';
$_CONFIG['database'] = $_CONFIG['data'].'/movies.php';
$_CONFIG['settings'] = $_CONFIG['data'].'/settings.php';
$_CONFIG['log'] = $_CONFIG['data'].'/area-51.txt';
$_CONFIG['images'] = 'images';
$_CONFIG['cache'] = 'cache';
$_CONFIG['title'] = 'myMovies';
$_CONFIG['url_rewriting'] = FALSE;
$_CONFIG['countries'] = array(
	'us' => 'United States of America',
	'fr' => 'France',
	'de' => 'Germany',
	'gb' => 'United Kingdom',
	'be' => 'Belgique',
	'ca' => 'Canada'
);
$_CONFIG['ban'] = $_CONFIG['data'].'/jail.php';
$_CONFIG['ban_after'] = 4;
$_CONFIG['ban_duration'] = 1800;
$_CONFIG['pagination'] = 10;

define('PHPPREFIX','<?php /* '); 
define('PHPSUFFIX',' */ ?>');
define('MYMOVIES_VERSION', '0.1');
define('INACTIVITY_TIMEOUT', 3600);

// Force cookie path (but do not change lifetime)
$cookie = session_get_cookie_params();
$cookiedir = ''; if(dirname($_SERVER['SCRIPT_NAME'])!='/') $cookiedir=dirname($_SERVER["SCRIPT_NAME"]).'/';
session_set_cookie_params($cookie['lifetime'], $cookiedir, $_SERVER['HTTP_HOST']);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', false);
session_name('myMovies');
if (session_id() == '') session_start();

// check right before create directories
if (!is_writable(realpath(dirname(__FILE__)))) die('<p style="text-align:center;"><span style="color:red;">ERROR</span><br />Application does not have the right to write in its own directory <code>'.realpath(dirname(__FILE__)).'</code>.</p>');
if (!is_dir($_CONFIG['data'])) { mkdir($_CONFIG['data'],0705); chmod($_CONFIG['data'],0705); }
if (!is_file($_CONFIG['data'].'/.htaccess')) { file_put_contents($_CONFIG['data'].'/.htaccess', 'deny from all'); }
if (!is_file($_CONFIG['data'].'/.htaccess')) die('<p style="text-align:center;"><span style="color:red;">*ERROR*</span><br />Application does not have the right to write in its own directory <code>'.realpath(dirname(__FILE__)).'</code>.</p>');
if (!is_dir($_CONFIG['cache'])) { mkdir($_CONFIG['cache'],0705); chmod($_CONFIG['cache'],0705); }
if (!is_file($_CONFIG['cache'].'/.htaccess')) { file_put_contents($_CONFIG['cache'].'/.htaccess', 'deny from all'); }
if (!is_dir($_CONFIG['images'])) { mkdir($_CONFIG['images'],0705); chmod($_CONFIG['images'],0705); }
if (!is_file($_CONFIG['images'].'/.htaccess')) { file_put_contents($_CONFIG['images'].'/.htaccess', 'options -indexes'); }
if (!is_file($_CONFIG['ban'])) { file_put_contents($_CONFIG['ban'], '<?php'.PHP_EOL.'$_CONFIG[\'ban_ip\']='.var_export(array('failures'=>array(),'banned'=>array()), TRUE).';'.PHP_EOL.'?>'); }

//ob_start();
$tpl = new RainTPL();

if (!is_file($_CONFIG['settings'])) {define('TITLE', $_CONFIG['title']); install($tpl);}
require($_CONFIG['settings']);
define('TITLE', $_CONFIG['title']);
define('PAGINATION', $_CONFIG['pagination']);

/**
 * Rain class
 * @version 2.7.2
 */
class RainTPL {static $tpl_dir="templates/";static $cache_dir="cache/";static $base_url=null;static $tpl_ext="rain";static $path_replace=false;static $path_replace_list=array('a','img','link','script','input');static $black_list=array('\$this','raintpl::','self::','_SESSION','_SERVER','_ENV','eval','exec','unlink','rmdir');static $check_template_update=true;static $php_enabled=false;static $debug=false;public $var=array();protected $tpl=array(),$cache=false,$cache_id=null;protected static $config_name_sum=array();const CACHE_EXPIRE_TIME=3600;function assign($variable,$value=null){if(is_array($variable))$this->var+=$variable;else $this->var[$variable]=$value;}function draw($tpl_name,$return_string=false){try{$this->check_template($tpl_name);}catch(RainTpl_Exception $e){$output=$this->printDebug($e);die($output);}if(!$this->cache&&!$return_string){extract($this->var);include $this->tpl['compiled_filename'];unset($this->tpl);}else{ob_start();extract($this->var);include $this->tpl['compiled_filename'];$raintpl_contents=ob_get_clean();if($this->cache)file_put_contents($this->tpl['cache_filename'],"<?php if(!class_exists('raintpl')){exit;}?>".$raintpl_contents);unset($this->tpl);if($return_string)return $raintpl_contents;else echo $raintpl_contents;}}function cache($tpl_name,$expire_time=self::CACHE_EXPIRE_TIME,$cache_id=null){$this->cache_id=$cache_id;if(!$this->check_template($tpl_name)&&file_exists($this->tpl['cache_filename'])&&(time()-filemtime($this->tpl['cache_filename'])<$expire_time))return substr(file_get_contents($this->tpl['cache_filename']),43);else{if(file_exists($this->tpl['cache_filename']))unlink($this->tpl['cache_filename']);$this->cache=true;}}static function configure($setting,$value=null){if(is_array($setting))foreach($setting as $key=>$value)self::configure($key,$value);else if(property_exists(__CLASS__,$setting)){self::$$setting=$value;self::$config_name_sum[$setting]=$value;}}protected function check_template($tpl_name){if(!isset($this->tpl['checked'])){$tpl_basename=basename($tpl_name);$tpl_basedir=strpos($tpl_name,"/")?dirname($tpl_name).'/':null;$tpl_dir=self::$tpl_dir.$tpl_basedir;$this->tpl['tpl_filename']=$tpl_dir.$tpl_basename.'.'.self::$tpl_ext;$temp_compiled_filename=self::$cache_dir.$tpl_basename.".".md5($tpl_dir.serialize(self::$config_name_sum));$this->tpl['compiled_filename']=$temp_compiled_filename.'.rtpl.php';$this->tpl['cache_filename']=$temp_compiled_filename.'.s_'.$this->cache_id.'.rtpl.php';if(self::$check_template_update&&!file_exists($this->tpl['tpl_filename'])){$e=new RainTpl_NotFoundException('Template '.$tpl_basename.' not found!');throw $e->setTemplateFile($this->tpl['tpl_filename']);}if(!file_exists($this->tpl['compiled_filename'])||(self::$check_template_update&&filemtime($this->tpl['compiled_filename'])<filemtime($this->tpl['tpl_filename']))){$this->compileFile($tpl_basename,$tpl_basedir,$this->tpl['tpl_filename'],self::$cache_dir,$this->tpl['compiled_filename']);return true;}$this->tpl['checked']=true;}}protected function xml_reSubstitution($capture){return "<?php echo '<?xml ".stripslashes($capture[1])." ?>'; ?>";}protected function compileFile($tpl_basename,$tpl_basedir,$tpl_filename,$cache_dir,$compiled_filename){$this->tpl['source']=$template_code=file_get_contents($tpl_filename);$template_code=preg_replace("/<\?xml(.*?)\?>/s","##XML\\1XML##",$template_code);if(!self::$php_enabled)$template_code=str_replace(array("<?","?>"),array("&lt;?","?&gt;"),$template_code);$template_code=preg_replace_callback("/##XML(.*?)XML##/s",array($this,'xml_reSubstitution'),$template_code);$template_compiled="<?php if(!class_exists('raintpl')){exit;}?>".$this->compileTemplate($template_code,$tpl_basedir);$template_compiled=str_replace("?>\n","?>\n\n",$template_compiled);if(!is_dir($cache_dir))mkdir($cache_dir,0755,true);if(!is_writable($cache_dir))throw new RainTpl_Exception('Cache directory '.$cache_dir.'doesn\'t have write permission. Set write permission or set RAINTPL_CHECK_TEMPLATE_UPDATE to false. More details on http://www.raintpl.com/Documentation/Documentation-for-PHP-developers/Configuration/');file_put_contents($compiled_filename,$template_compiled);}protected function compileTemplate($template_code,$tpl_basedir){$tag_regexp=array('loop'=>'(\{loop(?: name){0,1}="\${0,1}[^"]*"\})','loop_close'=>'(\{\/loop\})','if'=>'(\{if(?: condition){0,1}="[^"]*"\})','elseif'=>'(\{elseif(?: condition){0,1}="[^"]*"\})','else'=>'(\{else\})','if_close'=>'(\{\/if\})','function'=>'(\{function="[^"]*"\})','noparse'=>'(\{noparse\})','noparse_close'=>'(\{\/noparse\})','ignore'=>'(\{ignore\}|\{\*)','ignore_close'=>'(\{\/ignore\}|\*\})','include'=>'(\{include="[^"]*"(?: cache="[^"]*")?\})','template_info'=>'(\{\$template_info\})','function'=>'(\{function="(\w*?)(?:.*?)"\})');$tag_regexp="/".join("|",$tag_regexp)."/";$template_code=preg_split($tag_regexp,$template_code,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);$template_code=$this->path_replace($template_code,$tpl_basedir);$compiled_code=$this->compileCode($template_code);return $compiled_code;}protected function compileCode($parsed_code){$compiled_code=$open_if=$comment_is_open=$ignore_is_open=null;$loop_level=0;while($html=array_shift($parsed_code)){if(!$comment_is_open&&(strpos($html,'{/ignore}')!==FALSE||strpos($html,'*}')!==FALSE))$ignore_is_open=false;elseif($ignore_is_open){}elseif(strpos($html,'{/noparse}')!==FALSE)$comment_is_open=false;elseif($comment_is_open)$compiled_code.=$html;elseif(strpos($html,'{ignore}')!==FALSE||strpos($html,'{*')!==FALSE)$ignore_is_open=true;elseif(strpos($html,'{noparse}')!==FALSE)$comment_is_open=true;elseif(preg_match('/\{include="([^"]*)"(?: cache="([^"]*)"){0,1}\}/',$html,$code)){$include_var=$this->var_replace($code[1],$left_delimiter=null,$right_delimiter=null,$php_left_delimiter='".',$php_right_delimiter='."',$loop_level);if(isset($code[2])){$compiled_code.='<?php $tpl = new '.get_class($this).';'.'if( $cache = $tpl->cache( $template = basename("'.$include_var.'") ) )'.'	echo $cache;'.'else{'.'	$tpl_dir_temp = self::$tpl_dir;'.'	$tpl->assign( $this->var );'.(!$loop_level?null:'$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );').'	$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'") );'.'} ?>';}else{$compiled_code.='<?php $tpl = new '.get_class($this).';'.'$tpl_dir_temp = self::$tpl_dir;'.'$tpl->assign( $this->var );'.(!$loop_level?null:'$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );').'$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'") );'.'?>';}}elseif(preg_match('/\{loop(?: name){0,1}="\${0,1}([^"]*)"\}/',$html,$code)){$loop_level++;$var=$this->var_replace('$'.$code[1],$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level-1);$counter="\$counter$loop_level";$key="\$key$loop_level";$value="\$value$loop_level";$compiled_code.="<?php $counter=-1; if( isset($var) && is_array($var) && sizeof($var) ) foreach( $var as $key => $value ){ $counter++; ?>";}elseif(strpos($html,'{/loop}')!==FALSE){$counter="\$counter$loop_level";$loop_level--;$compiled_code.="<?php } ?>";}elseif(preg_match('/\{if(?: condition){0,1}="([^"]*)"\}/',$html,$code)){$open_if++;$tag=$code[0];$condition=$code[1];$this->function_check($tag);$parsed_condition=$this->var_replace($condition,$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php if( $parsed_condition ){ ?>";}elseif(preg_match('/\{elseif(?: condition){0,1}="([^"]*)"\}/',$html,$code)){$tag=$code[0];$condition=$code[1];$parsed_condition=$this->var_replace($condition,$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php }elseif( $parsed_condition ){ ?>";}elseif(strpos($html,'{else}')!==FALSE){$compiled_code.='<?php }else{ ?>';}elseif(strpos($html,'{/if}')!==FALSE){$open_if--;$compiled_code.='<?php } ?>';}elseif(preg_match('/\{function="(\w*)(.*?)"\}/',$html,$code)){$tag=$code[0];$function=$code[1];$this->function_check($tag);if(empty($code[2]))$parsed_function=$function."()";else $parsed_function=$function.$this->var_replace($code[2],$tag_left_delimiter=null,$tag_right_delimiter=null,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level);$compiled_code.="<?php echo $parsed_function; ?>";}elseif(strpos($html,'{$template_info}')!==FALSE){$tag='{$template_info}';$compiled_code.='<?php echo "<pre>"; print_r( $this->var ); echo "</pre>"; ?>';}else{$html=$this->var_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);$html=$this->const_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);$compiled_code.=$this->func_replace($html,$left_delimiter='\{',$right_delimiter='\}',$php_left_delimiter='<?php ',$php_right_delimiter=';?>',$loop_level,$echo=true);}}if($open_if>0){$e=new RainTpl_SyntaxException('Error! You need to close an {if} tag in '.$this->tpl['tpl_filename'].' template');throw $e->setTemplateFile($this->tpl['tpl_filename']);}return $compiled_code;}protected function reduce_path($path){$path=str_replace("://","@not_replace@",$path);$path=str_replace("//","/",$path);$path=str_replace("@not_replace@","://",$path);return preg_replace('/\w+\/\.\.\//','',$path);}protected function path_replace($html,$tpl_basedir){if(self::$path_replace){$tpl_dir=self::$base_url.self::$tpl_dir.$tpl_basedir;$path=$this->reduce_path($tpl_dir);$exp=$sub=array();if(in_array("img",self::$path_replace_list)){$exp=array('/<img(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<img(.*?)src=(?:")([^"]+?)#(?:")/i','/<img(.*?)src="(.*?)"/','/<img(.*?)src=(?:\@)([^"]+?)(?:\@)/i');$sub=array('<img$1src=@$2://$3@','<img$1src=@$2@','<img$1src="'.$path.'$2"','<img$1src="$2"');}if(in_array("script",self::$path_replace_list)){$exp=array_merge($exp,array('/<script(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<script(.*?)src=(?:")([^"]+?)#(?:")/i','/<script(.*?)src="(.*?)"/','/<script(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<script$1src=@$2://$3@','<script$1src=@$2@','<script$1src="'.$path.'$2"','<script$1src="$2"'));}if(in_array("link",self::$path_replace_list)){$exp=array_merge($exp,array('/<link(.*?)href=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<link(.*?)href=(?:")([^"]+?)#(?:")/i','/<link(.*?)href="(.*?)"/','/<link(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<link$1href=@$2://$3@','<link$1href=@$2@','<link$1href="'.$path.'$2"','<link$1href="$2"'));}if(in_array("a",self::$path_replace_list)){$exp=array_merge($exp,array('/<a(.*?)href=(?:")(http\:\/\/|https\:\/\/|javascript:)([^"]+?)(?:")/i','/<a(.*?)href="(.*?)"/','/<a(.*?)href=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<a$1href=@$2$3@','<a$1href="'.self::$base_url.'$2"','<a$1href="$2"'));}if(in_array("input",self::$path_replace_list)){$exp=array_merge($exp,array('/<input(.*?)src=(?:")(http|https)\:\/\/([^"]+?)(?:")/i','/<input(.*?)src=(?:")([^"]+?)#(?:")/i','/<input(.*?)src="(.*?)"/','/<input(.*?)src=(?:\@)([^"]+?)(?:\@)/i'));$sub=array_merge($sub,array('<input$1src=@$2://$3@','<input$1src=@$2@','<input$1src="'.$path.'$2"','<input$1src="$2"'));}return preg_replace($exp,$sub,$html);}else return $html;}function const_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){return preg_replace('/\{\#(\w+)\#{0,1}\}/',$php_left_delimiter.($echo?" echo ":null).'\\1'.$php_right_delimiter,$html);}function func_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){preg_match_all('/'.'\{\#{0,1}(\"{0,1}.*?\"{0,1})(\|\w.*?)\#{0,1}\}'.'/',$html,$matches);for($i=0,$n=count($matches[0]);$i<$n;$i++){$tag=$matches[0][$i];$var=$matches[1][$i];$extra_var=$matches[2][$i];$this->function_check($tag);$extra_var=$this->var_replace($extra_var,null,null,null,null,$loop_level);$is_init_variable=preg_match("/^(\s*?)\=[^=](.*?)$/",$extra_var);$function_var=($extra_var and $extra_var[0]=='|')?substr($extra_var,1):null;$temp=preg_split("/\.|\[|\-\>/",$var);$var_name=$temp[0];$variable_path=substr($var,strlen($var_name));$variable_path=str_replace('[','["',$variable_path);$variable_path=str_replace(']','"]',$variable_path);$variable_path=preg_replace('/\.\$(\w+)/','["$\\1"]',$variable_path);$variable_path=preg_replace('/\.(\w+)/','["\\1"]',$variable_path);if($function_var){$function_var=str_replace("::","@double_dot@",$function_var);if($dot_position=strpos($function_var,":")){$function=substr($function_var,0,$dot_position);$params=substr($function_var,$dot_position+1);}else{$function=str_replace("@double_dot@","::",$function_var);$params=null;}$function=str_replace("@double_dot@","::",$function);$params=str_replace("@double_dot@","::",$params);}else $function=$params=null;$php_var=$var_name.$variable_path;if(isset($function)){if($php_var)$php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $php_var, $params ) )":"$function( $php_var )").$php_right_delimiter;else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $params ) )":"$function()").$php_right_delimiter;}else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).$php_var.$extra_var.$php_right_delimiter;$html=str_replace($tag,$php_var,$html);}return $html;}function var_replace($html,$tag_left_delimiter,$tag_right_delimiter,$php_left_delimiter=null,$php_right_delimiter=null,$loop_level=null,$echo=null){if(preg_match_all('/'.$tag_left_delimiter.'\$(\w+(?:\.\${0,1}[A-Za-z0-9_]+)*(?:(?:\[\${0,1}[A-Za-z0-9_]+\])|(?:\-\>\${0,1}[A-Za-z0-9_]+))*)(.*?)'.$tag_right_delimiter.'/',$html,$matches)){for($parsed=array(),$i=0,$n=count($matches[0]);$i<$n;$i++)$parsed[$matches[0][$i]]=array('var'=>$matches[1][$i],'extra_var'=>$matches[2][$i]);foreach($parsed as $tag=>$array){$var=$array['var'];$extra_var=$array['extra_var'];$this->function_check($tag);$extra_var=$this->var_replace($extra_var,null,null,null,null,$loop_level);$is_init_variable=preg_match("/^[a-z_A-Z\.\[\](\-\>)]*=[^=]*$/",$extra_var);$function_var=($extra_var and $extra_var[0]=='|')?substr($extra_var,1):null;$temp=preg_split("/\.|\[|\-\>/",$var);$var_name=$temp[0];$variable_path=substr($var,strlen($var_name));$variable_path=str_replace('[','["',$variable_path);$variable_path=str_replace(']','"]',$variable_path);$variable_path=preg_replace('/\.(\${0,1}\w+)/','["\\1"]',$variable_path);if($is_init_variable)$extra_var="=\$this->var['{$var_name}']{$variable_path}".$extra_var;if($function_var){$function_var=str_replace("::","@double_dot@",$function_var);if($dot_position=strpos($function_var,":")){$function=substr($function_var,0,$dot_position);$params=substr($function_var,$dot_position+1);}else{$function=str_replace("@double_dot@","::",$function_var);$params=null;}$function=str_replace("@double_dot@","::",$function);$params=str_replace("@double_dot@","::",$params);}else $function=$params=null;if($loop_level){if($var_name=='key')$php_var='$key'.$loop_level;elseif($var_name=='value')$php_var='$value'.$loop_level.$variable_path;elseif($var_name=='counter')$php_var='$counter'.$loop_level;else $php_var='$'.$var_name.$variable_path;}else $php_var='$'.$var_name.$variable_path;if(isset($function))$php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).($params?"( $function( $php_var, $params ) )":"$function( $php_var )").$php_right_delimiter;else $php_var=$php_left_delimiter.(!$is_init_variable&&$echo?'echo ':null).$php_var.$extra_var.$php_right_delimiter;$html=str_replace($tag,$php_var,$html);}}return $html;}protected function function_check($code){$preg='#(\W|\s)'.implode('(\W|\s)|(\W|\s)',self::$black_list).'(\W|\s)#';if(count(self::$black_list)&&preg_match($preg,$code,$match)){$line=0;$rows=explode("\n",$this->tpl['source']);while(!strpos($rows[$line],$code))$line++;$e=new RainTpl_SyntaxException('Unallowed syntax in '.$this->tpl['tpl_filename'].' template');throw $e->setTemplateFile($this->tpl['tpl_filename'])->setTag($code)->setTemplateLine($line);}}protected function printDebug(RainTpl_Exception $e){if(!self::$debug){throw $e;}$output=sprintf('<h2>Exception: %s</h2><h3>%s</h3><p>template: %s</p>',get_class($e),$e->getMessage(),$e->getTemplateFile());if($e instanceof RainTpl_SyntaxException){if(null!=$e->getTemplateLine()){$output.='<p>line: '.$e->getTemplateLine().'</p>';}if(null!=$e->getTag()){$output.='<p>in tag: '.htmlspecialchars($e->getTag()).'</p>';}if(null!=$e->getTemplateLine()&&null!=$e->getTag()){$rows=explode("\n",htmlspecialchars($this->tpl['source']));$rows[$e->getTemplateLine()]='<font color=red>'.$rows[$e->getTemplateLine()].'</font>';$output.='<h3>template code</h3>'.implode('<br />',$rows).'</pre>';}}$output.=sprintf('<h3>trace</h3><p>In %s on line %d</p><pre>%s</pre>',$e->getFile(),$e->getLine(),nl2br(htmlspecialchars($e->getTraceAsString())));return $output;}}class RainTpl_Exception extends Exception{protected $templateFile='';public function getTemplateFile(){return $this->templateFile;}public function setTemplateFile($templateFile){$this->templateFile=(string) $templateFile;return $this;}}class RainTpl_NotFoundException extends RainTpl_Exception{}class RainTpl_SyntaxException extends RainTpl_Exception{protected $templateLine=null;protected $tag=null;public function getTemplateLine(){return $this->templateLine;}public function setTemplateLine($templateLine){$this->templateLine=(int) $templateLine;return $this;}public function getTag(){return $this->tag;}public function setTag($tag){$this->tag=(string) $tag;return $this;}}

/**
 * Movie class
 */
class Movie {
	const SEEN = TRUE;
	const NOT_SEEN = NULL;
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
	public function offsetSet($offset, $value) {
		if (!$this->logged) die('You are not authorized to add a movie.');
		if (empty($value['id'])) die('Internal Error: A movie should always have a date id.');
		if (empty($offset)) die('You must specify a key.');
		$this->data[$offset] = $value;
	}
	public function offsetExists($offset) { return array_key_exists($offset,$this->data); }
	public function offsetUnset($offset) {
		if (!$this->logged) die('You are not authorized to delete a movie.');
		unset($this->data[$offset]);
	}
	public function offsetGet($offset) { return isset($this->data[$offset]) ? $this->data[$offset] : NULL; }

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
			$movie = array('id' => 1375621919,'title' => 'Moi, moche et méchant','original_title' => 'Despicable me','release_date' => '2010-10-06','country' => 'us','genre' => 'animation, comédie, famille','duration' => 95,'synopsis' => 'Dans un charmant quartier résidentiel délimité par des clôtures de bois blanc et orné de rosiers fleurissants se dresse une bâtisse noire entourée d’une pelouse en friche. Cette façade sinistre cache un secret : Gru, un méchant vilain, entouré d’une myriade de sous-fifres et armé jusqu’aux dents, qui, à l’insu du voisinage, complote le plus gros casse de tous les temps : voler la lune (Oui, la lune !)...<br />Gru affectionne toutes sortes de sales joujoux. Il possède une multitude de véhicules de combat aérien et terrestre et un arsenal de rayons immobilisants et rétrécissants avec lesquels il anéantit tous ceux qui osent lui barrer la route... jusqu’au jour où il tombe nez à nez avec trois petites orphelines qui voient en lui quelqu’un de tout à fait différent : un papa.<br />Le plus grand vilain de tous les temps se retrouve confronté à sa plus dure épreuve : trois fillettes prénommées Margo, Edith et Agnes','link_image' => NULL,'link_website' => 'http://www.allocine.fr/film/fichefilm_gen_cfilm=140623.html','status' => Movie::SEEN,'note' => 9, 'owned' => TRUE);
			$this->data[$movie['id']] = $movie;
			$movie = array('id' => 1375621920,'title' => 'Moi, moche et méchant 2','original_title' => 'Despicable me 2','release_date' => '2013-06-26','country' => 'us','genre' => 'animation','duration' => 98,'synopsis' => 'Ayant abandonné la super-criminalité et mis de côté ses activités funestes pour se consacrer à la paternité et élever Margo, Édith et Agnès, Gru, et avec lui, le Professeur Néfario et les Minions, doivent se trouver de nouvelles occupations. Alors qu’il commence à peine à s’adapter à sa nouvelle vie tranquille de père de famille, une organisation ultrasecrète, menant une lutte acharnée contre le Mal à l’échelle planétaire, vient frapper à sa porte. Soudain, c’est à Gru, et à sa nouvelle coéquipière Lucy, que revient la responsabilité de résoudre une série de méfaits spectaculaires. Après tout, qui mieux que l’ex plus méchant méchant de tous les temps, pourrait attraper celui qui rivalise pour lui voler la place qu’il occupait encore récemment.<br />Rejoignant nos héros, on découvre : Floyd, le propriétaire du salon Eagle Postiche Club pour hommes et suspect numéro 1 du crime le plus abject jamais perpétré depuis le départ de Gru à la retraite ; Silas de Lamolefès, le super-espion à la tête de l’Agence Vigilance de Lynx, patron de Lucy, dont le nom de famille est une source inépuisable d’amusement pour les Minions ; Antonio, le si mielleux objet de l’affection naissante de Margo, et Eduardo Perez, le père d’Antonio, propriétaire du restaurant Salsa & Salsa et l’homme qui se cache peut-être derrière le masque d’El Macho, le plus impitoyable et, comme son nom l’indique, méchant macho que la terre ait jamais porté.','link_image' => NULL,'link_website' => 'http://www.allocine.fr/film/fichefilm_gen_cfilm=190299.html','status' => Movie::NOT_SEEN,'note' => NULL, 'owned' => FALSE);
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
		if (!$this->logged) die('You are not authorized to change the database.');
		file_put_contents($_CONFIG['database'], PHPPREFIX.base64_encode(gzdeflate(serialize($this->data))).PHPSUFFIX);		
	}

	// last movies inserted
	public function lastMovies($begin = 0) {
		krsort($this->data);
		return array_slice($this->data, $begin, PAGINATION, TRUE);
	}
}

/**
 * Get link to a page
 */
abstract class Path {
	private static function url($url, $name, $tpl = FALSE) {
		$result = '';
		if ($tpl) {
			$result .= '<li';
			if ($url == $tpl) {$result .= ' class="active"';}
			$result .= '>';
		}
		$prefix = '/?';
		$result .= '<a href=".';
		switch ($url) {
			case 'home':
				break;
			case 'favorites':
				$result .= $prefix.'favorites';
				break;
			case 'soon':
				$result .= $prefix.'soon';
				break;
			case 'add':
				$result .= $prefix.'add';
				break;
			default:
				$result .= '#';
		}
		return $result.'">'.$name."</a>".($tpl ? '</li>' : NULL);
	}
	private static function url_admin($url, $name, $tpl = FALSE) {
		$result = '';
		if ($tpl) {
			$result .= '<li';
			if ($url == $tpl) {$result .= ' class="active"';}
			$result .= '>';
		}
		$prefix = '/?';
		$result .= '<a href=".';
		$icon = NULL;
		switch ($url) {
			case 'add':
				$result .= $prefix.'add';
				$icon = 'plus';
				break;
			case 'admin':
				$result .= $prefix.'admin';
				$icon = '';
				break;
			default:
				$result .= '#';
		}
		return $result.'">'.($icon != NULL ? '<i class="icon-'.$icon.'"></i>' : NULL).' '.$name."</a>".($tpl ? '</li>' : NULL);
	}
	static function menu($active) {
		return self::url('home', 'All', $active).self::url('favorites', 'Favorites', $active).self::url('soon', 'Soon', $active).'<li class="rss"><a href="./movies.rss" rel="external"><i class="icon-rss"></i></a></li>'.PHP_EOL;
	}
	static function menuAdmin($active) {
		return self::url_admin('add', 'Movie', $active).self::url_admin('admin', 'Admin', $active).PHP_EOL;
	}
	static function movie($id) {
		return './?movie='.$id;
	}
	static function page($id) {
		return 'page='.$id;
	}
	static function admin() {
		return './?admin';
	}
	static function signin() {
		return './?signin';
	}
	static function signout() {
		return './?signout';
	}
	static function add() {
		return './?add';
	}
	static function edit($id) {
		return './?edit='.$id;
	}
	static function delete($id) {
		return './?delete='.$id;
	}
	static function logs() {
		return './?logs';
	}
	static function settings() {
		return './?settings';	
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
	global $_CONFIG;
	$log = strval(date('Y-m-d H:i:s')).' ['.$_SERVER["REMOTE_ADDR"].'] '.strval($message)."\n";
	file_put_contents($_CONFIG['log'], $log, FILE_APPEND);
}

// function message error page
function errorPage($message, $title) {
	global $tpl;
	$tpl->assign('page_title', 'Error');
	$tpl->assign('menu_links', Path::menu('error'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('error'));
	$tpl->assign('error_title', $title);
	$tpl->assign('error_content', $message.'<div class="espace-top">Please <a href="'.$_SERVER['REQUEST_URI'].'">try again</a>.</div>');
	$tpl->draw('error');
	exit();
}

// keep nl2br in synospis content
function checkSynopsis($html) {
	return nl2br(htmlspecialchars($html));
}

// check if a note give is correct
function checkRatingNote($note, $status) {
	if ($status != Movie::SEEN) { return NULL; }
	$note = (int) $note+0;
	if ($note >= 0 && $note <= 10)
		return $note;
	return 5;
}

// check if duration input is a positif integer
function checkDuration($duration) {
	$duration = (int) $duration+0;
	if ($duration > 0 && $duration <= 300)
		return $duration;
	return NULL;
}

// check if release date input is a date (but not if the date exists)
function checkReleaseDate($date) {
	if (empty($date)) { return NULL; }
	list($y, $m, $d) = explode('-', htmlspecialchars($date));
	$y = (int) $y+0; $m = (int) $m+0; $d = (int) $d+0;
	if (! $y > 0) { return NULL; }
	if (! ($m > 0 && $m <= 12)) { return NULL; }
	if (! ($d > 0 && $d <= 31)) { return NULL; }
	return implode('-', array($y, str_pad($m, 2, '0', STR_PAD_LEFT), str_pad($d, 2, '0', STR_PAD_LEFT)));
}

// check if country given is in list
function checkContry($country) {
	if (empty($country) || $country == 'o') { return NULL; }
	global $_CONFIG;
	if (array_key_exists($country, $_CONFIG['countries'])) { return htmlspecialchars($country) ;}
	return NULL;
}

// check if input is a link, and if prefix was added or not
function checkLink($url) {
	global $_CONFIG;
	if (empty($url)) { return NULL; }
	// in case of local link to images folder
	if (substr( $url, 0, strlen($_CONFIG['images'].'/') ) === $_CONFIG['images'].'/') { return $url; }
	$scheme = parse_url(htmlspecialchars($url), PHP_URL_SCHEME);
	$url = preg_replace('#https?://#', '', htmlspecialchars($url));
	return (!empty($scheme) ? $scheme : 'http').'://'.$url;
}

function checkGenre($genre) {
	return trim(mb_convert_case(htmlspecialchars($genre), MB_CASE_TITLE, "UTF-8"));
}

function importImage($url, $id) {
	global $_CONFIG;

	$tmp = $_CONFIG['images'].'/temp.jpg';
	$output = $_CONFIG['images'].'/'.$id;
	$width = 160;
	$height = 213;

	$allowed_ext = array('jpg', 'jpeg', 'gif', 'png');
	$allowed_mime = array('image/jpeg', 'image/png', 'image/gif');

	$infos = @getimagesize($url);
	if ($infos == FALSE) { throw new \Exception('The URL given is not an image or the file is not found.'); }

	$mime = $infos['mime'];
	$ext = pathinfo($url, PATHINFO_EXTENSION);

	if (!in_array($mime, $allowed_mime) || !in_array($ext, $allowed_ext)) { throw new \Exception('The MIMIE type or the extension of the image is not allowed.'); }

	$img = @file_get_contents($url);
	$imported = file_put_contents($tmp, $img);
	if ($imported == FALSE) { throw new \Exception('Unable to import image.'); }

	if ($ext == 'png') { $src = imagecreatefrompng($tmp); }
	else { $src = imagecreatefromjpeg($tmp); }
	unlink($tmp);

	$thumb = imagecreatetruecolor($width, $height);
	imagecopyresampled($thumb, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));
	$result = imagejpeg($thumb, $output.'.jpg');
	if ($result == FALSE) {  throw new \Exception('Unable to resize image.'); }
}

// check if page number asked is correct else 404 or homepage
function checkPagination($page, $total) {
	$page = (int) $page+0;
	if ($page <= 0) { header('Location: ./'); exit(); }
	$pages = ceil($total/PAGINATION);
	if ($page < $pages) { return TRUE; }
	notFound();
}


/**
 * Session managment (thanks to Sébastien Sauvage with Shaarli!)
 */
// Get state if user is logged in or not
function isLogged() {
	global $_CONFIG;
	if (!isset($_CONFIG['login'])) { return FALSE; }
	// If session does not exist on server side, or IP address has changed, or session has expired, logout.
	if (empty($_SESSION['uid']) || $_SESSION['ip'] != currentIP() || time() >= $_SESSION['expires_on']) {
		logout();
		return FALSE;
	}
	$_SESSION['expires_on'] = time() + INACTIVITY_TIMEOUT;
	return TRUE;
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
		$_SESSION['uid'] = sha1(uniqid('', TRUE).'_'.mt_rand());
		$_SESSION['ip'] = currentIP();
		$_SESSION['expires_on'] = time() + INACTIVITY_TIMEOUT;
		writeLog('Login successful for user '.htmlspecialchars($login));
		return TRUE;
	}
	writeLog('Login failed for user '.htmlspecialchars($login));
	return FALSE;
}

// Token are attached to the session
if (!isset($_SESSION['tokens'])) $_SESSION['tokens']=array();

// Returns a token
function getToken() {
	$token = sha1(uniqid('',true).'_'.mt_rand());
	$_SESSION['tokens'][$token] = 1;
	return $token;
}

// Tells if a token is ok and destroy it in case of success
function acceptToken($token) {
	if (isset($_SESSION['tokens'][$token])) {
		unset($_SESSION['tokens'][$token]);
		return TRUE;
	}
	writeLog('Invalid token given');
	return FALSE;
}

// Several consecutive failed logins will ban the IP address for 1 hour.
include $_CONFIG['ban'];
// in case of failed login
function loginFailed() {
	global $_CONFIG;
	$ip = $_SERVER['REMOTE_ADDR'];
	$ban = $_CONFIG['ban_ip'];
	if (!isset($ban['failures'][$ip])) {$ban['failures'][$ip] = 0;}
	$ban['failures'][$ip]++;
	if ($ban['failures'][$ip] > ($_CONFIG['ban_after']-1)) {
		$ban['banned'][$ip] = time() + $_CONFIG['ban_duration'];
		writeLog('IP address banned from login');
	}
	$_CONFIG['ban_ip'] = $ban;
	file_put_contents($_CONFIG['ban'], '<?php'.PHP_EOL.'$_CONFIG[\'ban_ip\']='.var_export($ban, TRUE).';'.PHP_EOL.'?>');
}

// Signals a successful login. Resets failed login counter.
function loginSucceeded() {
	global $_CONFIG;
	$ip = $_SERVER["REMOTE_ADDR"];
	$ban = $_CONFIG['ban_ip'];
	unset($ban['failures'][$ip]);
	unset($ban['banned'][$ip]);
	$_CONFIG['ban_ip'] = $ban;
	file_put_contents($_CONFIG['ban'], '<?php'.PHP_EOL.'$_CONFIG[\'ban_ip\']='.var_export($ban, TRUE).';'.PHP_EOL.'?>');
}

// Checks if the user CAN login. If 'true', the user can try to login.
function canLogin() {
	global $_CONFIG;
	$ip = $_SERVER["REMOTE_ADDR"];
	$ban = $_CONFIG['ban_ip'];
	if (isset($ban['banned'][$ip])) {
		// User is banned. Check if the ban has expired:
		if ($ban['banned'][$ip] <= time()) {
			writeLog('Ban lifted');
			unset($ban['failures'][$ip]);
			unset($ban['banned'][$ip]);
			file_put_contents($_CONFIG['ban'], '<?php'.PHP_EOL.'$_CONFIG[\'ban_ip\']='.var_export($ban, TRUE).';'.PHP_EOL.'?>');
			return TRUE;
		}
		return FALSE;
	}
	return TRUE;
}

// list of url allowed to be redirected
function targetIsAllowed($target) {
	$allowed = array('admin', 'add', 'logs', 'settings');
	return in_array(htmlspecialchars($target), $allowed);
}

/**
 * Display functions
 */
// kinks in <li></li>
function displayGenres($genres) {
	$genre = explode(",", $genres);
	ksort($genre);
	$result = '';
	foreach ($genre as $value)
		$result .= '<li><i class="icon-tag"></i> '.trim(mb_convert_case($value, MB_CASE_TITLE, "UTF-8")).'</li>';
	return $result.PHP_EOL;
}
// shortcut the synopsis (= summary) of the movie with [...]
function displaySynopsis($synopsis, $size = 400) {
	if (strlen($synopsis) > $size) {
		$begin = substr($synopsis, 0, $size);
		return $begin.'[...]';
	}
	return $synopsis;
}
// Convert note into stars
function displayNote($note) {
	$note = $note/2;
	$full_stars = floor($note);
	$half_star = (2*$note) % 2;
	$empty_stars = 5 - $note - $half_star;
	$result = '<div class="stars stars-'.ceil($note).' tip" data-title="Rated '.(2*$note).' out of 10" data-placement="bottom">';
	for ($i=0; $i<$full_stars; $i++)
		$result .= '<i class="icon-star"></i>';
	if ($half_star == 1)
		$result .= '<i class="icon-star-half-empty"></i>';
	for ($i=0; $i<$empty_stars; $i++)
		$result .= '<i class="icon-star-empty"></i>';
	return $result.'</div>'.PHP_EOL;
}
// remplace status by icon
function displayStatus($status) {
	$result = '<span class="tip" data-title="';
	if ($status == Movie::SEEN)
		 { $result .= 'Movie&nbsp;seen'; }
	else { $result .= 'Movie&nbsp;not&nbsp;seen'; }
	$result .= '" data-placement="bottom"><i class="icon-';
	if ($status == Movie::SEEN)
		 { $result .= 'desktop'; }
	else { $result .= 'eye-close'; }
	return $result.'"></i></span>';
}
// remplace country name by a flag
function displayFlag($country) {
	global $_CONFIG;
	return '<span class="tip" data-title="'.$_CONFIG['countries'][$country].'" data-placement="bottom"><span class="flag flag-'.$country.'" width="16" height="11"></span></span>';
}
// display option of each contry (for form.movie.rain)
function displayCountryOptions($active = FALSE) {
	global $_CONFIG;
	asort($_CONFIG['countries']);
	$result = '<option value="o">None</option>';
	foreach($_CONFIG['countries'] as $code => $name) {
		$result .= '<option value="'.$code.'"';
		if ($code == $active) {$result .= ' selected="selected"';}
		$result .= '>'.$name.'</option>';
	}
	return $result;
}

// generate the <li></li> for pagination
function displayPagination($page, $total) {
	$page = (int) $page+0;
	$pages = ceil($total/PAGINATION);
	$offset = 2;
	$begin = ($page-$offset <= 0) ? 0 : $page-$offset;
	$end = ($page+$offset >= $pages) ? $pages-1 : $page+$offset;
	if ($pages > 1 && $end-$begin < 2*$offset) {
		if ($end-$page <= $offset-1) { $begin -= $offset-$end+$page; }
		else { $end += $offset-$page; }
	}
	$result = '<li'.($page==0 ? ' class="disabled"' : NULL).'><a href="./" title="First page" class="tip"><i class="icon-double-angle-left"></i></a></li>';
	for ($i=$begin; $i<=$end; $i++) {
		$result .= '<li'.($i==$page ? ' class="active"' : NULL).'><a href="./'.($i>0 ? '?'.Path::page($i) : NULL).'">'.($i+1).'</a></li>';
	}
	$result .= '<li'.($page==$end ? ' class="disabled"' : NULL).'><a href="./?'.Path::page($pages-1).'" title="Last page" class="tip"><i class="icon-double-angle-right"></i></a></li>';
	return $result;
}

/**
 * Script for pages begin here
 */

// installation: get user's ID
function install($tpl) {
	// get informations to save
	if (!empty($_POST['login']) && !empty($_POST['password'])) {
		global $_CONFIG;
		$_CONFIG['login'] = htmlspecialchars($_POST['login']);
		$_CONFIG['salt'] = sha1(uniqid('',true).'_'.mt_rand());
		$_CONFIG['hash'] = sha1($_CONFIG['login'].$_POST['password'].$_CONFIG['salt']);
		$_CONFIG['title'] = empty($_POST['title']) ? 'myMovies' : htmlspecialchars($_POST['title']);
		writeSettings();
		header('Location: '.$_SERVER['REQUEST_URI']);
		exit();
	}

	$tpl->assign('page_title', 'Installation');	
	$tpl->assign('menu_links', NULL);
	$tpl->draw('form.install');
	exit();
}

// movie page
function moviePage() {
	$movies = new Movies();
	$id = (int) $_GET['movie']+0;
	if (! isset($movies[$id])) { notFound(); }
	$movie = $movies[$id];
	
	global $tpl;
	$tpl->assign('page_title', $movie['title']);
	$tpl->assign('menu_links', Path::menu('movie'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('movie'));
	$tpl->assign('movie', $movie);
	$tpl->assign('id', $movie['id']);
	$tpl->assign('displayStatus', displayStatus($movie['status']));
	$tpl->assign('displayNote', displayNote($movie['note']));
	$tpl->assign('country', $movie['country']);
	$tpl->assign('displayGenres', displayGenres($movie['genre']));
	$tpl->assign('token', getToken());
	$tpl->draw('movie');
	exit();
}

// administration pages
function administration() {
	if (!isLogged()) {
		header('Location: '.Path::signin().'&target=admin');
		exit();
	}
	global $tpl;

	// default page of administration
	$tpl->assign('page_title', 'Administration');
	$tpl->assign('menu_links', Path::menu('admin'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('admin'));
	$tpl->draw('admin');
	exit();
}

// display log file
function logsPage() {
	if (!isLogged()) {
		header('Location: '.Path::signin().'&target=logs');
		exit();
	}
	global $tpl;
	global $_CONFIG;

	if (!empty($_POST['purge-logs'])) {
		if (acceptToken($_POST['token'])) {		
			file_put_contents($_CONFIG['log'], NULL); // in case of deleting file will not work
			unlink($_CONFIG['log']);
			header('Location: '.Path::logs());
			exit();
		}
		errorPage('The given token was empty or invalid.', 'Invalid token');
	}

	if (!is_file($_CONFIG['log'])) {$logs = 'Nothing to say';}
	else {$logs = file_get_contents($_CONFIG['log']);}
	if (empty($logs)) {$logs = 'Nothing to say';}

	$tpl->assign('page_title', 'Logs');
	$tpl->assign('menu_links', Path::menu('logs'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('admin'));
	$tpl->assign('logs', $logs);
	$tpl->assign('filename', basename($_CONFIG['log']));
	$tpl->assign('token', getToken());
	$tpl->draw('admin.logs');
	exit();
}

// display log file
function settingsPage() {
	if (!isLogged()) {
		header('Location: '.Path::signin().'&target=settings');
		exit();
	}
	global $tpl;
	global $_CONFIG;

	if (!empty($_POST)) {
		if (!empty($_POST['token']) && acceptToken($_POST['token'])) {
			global $_CONFIG;
			if (!empty($_POST['title'])) { $_CONFIG['title'] = htmlspecialchars($_POST['title']); }
			if (!empty($_POST['password'])) { $_CONFIG['hash'] = sha1($_CONFIG['login'].$_POST['password'].$_CONFIG['salt']); }
			writeSettings();
			header('Location: '.Path::settings().'&update');
			exit();
		}
		errorPage('The given token was empty or invalid.', 'Invalid token');
	}

	$tpl->assign('page_title', 'Settings');
	$tpl->assign('menu_links', Path::menu('settings'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('admin'));
	$tpl->assign('username', $_CONFIG['login']);
	$tpl->assign('token', getToken());
	$tpl->draw('admin.settings');
	exit();
}

// add a new movie
function addMovie() {
	if (!isLogged()) {
		header('Location: '.Path::signin().'&target=add');
		exit();
	}
	global $tpl;
	global $_CONFIG;

	// process to add movie in database
	if (isset($_POST) && !empty($_POST)) {
		if (!empty($_POST['token']) && acceptToken($_POST['token'])) {
      if(!empty($_POST['search'])){
        try{
          include_once $_CONFIG['scraper_imdb'];
          
          $oIMDB = new IMDB($_POST['search']);
          
          if($oIMDB->isReady){
            $inputs = array(
              'title' => $oIMDB->getTitle(),
              'synopsis' => $oIMDB->getDescription(),
              'genre' => str_replace(' /', ',', $oIMDB->getGenre()),
              'status' => NULL,
              'note' => NULL,
              'owned' => NULL,
              'original_title' => $oIMDB->getTitle(),
              'duration' => NULL,
              'release_date' => NULL,
              'country' => NULL,
              'link_website' => checkLink($oIMDB->getUrl()),
              'link_image' => $_CONFIG['scraper'].'/'.$oIMDB->getPoster(),
              'link_image_import' => TRUE
              //'links-image-upload' => ???
            );
            $tpl->assign('inputs', $inputs);
          } 
          else{
            $tpl->assign('error', 'Movie not found in IMDB database.');
          }
        } catch(\Exception $e) {
          $tpl->assign('error', $e->getMessage());
        }
      }
      else{
        $inputs = array(
          'title' => (isset($_POST['title']) ? trim(htmlspecialchars($_POST['title'])) : NULL),
          'synopsis' => (isset($_POST['synopsis']) ? checkSynopsis($_POST['synopsis']) : NULL),
          'genre' => (isset($_POST['genre']) ? checkGenre($_POST['genre']) : NULL),
          'status' => (isset($_POST['status']) ? Movie::SEEN : NULL),
          'note' => (isset($_POST['note']) ? checkRatingNote($_POST['note'], (isset($_POST['status']) ? Movie::SEEN : NULL)) : NULL),
          'owned' => (isset($_POST['owned']) ? TRUE : NULL),
          'original_title' => (isset($_POST['original_title']) ? trim(htmlspecialchars($_POST['original_title'])) : NULL),
          'duration' => (isset($_POST['duration']) ? checkDuration($_POST['duration']) : NULL),
          'release_date' => (isset($_POST['release_date']) ? checkReleaseDate($_POST['release_date']) : NULL),
          'country' => (isset($_POST['country']) ? checkContry($_POST['country']) : NULL),
          'link_website' => (isset($_POST['link_website']) ? checkLink($_POST['link_website']) : NULL),
          'link_image' => (isset($_POST['link_image']) ? checkLink($_POST['link_image']) : NULL),
          'link_image_import' => (isset($_POST['link_image_import']) ? TRUE : NULL)
          //'links-image-upload' => (isset($_POST['links-image-upload']) ? htmlspecialchars($_POST['links-image-upload']) : NULL)
        );
        $tpl->assign('inputs', $inputs);
        try {
          if (empty($inputs['title'])) { throw new \Exception('Title must not be empty.'); }
          if (empty($inputs['synopsis'])) { throw new \Exception('Synopsis must not be empty.'); }
          $movie = array( 'id' => time() );

          // check if we need to get the image given with url
          if ($inputs['link_image_import']) {
            importImage($inputs['link_image'], $movie['id']);
            $inputs['link_image'] = $_CONFIG['images'].'/'.$movie['id'].'.jpg';
          }
          unset($inputs['link_image_import']);

          foreach ($inputs as $key => $value) { $movie[$key] = $value; }
          $movies = new Movies(isLogged());
          $movies[$movie['id']] = $movie;
          $movies->save();

          header('Location: '.Path::movie($movie['id']));
          exit();
        } catch(\Exception $e) {
          $tpl->assign('error', $e->getMessage());
        }
      }
		}
		else { errorPage('The given token was empty or invalid.', 'Invalid token'); }
	}

	$tpl->assign('page_title', 'New movie');
	$tpl->assign('menu_links', Path::menu('add'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('add'));
	$tpl->assign('today', date('Y-m-d'));
	$tpl->assign('countries', displayCountryOptions(isset($inputs['country']) ? $inputs['country'] : NULL));
	$tpl->assign('token', getToken());
	$tpl->assign('target', Path::add());
	$tpl->assign('target_search', Path::add());
	$tpl->draw('form.movie');
	exit();
}

// edit a movie
function editMovie() {
	if (!isLogged()) {
		header('Location: ./');
		exit();
	}
	
	$movies = new Movies(isLogged());
	$id = (int) $_GET['edit']+0;
	if (! isset($movies[$id])) { notFound(); }
	$movie = $movies[$id];
	global $tpl;
	global $_CONFIG;

	// process to edit movie in database
	if (isset($_POST) && !empty($_POST)) {
		if (!empty($_POST['token']) && acceptToken($_POST['token'])) {
			$inputs = array(
				'title' => (isset($_POST['title']) ? trim(htmlspecialchars($_POST['title'])) : NULL),
				'synopsis' => (isset($_POST['synopsis']) ? checkSynopsis($_POST['synopsis']) : NULL),
				'genre' => (isset($_POST['genre']) ? checkGenre($_POST['genre']) : NULL),
				'status' => (isset($_POST['status']) ? Movie::SEEN : NULL),
				'note' => (isset($_POST['note']) ? checkRatingNote($_POST['note'], (isset($_POST['status']) ? Movie::SEEN : NULL)) : NULL),
				'owned' => (isset($_POST['owned']) ? TRUE : NULL),
				'original_title' => (isset($_POST['original_title']) ? trim(htmlspecialchars($_POST['original_title'])) : NULL),
				'duration' => (isset($_POST['duration']) ? checkDuration($_POST['duration']) : NULL),
				'release_date' => (isset($_POST['release_date']) ? checkReleaseDate($_POST['release_date']) : NULL),
				'country' => (isset($_POST['country']) ? checkContry($_POST['country']) : NULL),
				'link_website' => (isset($_POST['link_website']) ? checkLink($_POST['link_website']) : NULL),
				'link_image' => (isset($_POST['link_image']) ? checkLink($_POST['link_image']) : NULL),
				'link_image_import' => (isset($_POST['link_image_import']) ? TRUE : NULL)
				//'links-image-upload' => (isset($_POST['links-image-upload']) ? htmlspecialchars($_POST['links-image-upload']) : NULL)
			);
			try {
				if (empty($inputs['title'])) { throw new \Exception('Title must not be empty.'); }
				if (empty($inputs['synopsis'])) { throw new \Exception('Synopsis must not be empty.'); }
				$movie = array( 'id' => $id );

				// check if we need to get the image given with url
				if ($inputs['link_image_import']) {
					importImage($inputs['link_image'], $movie['id']);
					$inputs['link_image'] = $_CONFIG['images'].'/'.$id.'.jpg';
				}
				unset($inputs['link_image_import']);

				foreach ($inputs as $key => $value) { $movie[$key] = $value; }
				$movies[$id] = $movie;
				$movies->save();

				header('Location: '.Path::movie($id));
				exit();
			} catch(\Exception $e) {
				$tpl->assign('error', $e->getMessage());
			}
		}
		else { errorPage('The given token was empty or invalid.', 'Invalid token'); }
	}
	else {
		$inputs = array(
			'title' => $movie['title'],
			'synopsis' => str_replace('<br />', '', $movie['synopsis']),
			'genre' => $movie['genre'],
			'status' => $movie['status'],
			'note' => $movie['note'],
			'owned' => $movie['owned'],
			'original_title' => $movie['original_title'],
			'duration' => $movie['duration'],
			'release_date' => $movie['release_date'],
			'country' => $movie['country'],
			'link_website' => preg_replace('#http://#', '', $movie['link_website']),
			'link_image' => preg_replace('#http://#', '', $movie['link_image'])
		);
	}

	$tpl->assign('page_title', 'Edit movie');
	$tpl->assign('menu_links', Path::menu('edit'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('edit'));
	$tpl->assign('inputs', $inputs);
	$tpl->assign('today', date('Y-m-d'));
	$tpl->assign('countries', displayCountryOptions($inputs['country']));
	$tpl->assign('token', getToken());
	$tpl->assign('target', Path::edit($id));
	$tpl->assign('delete', Path::delete($id));
	$tpl->draw('form.movie');
	exit();
}

// delete a movie
function deleteMovie() {
	if (!isLogged()) {
		header('Location: ./');
		exit();
	}
	
	$movies = new Movies(isLogged());
	$id = (int) $_GET['delete']+0;
	if (! isset($movies[$id])) { notFound(); }
	global $_CONFIG;

	// process to delete movie in database
	if (!empty($_GET['token']) && acceptToken($_GET['token'])) {
		// check if a miniature exists and delete it
		$img = $_CONFIG['images'].'/'.$id.'.jpg';
		if (is_file($img)) { unlink($img); }
		// delete movie in database
		unset($movies[$id]);
		$movies->save();
		header('Location: ./');
		exit();
	}
	else { errorPage('The given token was empty or invalid.', 'Invalid token'); }
}

// signout controller
function signout() {
	logout();
	session_destroy();
	header('Location: ./');
	exit();
}

// login page (and process to log user)
function signin() {
	// user already logged in
	if (isLogged()) {
		header('Location: '.Path::admin());
		exit();
	}
	global $tpl;
	global $_CONFIG;

	if (!canLogin()) {
		global $tpl;
		$tpl->assign('page_title', 'Error');
		$tpl->assign('menu_links', Path::menu('error'));
		$tpl->assign('error_title', 'You’re in jail');
		$tpl->assign('error_content', 'You have been banned after too many bad attemps. <div class="espace-top">Please try later.</div>');
		$tpl->draw('error');
		exit();
	}

	if (!empty($_POST['login']) && !empty($_POST['password'])) {
		if (!empty($_POST['token']) && acceptToken($_POST['token'])) {
			if (check_auth(htmlspecialchars($_POST['login']), $_POST['password'])) {
				loginSucceeded();
				$cookiedir = ''; if(dirname($_SERVER['SCRIPT_NAME'])!='/') { $cookiedir=dirname($_SERVER["SCRIPT_NAME"]).'/'; }
				session_set_cookie_params(0, $cookiedir, $_SERVER['HTTP_HOST']);
				session_regenerate_id(TRUE);
				// check if we need to redirect the user
				$target = (isset($_GET['target']) && targetIsAllowed($_GET['target'])) ? Path::$_GET['target']() : './';
				header('Location: '.$target);
				exit();
			}
			loginFailed();
			errorPage('The given username or password was wrong. <br />If you do not remberer your login informations, just delete the file <code>'.basename($_CONFIG['settings']).'</code>.', 'Invalid username or password');
		}
		loginFailed();
		errorPage('The given token was empty or invalid.', 'Invalid token');
	}
	
	$tpl->assign('page_title', 'Sign in');
	$tpl->assign('menu_links', Path::menu('signin'));
	$tpl->assign('target', (isset($_GET['target']) && targetIsAllowed($_GET['target'])) ? htmlspecialchars($_GET['target']) : NULL);
	$tpl->assign('token', getToken());
	$tpl->draw('form.signin');
	exit();
}

/**
 * Process to display (loading...)
 */
// home asked
if (empty($_GET) || isset($_GET['page'])) {
	$movies = new Movies();
	$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
	// check if pagination is asked
	if (!empty($_GET['page'])) {
			checkPagination($page, $movies->count());
			$tpl->assign('movie', $movies->lastMovies($page*PAGINATION));
	} else { $tpl->assign('movie', $movies->lastMovies()); }
	$tpl->assign('pagination', displayPagination($page, $movies->count()));
	$tpl->assign('page_title', 'Home');
	$tpl->assign('menu_links', Path::menu('home'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('home'));
	$tpl->assign('token', getToken());
	$tpl->draw('list');
	exit();
}
// movie asked
if (!empty($_GET['movie'])) {moviePage();}
// admin asked
if (isset($_GET['admin'])) {administration();}
// login asked
if (isset($_GET['signin'])) {signin();}
// logout asked
if (isset($_GET['signout'])) {signout();}
// new movie asked
if (isset($_GET['add'])) {addMovie();}
// edit movie asked
if (isset($_GET['edit']) && !empty($_GET['edit'])) {editMovie();}
// delete movie asked
if (isset($_GET['delete']) && !empty($_GET['delete'])) {deleteMovie();}
// display writted log asked
if (isset($_GET['logs'])) {logsPage();}
// display settings log asked
if (isset($_GET['settings'])) {settingsPage();}


// nothing to do: 404 error
function notFound() {
	global $tpl;
	header('HTTP/1.1 404 Not Found', true, 404);
	$tpl->assign('page_title', 'Error 404');
	$tpl->assign('menu_links', Path::menu('error'));
	$tpl->assign('menu_links_admin', Path::menuAdmin('error'));
	$tpl->draw('404');
	exit();
}
notFound();

?>