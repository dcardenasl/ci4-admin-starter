<?php

declare(strict_types=1);

namespace Config;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use App\Modules\ApiKeys\Services\ApiKeyApiService;
use App\Modules\ApiKeys\Services\ApiKeyApiServiceInterface;
use App\Modules\Audit\Services\AuditApiService;
use App\Modules\Audit\Services\AuditApiServiceInterface;
use App\Modules\Auth\Services\AuthApiService;
use App\Modules\Auth\Services\AuthApiServiceInterface;
use App\Modules\Dashboard\Services\HealthApiService;
use App\Modules\Dashboard\Services\HealthApiServiceInterface;
use App\Modules\Files\Services\FileApiService;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Modules\Iam\Services\RoleApiService;
use App\Modules\Iam\Services\RoleApiServiceInterface;
use App\Modules\Metrics\Services\MetricsApiService;
use App\Modules\Metrics\Services\MetricsApiServiceInterface;
use App\Modules\Profile\Services\ProfileApiService;
use App\Modules\Profile\Services\ProfileApiServiceInterface;
use App\Modules\Users\Services\UserApiService;
use App\Modules\Users\Services\UserApiServiceInterface;
use App\Support\Requests\FormRequestInterface;
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

    public static function authApiService(bool $getShared = true): AuthApiServiceInterface
    {
        if ($getShared) {
            /** @var AuthApiService */
            return static::getSharedInstance('authApiService');
        }

        return new AuthApiService(static::apiClient());
    }

    public static function fileApiService(bool $getShared = true): FileApiServiceInterface
    {
        if ($getShared) {
            /** @var FileApiService */
            return static::getSharedInstance('fileApiService');
        }

        return new FileApiService(static::apiClient());
    }

    public static function userApiService(bool $getShared = true): UserApiServiceInterface
    {
        if ($getShared) {
            /** @var UserApiService */
            return static::getSharedInstance('userApiService');
        }

        return new UserApiService(static::apiClient());
    }

    public static function auditApiService(bool $getShared = true): AuditApiServiceInterface
    {
        if ($getShared) {
            /** @var AuditApiService */
            return static::getSharedInstance('auditApiService');
        }

        return new AuditApiService(static::apiClient());
    }

    public static function apiKeyApiService(bool $getShared = true): ApiKeyApiServiceInterface
    {
        if ($getShared) {
            /** @var ApiKeyApiService */
            return static::getSharedInstance('apiKeyApiService');
        }

        return new ApiKeyApiService(static::apiClient());
    }

    public static function metricsApiService(bool $getShared = true): MetricsApiServiceInterface
    {
        if ($getShared) {
            /** @var MetricsApiService */
            return static::getSharedInstance('metricsApiService');
        }

        return new MetricsApiService(static::apiClient());
    }

    public static function healthApiService(bool $getShared = true): HealthApiServiceInterface
    {
        if ($getShared) {
            /** @var HealthApiService */
            return static::getSharedInstance('healthApiService');
        }

        return new HealthApiService(static::apiClient(), config('ApiClient')->healthPaths);
    }

    public static function profileApiService(bool $getShared = true): ProfileApiServiceInterface
    {
        if ($getShared) {
            /** @var ProfileApiService */
            return static::getSharedInstance('profileApiService');
        }

        return new ProfileApiService(static::apiClient());
    }

    public static function roleApiService(bool $getShared = true): RoleApiServiceInterface
    {
        if ($getShared) {
            /** @var RoleApiService */
            return static::getSharedInstance('roleApiService');
        }

        return new RoleApiService(static::apiClient());
    }
}
