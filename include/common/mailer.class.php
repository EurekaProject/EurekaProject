
<?php
class mailer
{
    var $securestring;
    var $regex_address = '/^[-+.\w]{1,64}@[-.\w]{1,64}\.[-.\w]{2,6}$/i';
    var $regex_head = '/[\n\r]/';
    var $nl = '\r\n';
    var $to = 'moi@domaine.com';
    var $format = 'text/plain';
    var $timeout = 120;
    var $msgheader = "";
    var $msgfooter = "";
    var $alert = "";
    var $name = "";
    var $message = "";
    var $subject = "";

    function __construct($to)
    {
        $this->to = $to;
        $this->boundary = md5(uniqid(microtime(), TRUE));
        if (empty($this->msgheader))
        {
            if ($this->format === 'text/html')
            {
                $this->msgheader .= "<body>";
                $this->msgheader .= "<p>"._("Hello").",</p>";
                $this->msgheader .= "<p>";
                $this->msgheader .= _("This mail was sending from ").$_SERVER["PHP_SELF"]._(" by ").$this->name."<br/>";
                $this->msgheader .= _("The message is");
                $this->msgheader .= "</p>";
                $this->msgheader .= "<div></div>";
                $this->msgheader .= "<div>";
                $this->msgfooter .= "</div>";
                $this->msgfooter .= "<div></div>";
                $this->msgfooter .= "</body>".$this->nl;
            }
            else
            {
                $this->msgheader .= _("Hello").",".$this->nl.$this->nl;
                $this->msgheader .= _("This mail was sending from ").$_SERVER["PHP_SELF"]._(" by ").$this->name.$this->nl.$this->nl;
                $this->msgheader .= _("The message is");
                $this->msgheader .= $this->nl.'***************************'.$this->nl;
                $this->msgfooter .= $this->nl.'***************************'.$this->nl;
            }
        }
    }
    function _destruct()
    {
        $this->_unattach();
    }
    public function securestring($string, $regex)
    {
        if (get_magic_quotes_gpc())
            return $this->_securestring_gpc($string, $regex);
        else
            return $this->_securestring($string, $regex);
    }
    private function _securestring_gpc($string, $regex)
    {
        $ret = tripslashes(trim($string));
        if (preg_match($regex, $ret))
            return "";
        return $ret;
    }
    private function _securestring($string, $regex)
    {
        $ret = trim($string);
        if (preg_match($regex, $ret))
            return "";
        return $ret;
    }
    public function attach($filename)
    {
    }
    private function _unattach()
    {
    }
    public function send($name,$from,$subject,$message)
    {
        if (isset($_COOKIE['sent']))
        {
            $this->alert = _("Wait before to resent");
            return $this->alert;
        }
        $this->name = $this->securestring($name,$this->regex_head);
        if (empty($this->name))
            $ret = "'"._("name")."' "._("not set");
        $this->from = $this->securestring($from,$this->regex_address);
        if (empty($this->from))
            $ret = "'"._("from")."' "._("not set");
        $this->subject = $this->securestring($subject,$this->regex_head);
        if (empty($this->subject))
            $ret = "'"._("subject")."' "._("not set");
        if (isset($message))
            $this->message = $this->securestring($message,$this->regex_head);
        if (empty($this->message))
            $ret = "'"._("message")."' "._("not set");

        $server = ($_SERVER["HTTPS"]?"https://":"http://").$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"];
        if($_SERVER['HTTP_REFERER'] !== $server)
        {
            header('Location: '.$_SERVER["PHP_SELF"]);
            die;
        }

        $msg = "";
        $headers = 'From: '.$this->name.' <'.$this->from.'>'.$this->nl;
        $headers .= 'Mime-Version: 1.0'.$this->nl;
        $headers .= 'Content-Type: multipart/mixed;boundary='.$this->boundary.$this->nl;
        $headers .= $this->nl;

        $msg .= '--'.$this->boundary.$this->nl;
        $msg .= 'Content-type:'.$this->format.';charset=utf-8'.$this->nl;
        $msg .= 'Content-transfer-encoding:8bit'.$this->nl;

        $msg .= $this->msgheader;
        $msg .= $this->message;
        $msg .= $this->msgfooter;
 
        if (isset($this->attach))
        {
            foreach ($this->attach as $file)
            {
                if (file_exists($file))
                {
                    $file_type = filetype($file);
                    $file_size = filesize($file);
                 
                    $handle = fopen($file, 'r') or die('File '.$file.'can t be open');
                    $content = fread($handle, $file_size);
                    $content = chunk_split(base64_encode($content));
                    $f = fclose($handle);
                 
                    $msg .= '--'.$this->boundary.$this->nl;
                    $msg .= 'Content-type:'.$file_type.';name='.$file_name.$this->nl;
                    $msg .= 'Content-transfer-encoding:base64'.$this->nl;
                    $msg .= $content.$this->nl;
                    $msg .= '--'.$this->boundary.$this->nl;
                }
            }
        }

        if (mail($this->to, $this->subject, $msg, $headers))
        {
            $ret = _("E-Mail sended"); 
            setcookie("sent", "1", time() + $this->timeout);
            $this->_unattach();
        }
        else
        {
            $ret = _("Error on E-Mail sending");
        }
        $this->alert = $ret;
        return $ret;
    }
    public function generateEditor()
    {
        $ret = "";
        if (isset($_POST['action']))
        {
            switch ($_POST['action'])
            {
                case "send":
                    $ret = $this->send($_POST["name"],$_POST["from"],$_POST["subject"],$_POST["message"]);
                break;
                case "attach":
                    $this->attach("test");
                break;
                case "back":
                    $this->attach("test");
                break;
            }
        }
?>
<form class="form-horizontal" action="<?=$_SERVER["PHP_SELF"]?>" method="post">
    <script>
        var mailer_send = function()
        {
            $("input[name='action']").val("send").change();
        }
        var mailer_back = function()
        {
            $("input[name='action']").val("back").change();
        }
    </script>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon" id="name-label"><?=_("Name")?></span>
            <input type="text" class="form-control" name="name" placeholder="Your name" aria-describedby="name-label">
            <span class="input-group-addon" id="address-label">@<span class="sr-only"><?=_("Address")?></span></span>
            <input type="text" class="form-control" name="from" placeholder="Your e-mail address" aria-describedby="address-label">
        </div>
    </div>
    <input type="hidden" name="action" value="" />
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon" id="subject-label"><?=_("Subject")?></span>
            <input type="text" class="form-control" name="subject" placeholder="Subject" aria-describedby="subject-label" value="<?=$this->subject?>">
        </div>
        <label class="sr-only" for="message"><?=_("Message")?></label>
        <div class="input-group">
            <textarea class="form-control" id="message" name="message" cols="40" rows="4" aria-label="message" aria-describedby="message-label"><?=$this->message?></textarea>
            <div class="input-group-btn">
                <input class="btn btn-default" type="submit" value="<?=_("Send")?>" onclick="mailer_send();"/>
            </div>
        </div>
    </div>
    <div class="form-group">
        <output><?=$this->alert?></output>
    </div>
</form>
<?php
    }
};
/*
$mailer = new mailer("marc.chalain@gmail.com");
$mailer->generateEditor();
*/
