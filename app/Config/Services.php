<?php

namespace Config;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use App\Requests\FormRequestInterface;
use App\Modules\Audit\Services\AuditApiService;
use App\Modules\Auth\Services\AuthApiService;
use App\Services\CatalogApiService;
use App\Modules\Files\Services\FileApiService;
use App\Services\HealthApiService;
use App\Modules\ApiKeys\Services\ApiKeyApiService;
use App\Modules\Metrics\Services\MetricsApiService;
use App\Modules\Profile\Services\ProfileApiService;
use App\Modules\Users\Services\UserApiService;
use CodeIgniter\Config\BaseService;
use InvalidArgumentException;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function formRequest(string $class, bool $getShared = true): FormRequestInterface
    {
        if ($getShared) {
            /** @var FormRequestInterface */
            return static::getSharedInstance('formRequest', $class);
        }

        if (! class_exists($class)) {
            throw new InvalidArgumentException('Form request class does not exist: ' . $class);
        }

        $request = new $class(service('request'), service('validation'));

        if (! $request instanceof FormRequestInterface) {
            throw new InvalidArgumentException('Form request must implement FormRequestInterface: ' . $class);
        }

        return $request;
    }

    public static function apiClient(bool $getShared = true): ApiClientInterface
    {
        if ($getShared) {
            /** @var ApiClientInterface */
            return static::getSharedInstance('apiClient');
        }

        return new ApiClient(config('ApiClient'));
    }

    public static function authApiService(bool $getShared = true): AuthApiService
    {
        if ($getShared) {
            /** @var AuthApiService */
            return static::getSharedInstance('authApiService');
        }

        return new AuthApiService(static::apiClient());
    }

    public static function fileApiService(bool $getShared = true): FileApiService
    {
        if ($getShared) {
            /** @var FileApiService */
            return static::getSharedInstance('fileApiService');
        }

        return new FileApiService(static::apiClient());
    }

    public static function userApiService(bool $getShared = true): UserApiService
    {
        if ($getShared) {
            /** @var UserApiService */
            return static::getSharedInstance('userApiService');
        }

        return new UserApiService(static::apiClient());
    }

    public static function auditApiService(bool $getShared = true): AuditApiService
    {
        if ($getShared) {
            /** @var AuditApiService */
            return static::getSharedInstance('auditApiService');
        }

        return new AuditApiService(static::apiClient());
    }

    public static function apiKeyApiService(bool $getShared = true): ApiKeyApiService
    {
        if ($getShared) {
            /** @var ApiKeyApiService */
            return static::getSharedInstance('apiKeyApiService');
        }

        return new ApiKeyApiService(static::apiClient());
    }

    public static function metricsApiService(bool $getShared = true): MetricsApiService
    {
        if ($getShared) {
            /** @var MetricsApiService */
            return static::getSharedInstance('metricsApiService');
        }

        return new MetricsApiService(static::apiClient());
    }

    public static function healthApiService(bool $getShared = true): HealthApiService
    {
        if ($getShared) {
            /** @var HealthApiService */
            return static::getSharedInstance('healthApiService');
        }

        return new HealthApiService(static::apiClient());
    }

    public static function catalogApiService(bool $getShared = true): CatalogApiService
    {
        if ($getShared) {
            /** @var CatalogApiService */
            return static::getSharedInstance('catalogApiService');
        }

        return new CatalogApiService(static::apiClient());
    }

    public static function profileApiService(bool $getShared = true): ProfileApiService
    {
        if ($getShared) {
            /** @var ProfileApiService */
            return static::getSharedInstance('profileApiService');
        }

        return new ProfileApiService(static::apiClient());
    }

}
