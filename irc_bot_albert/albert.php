<?
/* 
*    IRC BOT Albert  by p0ke (aka Armand Patou)
*		p0ke (a t) hotmail dot co dot th
*
* Thanks to bobo (uregina -at- gmail -dot- com) (his web site (http://hexmud.com)) 
* for his script that helped me to proceed my own bot.
* If you share, please let me know :) 
*
* 								GPL Version 2 or greater		
*
*		Thanks 		p0ke 		2014-12-15 
*
*/


set_time_limit(0);
error_reporting(E_ALL);
global $nick, $ident, $realname, $host, $port,$fp, $readbuffer, $line, $master,$curchan,$partmsg,$tblip,$tblnick, $logpath,$cmd;

$tblip[]='192.168.0.1';
$tblnick[]='bot';


$chuckfact = file_get_contents("chucknorris.txt");
$chuckfact = explode("\r\n",$chuckfact);
$chuckcount = 0;
shuffle($chuckfact);

$datafile = "mamajokes.txt"; // this a simple text file with one quote per line

$mamajokes = file_get_contents($datafile);
$mamajokes = explode("\n",$mamajokes);
$mamacount = 0;
shuffle($mamajokes);

$jcvd= file_get_contents("jcvd.txt"); // this a simple text file with one quote per line
$jcvd = explode("\n",$jcvd);
$jcvdcount = 0;
shuffle($jcvd);

$botname="p0ke's bot";
$botversion='0.2';
$logpath="./logs/";
$partmsg="Always follow your heart, but don't forget your brain !";
$host = "open.ircnet.net";
$port=6667;
$nick="Albert"; 
$ident="polop";
$chan[]="#demofr";
$realname = "halfblue";

autolog("Starting $botname version $botversion now \r\n");
autolog("On $host $port \r\n");

start:

$fp=connect($nick, $ident, $realname, $host, $port);
for($i=0;$i<count($chan);$i++)
{
	joinChan($chan[$i]); 
}
receive();

goto start;
//
// ̿' ̿'\̵͇̿̿\з=(◕_◕)=ε/̵͇̿̿/'̿'̿ ̿  
//
function safe_feof($fp, &$start = NULL) {
	$start = microtime(true);
	return feof($fp);
}
function connect($nick, $ident, $realname, $host, $port)
{
	$fp = fsockopen($host, $port, $erno, $errstr, 30);
	if(!$fp) die("Could not connect\r\n");
	fwrite($fp, "USER ".$ident." ".$host." ".$host." :".$realname."\r\n");
	fwrite($fp, "NICK ".$nick."\r\n");
	return $fp;
}
function receive()
{
	global $fp, $line,$nick;
	$start = NULL;
	$timeout = ini_get('default_socket_timeout');

	while(!safe_feof($fp, $start) && (microtime(true) - $start) < $timeout)
	{
		$line = fgets($fp, 256); 
		if(is_msgchan($line))count_nick($line);
		if(is_command($line))run_command($line);
		if(is_kick($line))autojoin($line);
		if(is_ping($line))pong();
		if(is_join($line))autoop();
		if(!is_ping($line))autolog($line);
		$line = "";
	}
	fclose($fp);
	autolog("Socket close after time out.Reconnecting. \r\n");
	return;
}
function send($msg)
{
	global $fp;
	if($msg=='') return false;
	if(!strstr($msg, "\n")) $msg .= "\n";
	fwrite($fp, $msg);
	return true;
}
function setNick($nick)						{ send("NICK ".$nick."\r\n");}
function joinChan($channel)					{ send("JOIN :".$channel."\r\n"); }
function quitChan($channel)					{ send("PART :".$channel."\r\n"); }
function listChans()						{ send("LIST\r\n"); }
function getTopic($channel)					{ send("TOPIC ".$channel."\r\n"); }
function msg($target, $msg)					{ send("PRIVMSG $target :$msg\r\n"); }
function msgChan($channel, $msg)			{ msg($channel, $msg); }
function msgUser($user, $msg)				{ msg($user, $msg); }
function pong()								{global $host; send("PONG :".$host."\r\n"); }
function send_quit($msg="")					{global $fp;send("QUIT :$msg\r\n");fclose($fp);exit(1);}
function is_ping($line)						{ if(strstr($line, 'PING')) return true; }
function is_kick($line)						{ if(strstr($line, 'KICK')) return true; }
function is_join($line)						{ if(strstr($line, 'JOIN')) return true; }
function is_msg($line)						{ if(strstr($line, 'PRIVMSG')) return true; }
function is_msgchan($line)					{ if(strstr($line, 'PRIVMSG #')) return true; }
function is_command($line)					{global $nick;if(strstr($line, ":!")) return true; }
function is_master()						{global $master,$line;$tmp=get_nick($line);if($master==$tmp)return true;}


