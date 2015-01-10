<?php

use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Rah\Danpu\Import;

class kernel
{
    public $sourcePath;

    public $targetPath;

    public $newDbName;

    public $config;

    public $newConfig;

    public function __construct($argv)
    {
        $this->sourcePath = $argv[1];
        $this->targetPath = $argv[2];
        $this->newDbName  = $argv[3];

        require_once __DIR__ . '/vendor/autoload.php';
        require_once $this->sourcePath . '/configuration.php';

        $this->config    = new JConfig();
        $this->newConfig = new JConfig();

        $this->copyFile();
        $this->createConfig();
        $this->createConfigFile();
        $this->createDb();
        $this->exportSql();
        $this->importSql();
    }

    public function copyFile()
    {
        system("cp -r {$this->sourcePath} {$this->targetPath}");
    }

    public function createDb()
    {
        $conn = new mysqli($this->newConfig->host, $this->newConfig->user, $this->newConfig->password);

        if ($conn->connect_error)
            die("Connection failed: " . $conn->connect_error);

        $q = $conn->query("CREATE DATABASE {$this->newDbName} COLLATE utf8_unicode_ci");

        if ($q !== true) {
            printf("Errormessage: %s\n", $conn->error);

            die("create failed.");
        }

        $conn->close();
    }

    public function exportSql()
    {
        try {
            $dump = new Dump;
            $dump
                ->file($this->targetPath . '/main.sql')
                ->dsn("mysql:dbname={$this->config->db};host={$this->config->host}")
                ->user($this->config->user)
                ->pass($this->config->password)
                ->tmp($this->newConfig->tmp_path);

            new Export($dump);
        } catch (\Exception $e) {
            die('Export failed with message: ' . $e->getMessage());
        }
    }

    public function importSql()
    {
        try {
            $dump = new Dump;
            $dump
                ->file($this->targetPath . '/main.sql')
                ->dsn("mysql:dbname={$this->newConfig->db};host={$this->newConfig->host}")
                ->user($this->newConfig->user)
                ->pass($this->newConfig->password)
                ->tmp($this->newConfig->tmp_path);

            new Import($dump);
        } catch (\Exception $e) {
            die('Export failed with message: ' . $e->getMessage());
        }
    }

    public function createConfig()
    {
        $this->newConfig->db       = $this->newDbName;
        $this->newConfig->tmp_path = $this->targetPath . '/tmp';
        $this->newConfig->log_path = $this->targetPath . '/logs';
    }

    public function createConfigFile()
    {
        $file = $this->targetPath . '/configuration.php';

        if (!file_exists($file))
            die('is this joomla dir ?');

        system("rm {$file}");

        $configuration = $this->objectToString($this->newConfig, array('class' => 'JConfig', 'closingtag' => false));

        file_put_contents($file, $configuration);
    }

    public function objectToString($object, $params = array())
    {
        // Build the object variables string
        $vars = '';

        foreach (get_object_vars($object) as $k => $v)
        {
            if (is_scalar($v))
            {
                $vars .= "\tpublic $" . $k . " = '" . addcslashes($v, '\\\'') . "';\n";
            }
            elseif (is_array($v) || is_object($v))
            {
                $vars .= "\tpublic $" . $k . " = " . $this->getArrayString((array) $v) . ";\n";
            }
        }

        $str = "<?php\nclass " . $params['class'] . " {\n";
        $str .= $vars;
        $str .= "}";

        // Use the closing tag if it not set to false in parameters.
        if (!isset($params['closingtag']) || $params['closingtag'] !== false)
        {
            $str .= "\n?>";
        }

        return $str;
    }
}

new kernel($argv);