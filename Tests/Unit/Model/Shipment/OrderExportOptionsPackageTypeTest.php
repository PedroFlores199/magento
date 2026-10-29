<?php

declare(strict_types=1);

use MyParcelNL\Magento\Adapter\DeliveryOptions\DeliveryOptions;
use MyParcelNL\Magento\Adapter\DeliveryOptions\ShipmentOptions as ResolvedOptions;
use MyParcelNL\Magento\Model\Shipment\PackageType;
use MyParcelNL\Magento\Model\Shipment\ShipmentOption;
use MyParcelNL\Magento\Model\Source\DefaultOptions;

/**
 * Precedence only: what the order stored, then the configured default. Nothing here overrides a
 * stored type, also not for a pickup.
 */
function packageTypeOf(
    array $stored,
    bool  $ageCheck = false,
    int   $configuredDefault = PackageType::PACKAGE_SMALL
): int
{
    $defaultOptions = Mockery::mock(DefaultOptions::class);
    $defaultOptions->shouldReceive('getPackageType')->andReturn($configuredDefault);

    return createOrderExportOptions([
        'defaultOptions'  => $defaultOptions,
        'deliveryOptions' => DeliveryOptions::fromOrderFallback($stored),
        'chosen'        => ResolvedOptions::of([ShipmentOption::AGE_CHECK => $ageCheck]),
    ])->packageTypeToShip();
}

it('takes the package type the order stored', function () {
    $result = packageTypeOf(['packageType' => 'digital_stamp']);

    expect($result)->toBe(PackageType::DIGITAL_STAMP);
});

it('falls through to the configured default when the order stored none', function () {
    $result = packageTypeOf([]);

    expect($result)->toBe(PackageType::PACKAGE_SMALL);
});

it('keeps the stored package type when an age check applies', function () {
    // Whether a mailbox can carry an age check is the account's answer, asked at checkout.
    $result = packageTypeOf(['packageType' => 'mailbox'], true);

    expect($result)->toBe(PackageType::MAILBOX);
});

it('leaves a pickup on the type the precedence resolved, rather than forcing a package', function () {
    $result = packageTypeOf(['packageType' => 'mailbox', 'isPickup' => true]);

    expect($result)->toBe(PackageType::MAILBOX);
});
