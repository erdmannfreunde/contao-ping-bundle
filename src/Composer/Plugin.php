<?php

namespace ErdmannFreunde\Ping\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpClient\HttpClient;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    public function __construct(private Filesystem $filesystem, private Config $config)
    {
    }

    public function ping(PackageEvent $event)
    {

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
                sprintf('[OK] "%s"', $response->getContent()),
                true,
                IOInterface::VERY_VERBOSE
            );
        } catch (\Exception $e) {
            $event->getIO()->write(
                sprintf('[ERROR] Error registering installation: %s', $e->getMessage()),
                true,
                IOInterface::VERY_VERBOSE
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
