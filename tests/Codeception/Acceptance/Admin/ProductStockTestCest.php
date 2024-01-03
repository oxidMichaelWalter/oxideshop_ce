<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace Acceptance\Admin;

use Codeception\Attribute\Group;
use Codeception\Util\Fixtures;
use DateTime;
use OxidEsales\EshopCommunity\Tests\Codeception\Support\AcceptanceTester;

#[Group('admin', 'product', 'stock')]
final class ProductStockTestCest
{
    private string $productID = '1000';

    public function setLowStockMessage(AcceptanceTester $I): void
    {
        $I->wantToTest('Set low stock message for product');

        $productsMainPage = $I->loginAdmin()->openProducts();
        $productMainTab = $productsMainPage->find($productsMainPage->searchNumberInput, $this->productID);
        $stockTab = $productMainTab->openStockTab();
        $lowStockMessage = 'This product is in low stock' . $this->productID;

        $I->amGoingTo('Set and activate low stock message');

        $stockTab->checkLowStockMessageOption()
            ->setLowStockMessageValue($lowStockMessage)
            ->save();

        $stockTab->seeLowStockMessageSelected();
        $stockTab->seeLowStockMessageValue($lowStockMessage);

        $I->amGoingTo('Disable low stock message');

        $stockTab->uncheckLowStockMessageOption()
            ->save();

        $stockTab->dontSeeLowStockMessageSelected();
    }
}
