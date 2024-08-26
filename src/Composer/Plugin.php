<?php

namespace ErdmannFreunde\Ping\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use Symfony\Component\HttpClient\HttpClient;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private static bool $pingCalled = false;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // nothing to do here
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    /** @noinspection PhpUnused */
    public function ping(PackageEvent $event)
    {
        if (self::$pingCalled) {
            return;
        }

        self::$pingCalled = true; 

        $packageName = $event->getComposer()->getPackage()->getName() ?? 'not found';
        $packageVersion = $event->getComposer()->getPackage()->getVersion() ?? 'not found';
        $packageOrderId = $event->getComposer()->getPackage()->getExtra()['theme-order-id'] ?? 'not found';
        $domain = $_SERVER['HTTP_HOST'] ?? 'CLI-Installation (no domain available)';
        $url = 'https://ping.erdmann.app/api/v1/log';

        $postData = [
            'package' => $packageName,
            'domain' => $domain,
            'version' => $packageVersion,
            'orderId' => $packageOrderId
        ];

        $client = HttpClient::create();

        try {
            $response = $client->request('POST', $url, [
                'body' => $postData,
            ]);

            $event->getIO()->write(
                sprintf("\n\n[OK] \"%s\"\n\n", $response->getContent()),
                true,
                IOInterface::NORMAL
            );
        } catch (\Exception $e) {
            $event->getIO()->write(
                sprintf("\n\n[ERROR] Error registering installation: \"%s\"\n\n", $e->getMessage()),
                true,
                IOInterface::NORMAL
            );
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'ping',
            PackageEvents::POST_PACKAGE_UPDATE => 'ping',
        ];
    }
}