function count_nick($line)
{
	global $statnick;
	$msg = msgToArray($line);
	$cntnick=count($statnick);
	$sentence=explode(' ',$msg['msg']);
	$cntword=count($sentence);
	$found=0;
	for($i=0;$i<$cntnick;$i=$i+4)
	{
		if(isset($statnick[$i]) and $statnick[$i]==$msg['from'] and isset($statnick[$i+3]) and $statnick[$i+3]==$msg['chan']){
			$statnick[$i+1]=$statnick[$i+1]+$cntword;
			$found=1;
			break;
		}
	}
	if($found==0)
	{
		$statnick[$cntnick]=$msg['from'];
		$statnick[$cntnick+1]=$cntword;
		$statnick[$cntnick+2]=date("Y-m-d_H:i:s");
		$statnick[$cntnick+3]=$msg['chan'];
	}
}

function autoop()
{
	global $line, $nick,$tblip,$tblnick;
	if(stripos($line,'JOIN')!==false and stripos($line,'PRIVMSG')===false ){
		$tmp=explode('!',$line);
		$tmp=explode(':',$tmp[0]);
		$tmpnick=$tmp[1];
		if($tmpnick!=$nick)
		{
			$tmp=explode(' ',$line);
			$tmp=explode('@',$tmp[0]);
			$ip=trim(gethostbyname($tmp[1]));
			$tmpchan =explode('JOIN :',$line);
			$tmpchan[1]=trim($tmpchan[1]);
			$tmpnick=trim($tmpnick);
			$tosend="MODE ".$tmpchan[1]." +o ".$tmpnick;
			$count=count($tblip);
			for($i=0;$i<$count;$i++)
			{
				if($tblip[$i]==$ip and $tblnick[$i]==$tmpnick)send($tosend);
			}
		}
	}
}
function autojoin($line)
{
	global $nick;
	$tmp=explode('KICK',$line);
	$tmp=explode(' ',trim($tmp[1]));
	if(trim($tmp[1])==($nick))joinChan(trim($tmp[0]));
}
function msgToArray($line)
{
	global $nick;
	if(!is_msg($line))return false;
	$array = explode(":",$line);
	$from = explode("!",$array[1]);
	$from = trim($from[0]);
	$tmpdestchan ='PRIVMSG #';
	if(strstr ( $line , $tmpdestchan)){
		$fromchan = explode("#",$array[1]);
		$fromchan = "#".trim($fromchan[1]);
	}else{
		$fromchan = explode("PRIVMSG",$array[1]);
		$fromchan = explode(":",$fromchan[1]);
		$fromchan = trim($fromchan[0]);
	}
	$string = $array[2];
	$string = trim($string);
	$msg = array('from'=>$from, 'chan'=>$fromchan, 'msg'=>$string);
	return $msg;
}
function get_command($string)
{
	if(!strstr($string,"!")) return false;
	if(!strstr($string, " "))
	$command = $string;
	else
	{
		$command = explode(" ", $string,2);
		$command = $command[0];
	}
	return $command;
}
function get_nick($line)
{
	if(strripos ( $line , 'PRIVMSG')!=0){
		$tmp=explode("PRIVMSG",$line);
		$tmp=trim(substr($tmp[0],1,strlen($tmp[0])-1));
		return $tmp;
	}
}

