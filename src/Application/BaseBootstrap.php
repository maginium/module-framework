<?php

declare(strict_types=1);

namespace Maginium\Framework\Application;

use DomainException;
use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ErrorHandler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\State;
use Magento\Framework\AppInterface;
use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\Autoload\Populator;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Profiler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A bootstrap of Magento application.
 *
 * Performs basic initialization root function: injects init parameters and creates object manager
 * Can create/run applications.
 */
class BaseBootstrap
{
    /**#@+
     * Possible errors that can be triggered by the bootstrap
     */
    public const ERR_MAINTENANCE = 901;

    public const ERR_IS_INSTALLED = 902;
    // #@-

    /**#@+
     * Initialization parameters that allow control bootstrap behavior of asserting maintenance mode or is installed
     *
     * Possible values:
     * - true -- set expectation that it is required
     * - false -- set expectation that is required not to
     * - null -- bypass the assertion completely
     *
     * If key is absent in the parameters array, the default behavior will be used
     * @see DEFAULT_REQUIRE_MAINTENANCE
     * @see DEFAULT_REQUIRE_IS_INSTALLED
     */
    public const PARAM_REQUIRE_MAINTENANCE = 'MAGE_REQUIRE_MAINTENANCE';

    public const PARAM_REQUIRE_IS_INSTALLED = 'MAGE_REQUIRE_IS_INSTALLED';
    // #@-

    /**#@+
     * Default behavior of bootstrap assertions
     */
    public const DEFAULT_REQUIRE_MAINTENANCE = false;

    public const DEFAULT_REQUIRE_IS_INSTALLED = true;
    // #@-

    /**
     * Initialization parameter for custom directory paths.
     */
    public const INIT_PARAM_FILESYSTEM_DIR_PATHS = 'MAGE_DIRS';

    /**
     * Initialization parameter for additional filesystem drivers.
     */
    public const INIT_PARAM_FILESYSTEM_DRIVERS = 'MAGE_FILESYSTEM_DRIVERS';

    /**
     * The initialization parameters (normally come from the $_SERVER).
     *
     * @var array
     */
    private $server;

    /**
     * Root directory.
     *
     * @var string
     */
    private $rootDir;

    /**
     * Object manager.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Maintenance mode manager.
     *
     * @var MaintenanceMode
     */
    private $maintenance;

    /**
     * Bootstrap-specific error code that may have been set in runtime.
     *
     * @var int
     */
    private $errorCode = 0;

    /**
     * Attribute for creating object manager.
     *
     * @var ObjectManagerFactory
     */
    private $factory;

    /**
     * Constructor.
     *
     * @param ObjectManagerFactory $factory
     * @param string $rootDir
     * @param array $initParams
     */
    public function __construct(ObjectManagerFactory $factory, $rootDir, array $initParams)
    {
        $this->factory = $factory;
        $this->rootDir = $rootDir;
        $this->server = $initParams;
        $this->objectManager = $this->factory->create($this->server);
    }

    /**
     * Populates autoloader with mapping info.
     *
     * @param string $rootDir
     * @param array $initParams
     *
     * @return void
     */
    public static function populateAutoloader($rootDir, $initParams)
    {
        $dirList = self::createFilesystemDirectoryList($rootDir, $initParams);
        $autoloadWrapper = AutoloaderRegistry::getAutoloader();
        Populator::populateMappings($autoloadWrapper, $dirList);
    }

    /**
     * Creates instance of object manager factory.
     *
     * @param string $rootDir
     * @param array $initParams
     *
     * @return ObjectManagerFactory
     */
    public static function createObjectManagerFactory($rootDir, array $initParams)
    {
        $dirList = self::createFilesystemDirectoryList($rootDir, $initParams);
        $driverPool = self::createFilesystemDriverPool($initParams);
        $configFilePool = self::createConfigFilePool();

        return new ObjectManagerFactory($dirList, $driverPool, $configFilePool);
    }

