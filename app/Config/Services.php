<?php

namespace Config;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use App\Services\AuditApiService;
use App\Services\AuthApiService;
use App\Services\FileApiService;
use App\Services\HealthApiService;
use App\Services\MetricsApiService;
use App\Services\ReportApiService;
use App\Services\UserApiService;
use CodeIgniter\Config\BaseService;

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
    public static function apiClient(bool $getShared = true): ApiClient
    {
        if ($getShared) {
            /** @var ApiClient */
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

    public static function reportApiService(bool $getShared = true): ReportApiService
    {
        if ($getShared) {
            /** @var ReportApiService */
            return static::getSharedInstance('reportApiService');
        }

        return new ReportApiService(static::apiClient());
    }
}
