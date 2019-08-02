<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Test\Cart\LineItem\Group\Packager;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\Group\LineItemGroupPackagerInterface;
use Shopware\Core\Checkout\Cart\LineItem\Group\Packager\LineItemGroupCountPackager;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Test\Cart\LineItem\Group\Helpers\Traits\LineItemTestFixtureBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class LineItemGroupCountPackagerTest extends TestCase
{
    use LineItemTestFixtureBehaviour;

    /**
     * @var LineItemGroupPackagerInterface
     */
    private $packager;

    /**
     * @var SalesChannelContext
     */
    private $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packager = new LineItemGroupCountPackager();

        $this->context = $this->getMockBuilder(SalesChannelContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * This test verifies that our key identifier is not touched without recognizing it.
     * Please keep in mind, if you change the identifier, there might still
     * be old keys in the SetGroup entities in the database of shops, that
     * try to execute a packager that does not exist anymore with this key.
     *
     * @test
     * @group lineitemgroup
     */
    public function testKey()
    {
        static::assertEquals('COUNT', $this->packager->getKey());
    }

    /**
     * This test verifies that our packaging does correctly
     * return 2 items if we request that, and if more than 2 items exist.
     *
     * @test
     * @group lineitemgroup
     */
    public function testPackageDoneWhenCountReached()
    {
        $items = new LineItemCollection();
        $items->add($this->createProductItem(50.0, 0));
        $items->add($this->createProductItem(23.5, 0));
        $items->add($this->createProductItem(150.0, 0));

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(2, $items, $this->context);

        // verify we have only 2 items
        static::assertCount(2, $packageItems);

        // test that we have the first 2 from our list
        static::assertEquals(50.0, $packageItems->getFlat()[0]->getPrice()->getUnitPrice());
        static::assertEquals(23.5, $packageItems->getFlat()[1]->getPrice()->getUnitPrice());
    }

    /**
     * This test verifies, that we do not get any results, if not
     * enough items exist, to build a package.
     *
     * @test
     * @group lineitemgroup
     */
    public function testNoResultsIfNotEnoughtItems()
    {
        $items = new LineItemCollection();
        $items->add($this->createProductItem(50.0, 0));

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(2, $items, $this->context);

        // verify we dont have results, because a
        // package of 2 items couldnt be created
        static::assertCount(0, $packageItems);
    }

    /**
     * This test verifies, that our packager does also work
     * with an empty list of items. We should also get an empty result list.
     *
     * @test
     * @group lineitemgroup
     */
    public function testNoItemsReturnsEmptyList()
    {
        $items = new LineItemCollection();

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(2, $items, $this->context);

        static::assertCount(0, $packageItems);
    }

    /**
     * This test verifies, that our packager does also work
     * with an invalid negative count. In that case we want an empty result list.
     *
     * @test
     * @group lineitemgroup
     */
    public function testNegativeCountReturnsEmptyList()
    {
        $items = new LineItemCollection();

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(-1, $items, $this->context);

        static::assertCount(0, $packageItems);
    }

    /**
     * This test verifies, that our packager does also work
     * with an invalid zero count. In that case we want an empty result list.
     *
     * @test
     * @group lineitemgroup
     */
    public function testZeroCountReturnsEmptyList()
    {
        $items = new LineItemCollection();

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(0, $items, $this->context);

        static::assertCount(0, $packageItems);
    }

    /**
     * This test verifies that we can successfully build a
     * package, if the quantity of the item is high enough.
     * This means we have just 1 single item, but 3 quantities.
     * Our package needs only 2, so we should get 1 package.
     *
     * @test
     * @group lineitemgroup
     */
    public function testQuantityHigherAsPackage()
    {
        $items = new LineItemCollection();

        $product = $this->createProductItem(50.0, 0);
        $product->setQuantity(3);

        $items->add($product);

        /** @var LineItemCollection $packageItems */
        $packageItems = $this->packager->buildGroupPackage(2, $items, $this->context);

        static::assertCount(1, $packageItems);
    }
}