    /**
     * Creates instance of filesystem directory list.
     *
     * @param string $rootDir
     * @param array $initParams
     *
     * @return DirectoryList
     */
    public static function createFilesystemDirectoryList($rootDir, array $initParams)
    {
        $customDirs = [];

        if (isset($initParams[static::INIT_PARAM_FILESYSTEM_DIR_PATHS])) {
            $customDirs = $initParams[static::INIT_PARAM_FILESYSTEM_DIR_PATHS];
        }

        return new DirectoryList($rootDir, $customDirs);
    }

    /**
     * Creates instance of filesystem driver pool.
     *
     * @param array $initParams
     *
     * @return DriverPool
     */
    public static function createFilesystemDriverPool(array $initParams)
    {
        $extraDrivers = [];

        if (isset($initParams[static::INIT_PARAM_FILESYSTEM_DRIVERS])) {
            $extraDrivers = $initParams[static::INIT_PARAM_FILESYSTEM_DRIVERS];
        }

        return new DriverPool($extraDrivers);
    }

    /**
     * Creates instance of configuration files pool.
     *
     * @return ConfigFilePool
     */
    public static function createConfigFilePool(): ConfigFilePool
    {
        return new ConfigFilePool;
    }

    /**
     * Static method so that client code does not have to create Object Manager Factory every time Bootstrap is called.
     *
     * @param string $rootDir
     * @param array $initParams
     * @param ObjectManagerFactory $factory
     *
     * @return BaseBootstrap
     */
    public static function create($rootDir, array $initParams, ?ObjectManagerFactory $factory = null): self
    {
        self::populateAutoloader($rootDir, $initParams);

        if ($factory === null) {
            $factory = self::createObjectManagerFactory($rootDir, $initParams);
        }

        return new self($factory, $rootDir, $initParams);
    }

    /**
     * Gets the current parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->server;
    }

    /**
     * Factory method for creating application instances.
     *
     * In case of failure,
     * the application will be terminated by "exit(1)"
     *
     * @param string $type
     * @param array $arguments
     *
     * @return AppInterface | void
     */
    public function createApplication($type, $arguments = [])
    {
        try {
            $application = $this->objectManager->create($type, $arguments);

            if (! $application instanceof AppInterface) {
                throw new InvalidArgumentException("The provided class doesn't implement AppInterface: {$type}");
            }

            return $application;
        } catch (Exception $e) {
            $this->terminate($e);
        }
    }

    /**
     * Runs an application.
     *
     * @param AppInterface $application
     *
     * @return void
     *
     * phpcs:disable Magento2.Exceptions,Squiz.Commenting.FunctionCommentThrowTag
     */
    public function run(AppInterface $application)
    {
        try {
            try {
                Profiler::start('magento');
                $this->initErrorHandler();
                $this->assertMaintenance();
                $this->assertInstalled();
                $response = $application->launch();
                $response->sendResponse();
                Profiler::stop('magento');
            } catch (Exception $e) {
                Profiler::stop('magento');
                $this->objectManager->get(LoggerInterface::class)->error($e->getMessage());

                /** @var Bootstrap $app */
                $app = $this;

                if (! $application->catchException($app, $e)) {
                    throw $e;
                }
            }
        } catch (Throwable $e) {
            $this->terminate($e);
        }
    } // phpcs:enable

    /**
     * Gets the object manager instance.
     *
     * @return ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Getter for error code.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Checks whether developer mode is set in the initialization parameters.
     *
     * @return bool
     */
    public function isDeveloperMode()
    {
        $mode = 'default';

        if (isset($this->server[State::PARAM_MODE])) {
            $mode = $this->server[State::PARAM_MODE];
        } else {
            $deploymentConfig = $this->getObjectManager()->get(DeploymentConfig::class);
            $configMode = $deploymentConfig->get(State::PARAM_MODE);

            if ($configMode) {
                $mode = $configMode;
            }
        }

        return $mode === State::MODE_DEVELOPER;
    }

