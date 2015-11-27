<?php namespace Anomaly\LocalStorageAdapterExtension\Command;

use Anomaly\ConfigurationModule\Configuration\Contract\ConfigurationRepositoryInterface;
use Anomaly\FilesModule\Disk\Adapter\AdapterFilesystem;
use Anomaly\FilesModule\Disk\Contract\DiskInterface;
use Anomaly\Streams\Platform\Application\Application;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\MountManager;

/**
 * Class LoadDisk
 *
 * @link          http://anomaly.is/streams-platform
 * @author        AnomalyLabs, Inc. <hello@anomaly.is>
 * @author        Ryan Thompson <ryan@anomaly.is>
 * @package       Anomaly\LocalStorageAdapterExtension\Command
 */
class LoadDisk implements SelfHandling
{

    /**
     * The disk instance.
     *
     * @var DiskInterface
     */
    protected $disk;

    /**
     * Create a new LoadDisk instance.
     *
     * @param DiskInterface $disk
     */
    public function __construct(DiskInterface $disk)
    {
        $this->disk = $disk;
    }

    /**
     * Handle the command.
     *
     * @param MountManager                     $flysystem
     * @param Application                      $application
     * @param FilesystemManager                $filesystem
     * @param ConfigurationRepositoryInterface $configuration
     * @return AdapterFilesystem
     */
    public function handle(
        MountManager $flysystem,
        Application $application,
        FilesystemManager $filesystem,
        ConfigurationRepositoryInterface $configuration
    ) {
        $private = $configuration->value(
            'anomaly.extension.local_storage_adapter::private',
            $this->disk->getSlug(),
            false
        );

        if ($private) {
            $root = $application->getStoragePath("files-module/{$this->disk->getSlug()}");
        } else {
            $root = $application->getAssetsPath("files-module/{$this->disk->getSlug()}");
        }

        $driver = new AdapterFilesystem(
            $this->disk,
            new Local($root)
        );

        $flysystem->mountFilesystem($this->disk->getSlug(), $driver);

        $filesystem->extend(
            $this->disk->getSlug(),
            function () use ($driver) {
                return $driver;
            }
        );
    }
}