function autolog($line)
{
	global $logpath;
	$msg = msgToArray($line);
	$datestr=date("Ymd")."-";
	if(is_msg($line))
	{
		if(is_string($msg['chan'])==TRUE){
			if(isset($msg['chan']) and $msg['chan']!="" and stripos ( $msg['chan'] , '#')!== false){
				$nomfich=$logpath.$datestr.$msg['chan'].".log";
			}else if(isset($msg['from']) and $msg['from']!=""){
				$nomfich=$logpath.$datestr.$msg['from'].".log";
			}
		}else{
			$nomfich=$logpath.$datestr.$msg['from'].".log";
		}
	}else{
		$nomfich=$logpath.$datestr.'system'.".log";
	}
	if(trim($line)!='')
	{
		$loggy = fopen($nomfich, 'a');
		$tolog=date("Y-m-d_H:i:s-").$line;
		fputs($loggy,$tolog);
		fclose($loggy);
	}
}     
function send_showloglist($msg)
{
	global $line,$master,$logpath;
	if(is_master())
	{
		$directory = $logpath;
		$scanned_directory = scandir($directory);
		$count=count($scanned_directory);
		for($i=0;$i<$count;$i++)
		{
			if($scanned_directory[$i]!='.' and $scanned_directory[$i]!='..')msgUser( $msg['from'], $scanned_directory[$i] );
			usleep(100000);
		}
	}
}    
function send_getlog($msg)
{
	global $line, $nick, $master,$logpath;
	if(is_master())
	{
		$filetoload=$logpath.$msg['msg'];
		if (file_exists($filetoload)){
			$maxligne=0;
			$handle = @fopen($filetoload, "r");
			if ($handle) {
				while (($buffer = fgets($handle, 4096)) !== false) {
					$pile[$maxligne]=trim($buffer);
					$maxligne++;
				}
				if (!feof($handle)) {
					$msgUser( $msg['from'], "Error: fget fail");
				}
				fclose($handle);
			}    
			for($i=0;$i<$maxligne;$i++)
			{
				msgUser( $msg['from'], $pile[$i]);
				usleep(3000000);
			}
		}else{
			msgUser( $msg['from'], "Pas de log avec ce nom");
		}
	}
}

function send_greplog($msg)
{
	global $line, $nick, $master,$logpath;
	if(is_master())
	{
		$togrep=explode(' ',$msg['msg']);
		$filetoload=$logpath.$togrep[0];
		if (file_exists($filetoload)){
			$maxligne=0;
			$handle = @fopen($filetoload, "r");
			if ($handle) {
				while (($buffer = fgets($handle, 4096)) !== false) {
					$pile[$maxligne]=trim($buffer);
					$maxligne++;
				}
				if (!feof($handle)) {
					$msgUser( $msg['from'], "Error: fget fail");
				}
				fclose($handle);
			}    
			for($i=0;$i<$maxligne;$i++)
			{
				if(strstr($pile[$i],$togrep[1])){msgUser( $msg['from'], $pile[$i]);usleep(3000000);}
			}
		}else{
			msgUser( $msg['from'], "Pas de log avec ce nom");
		}
	}
}  
    
function send_mamajoke($msg){
	global $line;
	if(strstr($line, 'PRIVMSG #'))
	{
		msgUser( $msg['chan'], get_mama_joke());
	}else{
		msgUser( $msg['from'], get_mama_joke());}
	}

function send_chuck($msg){
	global $line;
	if(strstr($line, 'PRIVMSG #'))
	{
		msgUser( $msg['chan'], get_chuck_joke());
	}else{
		msgUser( $msg['from'], get_chuck_joke());
	}
}   

function send_jcvd($msg){
	global $line;
	if(strstr($line, 'PRIVMSG #'))
	{
		msgUser( $msg['chan'], get_jcvd_joke());
	}else{
		msgUser( $msg['from'], get_jcvd_joke());
	}
}   