    /**
     * Asserts maintenance mode.
     *
     * @throws Exception
     *
     * phpcs:disable Magento2.Exceptions
     *
     * @return void
     */
    protected function assertMaintenance()
    {
        $isExpected = $this->getIsExpected(self::PARAM_REQUIRE_MAINTENANCE, self::DEFAULT_REQUIRE_MAINTENANCE);

        if ($isExpected === null) {
            return;
        }

        /** @var MaintenanceMode $maintenance */
        $this->maintenance = $this->objectManager->get(MaintenanceMode::class);

        /** @var RemoteAddress $phpRemoteAddressEnvironment */
        $phpRemoteAddressEnvironment = $this->objectManager->get(
            RemoteAddress::class,
        );
        $remoteAddress = $phpRemoteAddressEnvironment->getRemoteAddress();
        $isOn = $this->maintenance->isOn($remoteAddress ? $remoteAddress : '');

        if ($isOn && ! $isExpected) {
            $this->errorCode = self::ERR_MAINTENANCE;

            throw new Exception('Unable to proceed: the maintenance mode is enabled. ');
        }

        if (! $isOn && $isExpected) {
            $this->errorCode = self::ERR_MAINTENANCE;

            throw new Exception('Unable to proceed: the maintenance mode must be enabled first. ');
        }
    } // phpcs:enable

    /**
     * Asserts whether application is installed.
     *
     * @throws Exception
     *
     * @return void
     */
    protected function assertInstalled()
    {
        $isExpected = $this->getIsExpected(self::PARAM_REQUIRE_IS_INSTALLED, self::DEFAULT_REQUIRE_IS_INSTALLED);

        if ($isExpected === null) {
            return;
        }
        $isInstalled = $this->isInstalled();

        if (! $isInstalled && $isExpected) {
            $this->errorCode = self::ERR_IS_INSTALLED;

            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Error: Application is not installed yet. ');
        }

        if ($isInstalled && ! $isExpected) {
            $this->errorCode = self::ERR_IS_INSTALLED;

            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Error: Application is already installed. ');
        }
    }

    /**
     * Display an exception and terminate program execution.
     *
     * @param Throwable $e
     *
     * @return void
     *
     * phpcs:disable Magento2.Security.LanguageConstruct, Squiz.Commenting.FunctionCommentThrowTag
     */
    protected function terminate(Throwable $e)
    {
        /** @var Response $response */
        $response = $this->objectManager->get(Response::class);
        $response->clearHeaders();
        $response->setHttpResponseCode(500);
        $response->setHeader('Content-Type', 'text/plain');

        if ($this->isDeveloperMode()) {
            $response->setBody($e);
        } else {
            $message = "An error has happened during application run. See exception log for details.\n";

            try {
                if (! $this->objectManager) {
                    throw new DomainException;
                }
                $this->objectManager->get(LoggerInterface::class)->critical($e);
            } catch (Exception $e) {
                $message .= "Could not write error message to log. Please use developer mode to see the message.\n";
            }
            $response->setBody($message);
        }
        $response->sendResponse();

        exit(1);
    }

    /**
     * Analyze a key in the initialization parameters as "is expected" parameter.
     *
     * If there is no such key, returns default value. Otherwise casts it to boolean, unless it is null
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool|null
     */
    private function getIsExpected($key, $default)
    {
        if (array_key_exists($key, $this->server)) {
            if (isset($this->server[$key])) {
                return (bool)(int)$this->server[$key];
            }

            return;
        }

        return $default;
    }

    /**
     * Determines whether application is installed.
     *
     * @return bool
     */
    private function isInstalled()
    {
        /** @var DeploymentConfig $deploymentConfig */
        $deploymentConfig = $this->objectManager->get(DeploymentConfig::class);

        return $deploymentConfig->isAvailable();
    }

    /**
     * Sets a custom error handler.
     *
     * @return void
     */
    private function initErrorHandler()
    {
        $handler = new ErrorHandler;
        set_error_handler([$handler, 'handler']);
    }
    // phpcs:enable
}
