<?php
$LANGUAGES = array(
    "fra"=>array(_("French"),"fr_FR","fr"),
    "fre"=>array(_("French"),"fr_FR","fr"),
    "deu"=>array(_("German"),"de_DE","de"),
    "spa"=>array(_("Spanish"),"es_ES","es"),
    "ita"=>array(_("Italian"),"it"),
    "eng"=>array(_("English"),"en_US","en"),
);
abstract class user implements Serializable
{
    var $data = array();
    abstract protected function _initialize();
    abstract protected function _login($name, $password);
    abstract protected function _logout();
    abstract protected function _save();

    public function serialize()
    {
        return serialize($this->data);
    }
    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }
    function __construct($login = false, $password = false)
    {
	session_start();
        global $_SESSION;

	//default values
	$this->data["id"] = -1;
	$this->data["login"] = "Anonymous";
	$this->data["lang"] = "eng";
	$this->data["groupid"] = 0;
	$this->data["capabilities"] = array();
	$this->data["capabilities"]["admin"] = false;
	if (!isset($_SESSION['NgenUser']) && session_name() == "RECORD-NGEN-USER")
	{
	    //error_log("user class 1");
	    $this->_initialize();

	    if ($this->login('RECORD-NGEN-USER', 'RECORD-PASSWD'))
		$_SESSION['NgenUser'] = $this->serialize();
	}
	elseif ($login != false)
	{
	    //error_log("user class 2");
	    // Pass here if :
	    //  the user is authenticating
	    //  the user try without success to log in
	    $this->_initialize();
	    $this->login($login, $password);
		
	}
	else if (isset($_SESSION['NgenUser']))
	{
	    //error_log("user class 3");
	    // Pass here if :
	    //  the user is not log in (first call)
	    //  the user is already log in 
	    $this->unserialize($_SESSION['NgenUser']);
	}
	else
	{
	    //error_log("user class 4");
	    $this->_initialize();
	    // Pass here if :
	    //  the user is log out (after to be log in)
	    $_SESSION['NgenUser'] = $this->serialize();
	}
	session_write_close();
	//putenv("LC_ALL=".$current_locale);
	global $LANGUAGES;
	if (isset($this->data["lang"]) && isset($LANGUAGES[$this->data["lang"]]))
	    $lang = $LANGUAGES[$this->data["lang"]][1];
	else
	    $lang = "en_US";
	putenv("LANG=".$lang);
	putenv("LANGUAGE=".$lang);
	putenv("LC_MESSAGES=".$lang);
	putenv("LC_ALL=".$lang);

	// grosse erreur php... pourquoi ?
	//	setlocale(LC_MESSAGES,$lang);
      
	setlocale(LC_CTYPE,$lang);
	setlocale(LC_ALL, $lang);
	//setlocale(LC_ALL,"");
    }

    public function login($login, $password)
    {
        global $_SESSION;
	$ret = false;
	if (!isset($password) || $password == false)
	    $password = "";
	session_start();
	$_SESSION["Auth"] = $this->_login($login, $password);
	if ($_SESSION["Auth"] > 0)
	{
	    $_SESSION['NgenUser'] = $this->serialize();
	    $ret = true;
	}
	session_write_close();
	return $ret;
    }

    public function logout($url)
    {
        global $_SESSION;
        global $_COOKIE;

	session_start();
	$this->_logout();
        unset($_SESSION['NgenUser']);
        unset($_SESSION['Auth']);
        $_SESSION = array();
        if (isset($_COOKIE[session_name()]))
        {
	    setcookie(session_name(), '', time()-42000, '/', null, null, true);
        }
        session_destroy();
	if (isset($url))
	{
	    header("Location:".$url);
	    die;
	}
    }

    public function save()
    {
	if (!$this->is_authenticated())
	    return false;
	return $this->_save();
    }

    public function language()
    {
	return $this->data["lang"];
    }

    public function id()
    {
	return $this->data["id"];
    }

    public function name()
    {
	return $this->data["login"];
    }

    public function is_menu_enabled($menuname)
    {
        if(! $this->is_authenticated())
            return FALSE;
	if (isset($this->data["capabilities"]["admin"]) && $this->data["capabilities"]["admin"])
	    return TRUE;
        return (isset($this->data["capabilities"][$menuname])?1:0);
    }

    public function get_login()
    {
        return ($this->is_authenticated() && (isset($this->data["login"]))?$this->data["login"]:NULL);
    }

    public function get_userid()
    {
        return ($this->is_authenticated())?$this->data["id"]:NULL;
    }
	
    public function is_authenticated()
    {
	if (isset($_SESSION['Auth']))
	    $auth = ($this->data["id"] >= 0 )? TRUE : FALSE;
	else
	    $auth = FALSE;

        return $auth;
    }

    public function is_administrator()
    {
        if (! $this->is_authenticated()) {
            return FALSE;
        }

        return $this->get_mode('admin');
    }

    public function has_capability($cap)
    {
        if (! $this->is_authenticated())
            return FALSE;

	if(array_key_exists($cap, $this->data["capabilities"]))
	    return TRUE;
	else
	    return FALSE;
    }

    public function get_mode($cap)
    {
	if(! $this->is_authenticated())
            return FALSE;

	if(!array_key_exists($cap, $this->data["capabilities"]))
	    return FALSE;

	$capabilities = $this->data["capabilities"];
	return $capabilities[$cap];
    }
}

class stduser extends user
{
    public function __construct($login = false, $password = false, $serialize = false)
    {
	if ($serialize)
	    $_SESSION['NgenUser'] = $serialize;
	parent::__construct($login, $password);
    }

    protected function _initialize()
    {
    }

    protected function _login($name, $password)
    {
	return false;
    }
    
    protected function _logout()
    {
    }

    protected function _save()
    {
	return false;
    }

}

function otherwise_error($message="Not allowed")
{
    $message = htmlentities($capability);
    printf("
        <html>
        <body>
        %s<br/>
        <a href='%s?action=logout'>"._("Back to login page")."</a>
        </body>
        </html>", $message, $_SERVER['_PHP_FILE_']);
    die();
}

function has_capability_otherwise_error($capability)
{
    global $_SESSION;

    if (! $_SESSION['NgenUser']->has_capability($capability))
    {
        $message = sprintf('Not allowed : you need "%s" capability', htmlentities($capability));
        printf("
            <html>
            <body>
            %s<br/>
            <a href='%s?action=logout'>"._("Back to login page")."</a>
            </body>
            </html>", $message, $_SERVER['_PHP_FILE_']);
        die();
    }
}
?>
