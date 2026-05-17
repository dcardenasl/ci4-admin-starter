<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Tests for badge_helper.php functions.
 *
 * @internal
 */
final class BadgeHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Load the helper
        helper('badge');
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    // ─── status_badge() ────────────────────────────────────────────

    public function testStatusBadgeReturnsGreenForActiveStatus(): void
    {
        $result = status_badge('active');
        $this->assertStringContainsString('bg-green-100', $result);
        $this->assertStringContainsString('text-green-800', $result);
    }

    public function testStatusBadgeReturnsGreenForApprovedStatus(): void
    {
        $result = status_badge('approved');
        $this->assertStringContainsString('bg-green-100', $result);
    }

    public function testStatusBadgeReturnsYellowForPendingStatus(): void
    {
        $result = status_badge('pending');
        $this->assertStringContainsString('bg-yellow-100', $result);
        $this->assertStringContainsString('text-yellow-800', $result);
    }

    public function testStatusBadgeReturnsYellowForPendingApprovalStatus(): void
    {
        $result = status_badge('pending_approval');
        $this->assertStringContainsString('bg-yellow-100', $result);
    }

    public function testStatusBadgeReturnsRedForSuspendedStatus(): void
    {
        $result = status_badge('suspended');
        $this->assertStringContainsString('bg-red-100', $result);
        $this->assertStringContainsString('text-red-800', $result);
    }

    public function testStatusBadgeReturnsRedForRejectedStatus(): void
    {
        $result = status_badge('rejected');
        $this->assertStringContainsString('bg-red-100', $result);
    }

    public function testStatusBadgeReturnsGrayForUnknownStatus(): void
    {
        $result = status_badge('unknown_status');
        $this->assertStringContainsString('bg-gray-100', $result);
    }

    public function testStatusBadgeHandlesNullValue(): void
    {
        $result = status_badge(null);
        $this->assertStringContainsString('bg-gray-100', $result);
    }

    public function testStatusBadgeIsCaseInsensitive(): void
    {
        $result1 = status_badge('ACTIVE');
        $result2 = status_badge('Active');
        $result3 = status_badge('active');

        $this->assertStringContainsString('bg-green-100', $result1);
        $this->assertStringContainsString('bg-green-100', $result2);
        $this->assertStringContainsString('bg-green-100', $result3);
    }

    // ─── localized_status() ────────────────────────────────────────

    public function testLocalizedStatusReturnsLocalizedYesForActive(): void
    {
        Services::language()->setLocale('en');
        $result = localized_status('active');
        $this->assertStringContainsString('Yes', $result);
    }

    public function testLocalizedStatusReturnsLocalizedNoForInactive(): void
    {
        Services::language()->setLocale('en');
        $result = localized_status('inactive');
        $this->assertStringContainsString('No', $result);
    }

    public function testLocalizedStatusReturnsLocalizedPendingForPending(): void
    {
        Services::language()->setLocale('en');
        $result = localized_status('pending');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testLocalizedStatusReturnsRawValueForUnknownStatus(): void
    {
        $result = localized_status('unknown_status_xyz');
        $this->assertSame('unknown_status_xyz', $result);
    }

    // ─── audit_action_badge() ──────────────────────────────────────

    public function testAuditActionBadgeReturnsGreenForCreate(): void
    {
        $result = audit_action_badge('create');
        $this->assertStringContainsString('bg-green-100', $result);
    }

    public function testAuditActionBadgeReturnsBlueForUpdate(): void
    {
        $result = audit_action_badge('update');
        $this->assertStringContainsString('bg-blue-100', $result);
    }

    public function testAuditActionBadgeReturnsRedForDelete(): void
    {
        $result = audit_action_badge('delete');
        $this->assertStringContainsString('bg-red-100', $result);
    }

    public function testAuditActionBadgeReturnsBrandForLogin(): void
    {
        $result = audit_action_badge('login');
        $this->assertStringContainsString('bg-brand-100', $result);
    }

    public function testAuditActionBadgeReturnsGrayForUnknownAction(): void
    {
        $result = audit_action_badge('unknown_action');
        $this->assertStringContainsString('bg-gray-100', $result);
    }

    // ─── localized_audit_action() ──────────────────────────────────

    public function testLocalizedAuditActionReturnsLocalizedValue(): void
    {
        Services::language()->setLocale('en');
        $result = localized_audit_action('create');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testLocalizedAuditActionReturnsRawValueForUnknown(): void
    {
        $result = localized_audit_action('unknown_action_xyz');
        $this->assertSame('unknown_action_xyz', $result);
    }

    // ─── audit_result_badge() ──────────────────────────────────────

    public function testAuditResultBadgeReturnsGreenForSuccess(): void
    {
        $result = audit_result_badge('success');
        $this->assertStringContainsString('bg-green-100', $result);
    }

    public function testAuditResultBadgeReturnsRedForFailure(): void
    {
        $result = audit_result_badge('failure');
        $this->assertStringContainsString('bg-red-100', $result);
    }

    public function testAuditResultBadgeReturnsOrangeForDenied(): void
    {
        $result = audit_result_badge('denied');
        $this->assertStringContainsString('bg-orange-100', $result);
    }

    public function testAuditResultBadgeReturnsGrayForUnknown(): void
    {
        $result = audit_result_badge('unknown');
        $this->assertStringContainsString('bg-gray-100', $result);
    }

    // ─── localized_audit_result() ──────────────────────────────────

    public function testLocalizedAuditResultReturnsLocalizedValue(): void
    {
        Services::language()->setLocale('en');
        $result = localized_audit_result('success');
        $this->assertSame(lang('Audit.result_success'), $result);
    }

    public function testLocalizedAuditResultReturnsRawValueForUnknown(): void
    {
        $result = localized_audit_result('unexpected_result');
        $this->assertSame('unexpected_result', $result);
    }

    // ─── audit_severity_badge() ───────────────────────────────────

    public function testAuditSeverityBadgeReturnsBlueForInfo(): void
    {
        $result = audit_severity_badge('info');
        $this->assertStringContainsString('bg-blue-50', $result);
        $this->assertStringContainsString('text-blue-700', $result);
    }

    public function testAuditSeverityBadgeReturnsAmberForWarning(): void
    {
        $result = audit_severity_badge('warning');
        $this->assertStringContainsString('bg-amber-50', $result);
    }

    public function testAuditSeverityBadgeReturnsRedForCritical(): void
    {
        $result = audit_severity_badge('critical');
        $this->assertStringContainsString('bg-red-100', $result);
        $this->assertStringContainsString('font-bold', $result);
    }

    public function testAuditSeverityBadgeReturnsGrayForUnknown(): void
    {
        $result = audit_severity_badge('unknown');
        $this->assertStringContainsString('bg-gray-100', $result);
    }

    // ─── localized_audit_severity() ────────────────────────────────

    public function testLocalizedAuditSeverityReturnsLocalizedValue(): void
    {
        Services::language()->setLocale('en');
        $result = localized_audit_severity('info');
        $this->assertSame(lang('Audit.severity_info'), $result);
    }

    public function testLocalizedAuditSeverityReturnsRawValueForUnknown(): void
    {
        $result = localized_audit_severity('unexpected_severity');
        $this->assertSame('unexpected_severity', $result);
    }

    // ─── health_tone_badge() ───────────────────────────────────────

    public function testHealthToneBadgeReturnsGreenForUp(): void
    {
        $result = health_tone_badge('up');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dot', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('bg', $result);
        $this->assertStringContainsString('bg-green', $result['dot']);
        $this->assertStringContainsString('text-green', $result['text']);
        $this->assertStringContainsString('bg-green', $result['bg']);
    }

    public function testHealthToneBadgeReturnsAmberForDegraded(): void
    {
        $result = health_tone_badge('degraded');

        $this->assertIsArray($result);
        $this->assertStringContainsString('bg-amber', $result['dot']);
        $this->assertStringContainsString('text-amber', $result['text']);
        $this->assertStringContainsString('bg-amber', $result['bg']);
    }

    public function testHealthToneBadgeReturnsRedForDown(): void
    {
        $result = health_tone_badge('down');

        $this->assertIsArray($result);
        $this->assertStringContainsString('bg-red', $result['dot']);
        $this->assertStringContainsString('text-red', $result['text']);
        $this->assertStringContainsString('bg-red', $result['bg']);
    }

    public function testHealthToneBadgeReturnsRedForUnknownState(): void
    {
        $result = health_tone_badge('unknown');

        $this->assertIsArray($result);
        $this->assertStringContainsString('bg-red', $result['dot']);
    }

    public function testHealthToneBadgeHandlesNull(): void
    {
        $result = health_tone_badge(null);

        $this->assertIsArray($result);
        $this->assertStringContainsString('bg-red', $result['dot']);
    }

    public function testHealthToneBadgeReturnArrayStructure(): void
    {
        $result = health_tone_badge('up');

        $this->assertCount(3, $result);
        $this->assertSame(['dot', 'text', 'bg'], array_keys($result));
    }
}
