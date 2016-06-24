// Init application mode
if (empty($_ENV['SLIM_MODE'])) {
    $_ENV['SLIM_MODE'] = (getenv('SLIM_MODE'))
        ? getenv('SLIM_MODE') : 'development';
}

// Init and load configuration
$config = array();

$configFile = dirname(__FILE__) . '/share/config/'
    . $_ENV['SLIM_MODE'] . '.php';

if (is_readable($configFile)) {
    require_once $configFile;
} else {
    require_once dirname(__FILE__) . '/share/config/default.php';
}

// Create Application
$app = new API\Application($config['app']);

// Get log writer
$log = $app->getLog();

// Init database
try {
    
    if (!empty($config['db'])) {
        \ORM::configure($config['db']['dsn']);
        if (!empty($config['db']['username']) && !empty($config['db']['password'])) {
            \ORM::configure('username', $config['db']['username']);
            \ORM::configure('password', $config['db']['password']);
        }
    }

} catch (\PDOException $e) {
    $log->error($e->getMessage());
}

// Add middleware
// Your middleware here...\