function send_master($msg)
{
	global $line,$nick,$master;
	// password stuff here 
	if($msg['msg']==$mdp)
	{
		$tmp=get_nick($line);
		$master = $tmp;
	}
}
function send_clearmaster($msg)
{
	global $master;
	$master = '';
	msg($msg['from'],"you are not my master anymore");
}
function send_whoami($msg)
{
	global $line, $master;
	if(is_master())msg($msg['from'],"you are my master $master");
}
function send_opme($msg)
{
	global $line, $master;
	if(is_master())send("MODE ".$msg['msg']." +o ".$msg['from']);
}
function send_saychan($msg)
{
	global $line, $master;
	if(is_master())
	{
		$tmp=explode(" ",$msg['msg']);
		$tmp=trim($tmp[0]);
		msgChan('#'.$tmp, substr($msg['msg'],strlen($tmp),strlen($msg['msg'])-strlen($tmp)));
	}
}
function send_sendcmd($msg)
{
	global $line, $nick, $master;
	if(is_master())send("WHOIS ".$nick);
}
function send_showusers($msg)
{
	global $line, $nick, $master,$tblnick,$tblip;
	if(is_master()){
		$count=count($tblip);
		for($i=0;$i<$count;$i++)
		{
			msgUser( $msg['from'], $tblnick[$i].':'.$tblip[$i]);
			usleep(100000);
		}
	}
}

function send_nick_stats($msg)
{
	global $line, $nick, $master,$statnick;
	if(is_master()){
		$count=count($statnick);
		for($i=0;$i<$count;$i=$i+4)
		{
			if(isset($statnick[$i]) and isset($statnick[$i+3]) and $msg['msg']==$statnick[$i+3])msgUser($msg['from'], $statnick[$i+3].' : '.$statnick[$i+2].' : '.$statnick[$i].':'.$statnick[$i+1]);
			usleep(300000);
		}
	}
}

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function send_memory_usage($msg)
{
	global $line, $nick, $master,$statnick;
	if(is_master()){
		msgUser($msg['from'], 'Memory : '.convert(memory_get_usage(true)));
		msgUser($msg['from'], 'Memory : '.memory_get_usage());
	}
}


function send_clear_nick_stats()
{
	global $statnick;
	unset($statnick);
}

function get_chuck_joke()
{
	global $chuckfact,$chuckcount;
	return($chuckfact[$chuckcount++]);
}

function get_mama_joke()
{
	global $mamajokes, $mamacount;
	return($mamajokes[$mamacount++]);
}

function get_jcvd_joke()
{
	global $jcvd, $jcvdcount;
	return($jcvd[$jcvdcount++]);
}

function run_command($line)
{
	global $partmsg,$msg;
	$msg = msgToArray($line);
	$command = get_command($msg['msg']);
	$msg['msg'] = trim(str_replace($command,'',$msg['msg']));

	$cmd=array(
	'!yomama','send_mamajoke($msg);',2,
	'!chuck','send_chuck($msg);',2,
	'!jcvd','send_jcvd($msg);',2,
	'!disconnect','send_quit($partmsg);',1,
	'!logme','send_master($msg);',1,
	'!whoami','send_whoami($msg);',1,
	'!opme','send_opme($msg);',1,
	'!clearmaster','send_clearmaster($msg);',1,
	'!saychan','send_saychan($msg);',1,
	'!showloglist','send_showloglist($msg);',1,
	'!getlog','send_getlog($msg);',1,
	'!sendcmd','send_sendcmd($msg);',1,
	'!showusers','send_showusers($msg);',1,
	'!greplog','send_greplog($msg);',1,
	'!nickstats','send_nick_stats($msg);',1,
	'!clearnickstats','send_clear_nick_stats($msg);',1,
	'!memory','send_memory_usage($msg);',1
	);	
	for($i=0;$i<count($cmd);$i=$i+3)
	{
		if(substr($msg['chan'],0,1)=='#' and ($cmd[$i+2]==0 or $cmd[$i+2]==2) and $cmd[$i]==$command){eval($cmd[$i+1]);break;}
		if(substr($msg['chan'],0,1)!='#' and ($cmd[$i+2]==1 or $cmd[$i+2]==2) and $cmd[$i]==$command){eval($cmd[$i+1]);break;}
	}
}

?>