<?php
/**
 * This code is distributed under GNU Lesser General Public License
 * See http://www.gnu.org/copyleft/lesser.html
 *
 * How to use it:
 *
 *
 * @category   Debug
 * @package    SDebugger
 * @author     Oleg Serov <oleg@serov.name>
 * @copyright  2010 Oleg Serov <oleg@serov.name>
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public Licens
 * @version    Release: 3.4.0
 * @link       http://github.com/expolit/SDebugger
 * @since      Class available since Release 2.3.0
 */
class SDebugger
{
    /**
     * Singelton instance of class
     * @var SDebugger
     */
    protected static $_instance;

    /**
     * Config data
     * @var array
     */
    protected $_config = array(
        'log_errors' => true,
        'throw_errors' => false,
        'tmp_path' => null,
        'delay' => 60,
        // 'mail_to' => '...',
        // 'mail_from' => '...',
        'mail_charset' => 'utf-8',
        'custom_log_callback' => null
    );

    /**
     * Get SDebugger
     * @return SDebugger
     */
    public function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->setHandlers();
    }

    /**
     * Sets error and exception handler
     * @return void
     */
    public function setHandlers()
    {
        set_error_handler(array($this, 'handleError'));
        set_exception_handler(array($this, 'logException'));
    }

    /**
     * Restore error and exception handler
     * @return void
     */
    public function restoreHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Sets config.
     * Accept first argument as key-value array,
     * or name of param and value as second argument
     * @param mixed $name array or string
     * @param mixed $value
     * @return void
     */
    public function setConfig($name, $value = null)
    {
        if (is_array($name)) {
            $this->_config = array_merge($this->_config, $name);
        } else {
            $this->_config[$name] = $value;
        }
    }

    /**
     * var_export clone, without using output buffering.
     * (For calls in ob_handler)
     *
     * @param mixed $var to be exported
     * @param integer $maxLevel (recursion protect)
     * @param integer $level of current indent
     * @return string
     */
    public static function varExport($var, $maxLevel = 10, $level = 0)
    {
        $escapes = "\"\r\t\x00\$";
        $tab = '    ';


        if (is_bool($var)) {
            return $var ? 'TRUE' : 'FALSE';
        } elseif (is_string($var)) {
            return '"' . addcslashes($var, $escapes) . '"';
        } elseif (is_float($var) || is_int($var)) {
            return $var;
        } elseif (is_null($var)) {
            return 'NULL';
        } elseif (is_resource($var)) {
            return 'NULL /* ' . $var . ' */';
        }

        if ($maxLevel < $level) {
            return 'NULL /* ' . (string) $var . ' MAX LEVEL ' . $maxLevel . " REACHED*/";
        }

        if (is_array($var)) {
            $return = "array(\n";
        } else {
            $return = get_class($var) . "::__set_state(array(\n";
        }

        $offset = str_repeat($tab, $level + 1);


        foreach ((array) $var as $key => $value) {
            $return .= $offset;
            if (is_int($key)) {
                $return .= $key;
            } else {
                $return .= '"' . addcslashes($key, $escapes). '"';
            }
            $return .= ' => ' . self::varExport($value, $maxLevel, $level + 1) . ",\n";
        }


        return $return
            . str_repeat($tab, $level)
            . (is_array($var) ? ')' : '))');

    }

    /**
     * Write msg to error log
     *
     * @param string $msg multi line
     */
    public function errorLog($msg)
    {
        if (!ini_get('log_errors') || !self::$_config['log_errors']) {
            return;
        }

        foreach (explode("\n", $msg) as $line) {
            error_log(rtrim($line));
        }

        $this->errorLogCustom($msg);

    }

    /**
     * Error handler
     *
     * @param $errNo
     * @param $errStr
     * @param $errFile
     * @param $errLine
     * @return void
     */
    public function handleError($errNo, $errStr, $errFile, $errLine)
    {
        if (!$this->__config['log_errors']) {
            return false;
        }

        if (!($errNo & error_reporting())) {
            return false;
        }

        if ($this->_checkSended($errFile, $errLine)) {
            return false;
        }

        $types = array(
            'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR',
            'E_CORE_WARNING', 'E_COMPILE_ERROR', 'E_COMPILE_WARNING',
            'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_STRICT',
            'E_RECOVERABLE_ERROR'
        );

        $className = 'E_EXCEPTION';

        foreach ($types as $t) {
            $e = constant($t);
            if ($errNo & $e) {
                $className = $t;
                break;
            }
        }

        $e = new SDebuggerException(
            $errNo,
            $className . ': ' . $errStr,
            $errFile,
            $errLine
        );

        $this->logException($e);

        if ($this->_config['throw_errors']) {
            throw $e;
        }

        return true;
    }

    /**
     * Logs Exception
     * @param Exception $e
     * @return void
     */
    public function logException(Exception $e)
    {
        if (!$this->_config['log_errors']) {
            return ;
        }

        $need_mail_report = !$this->_checkSended($e->getFile(), $e->getLine());

        $msg = 'PHP Exception: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        $msg .= 'PHP Exception: ' . get_class($e) . ': ' . $e->getFile() . ':' . $e->getLine() . "\n";
        $msg .= 'PHP Exception Trace: ' . $e->getTraceAsString();

        if ($need_detail_log) {
            if ($this->_config['custom_log_format']) {
                $log = call_user_funct($this->_config['custom_log_format']);
            } else {
                $log = array(
                    '_SERVER' => $_SERVER,
                    '_SESSION' => @$_SESSION,
                    '_POST' => @$_POST,
                );
            }
            $msg .= is_string($log) ? $log : self::varExport($log);
            $log = null;
        }

        if ($need_mail_report && !$this->isDebugMode()) {
            $this->_markSended($e->getFile(), $e->getLine());
            $this->_mail($msg, 'PHP Exception: ' . get_class($e) . ': ' . $e->getMessage());
        }


        $this->errorLog($msg);
    }

    /**
     * Gets mark file name
     * @param string $file
     * @param int $line
     * @return string
     */
    protected function _getMarkFileName($file, $line)
    {
        return $this->_config['tmp_path'] . get_class($this) . '_' . md5($file . ':' . $line);
    }

    /**
     * Mark error as sended
     * @param string $file
     * @param int $line
     * @return void
     */
    protected function _markSended($file, $line)
    {
        $fname = $this->_getMarkFileName($file, $line);

        if (file_exists($fname)) {
            touch($fname);
        } else {
            if (!file_exists(dirname($fname))) {
                mkdir(dirname($fname), 0777);
            }

            file_put_contents($fname, $file . ':' . $line . "\n" . date('r'));
            chmod($fname, 0777);
        }
    }

    /**
     * Check if error was not already sended
     * @param string $file
     * @param int $line
     * @return boolean
     */
    protected function _markCheck($file, $line)
    {
        $fname = $this->_getMarkFileName($file, $line);
        return file_exists($fname) && filemtime($fname) > time() - $this->_config['delay'];
    }

    /**
     * Mails message and record it in error log
     *
     * @param text $subject
     * @param text $text
     * @return void
     */
    public function mail($subject, $text)
    {
        if (!is_string($text)) {
            $text = $this->varExport($text);
        }

        $this->_mail('PHP Msg: ' . $subject, $text);

        $this->errorLog('PHP Msg: ' . $subject . "\nPHP Msg detail: " . $text);
    }

    /**
     * Real send mail, only send msg.
     * @param string $subject
     * @param string $text
     * @return void
     */
    protected function _mail($subject, $text)
    {
        if (empty($this->_config['mail_to'])) {
            return;
        }

        $headers = array(
            'Content-Type: text/plain; charset="' . $this->_config['mail_charset'] . '"'
        );

        if (!empty($this->_config['mail_from'])) {
            $headers[] = 'From: ' . $this->_config['mail_from'];
        }

        mail(
            $this->_config['mail_to'],
            $subject,
            $text,
            join("\r\n", $headers)
        );
    }

}

class SDebuggerException extends Exception
{
    /**
     * Constructor
     * @param $no
     * @param $str
     * @param $file
     * @param $line
     * @return void
     */
    public function __construct($no = 0, $str = null, $file = null, $line = 0)
    {
        parent::__construct($str, $no);
        $this->file = $file;
        $this->line = $line;
    }
}



