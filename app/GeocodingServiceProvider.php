<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Geocoding\CachedGeocodingService;
use CultuurNet\UDB3\Geocoding\DefaultGeocodingService;
use CultuurNet\UDB3\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use Geocoder\Provider\GoogleMaps;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GeocodingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['geocoding_service'] = $app->share(
            function (Application $app) {
                $googleMapsApiKey = null;

                if (isset($app['geocoding_service.google_maps_api_key'])) {
                    $googleMapsApiKey = $app['geocoding_service.google_maps_api_key'];
                }

                return new DefaultGeocodingService(
                    new GoogleMaps(
                        new CurlHttpAdapter(),
                        null,
                        null,
                        true,
                        $googleMapsApiKey
                    ),
                    LoggerFactory::create($app, LoggerName::forService('geo-coordinates', 'google'))
                );
            }
        );

        $app->extend(
            'geocoding_service',
            function (GeocodingServiceInterface $geocodingService, Application $app) {
                return new CachedGeocodingService(
                    $geocodingService,
                    $app['cache']('geocoords')
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}
