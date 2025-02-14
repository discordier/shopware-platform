<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\System\Salutation\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\Event\SalutationRouteCacheKeyEvent;
use Shopware\Core\System\Salutation\SalesChannel\AbstractSalutationRoute;
use Shopware\Core\System\Salutation\SalesChannel\CachedSalutationRoute;
use Shopware\Core\System\Salutation\SalesChannel\SalutationRouteResponse;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\Test\Generator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CachedSalutationRoute::class)]
class CachedSalutationRouteTest extends TestCase
{
    private MockObject&AbstractSalutationRoute $decorated;

    private MockObject&CacheInterface $cache;

    private EventDispatcher $eventDispatcher;

    private CachedSalutationRoute $cachedRoute;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $this->decorated = $this->createMock(AbstractSalutationRoute::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->context = Generator::generateSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
        );

        $this->cachedRoute = new CachedSalutationRoute(
            $this->decorated,
            $this->cache,
            $this->createMock(EntityCacheKeyGenerator::class),
            $this->createMock(AbstractCacheTracer::class),
            $this->eventDispatcher,
            []
        );
    }

    public function testLoadWithDisabledCacheWillCallDecoratedRoute(): void
    {
        $this->decorated
            ->expects(static::once())
            ->method('load')
            ->willReturn(new SalutationRouteResponse(new EntitySearchResult(
                'entity',
                1,
                new SalutationCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )));
        $this->cache
            ->expects(static::never())
            ->method('get');
        $this->eventDispatcher->addListener(
            SalutationRouteCacheKeyEvent::class,
            fn (SalutationRouteCacheKeyEvent $event) => $event->disableCaching()
        );

        $this->cachedRoute->load(new Request(), $this->context, new Criteria());
    }

    public function testLoadWithEnabledCacheWillReturnDataFromCache(): void
    {
        $this->decorated
            ->expects(static::never())
            ->method('load');
        $this->cache
            ->expects(static::once())
            ->method('get')
            ->willReturn(
                CacheValueCompressor::compress(
                    new SalutationRouteResponse(new EntitySearchResult(
                        'entity',
                        1,
                        new SalutationCollection(),
                        null,
                        new Criteria(),
                        Context::createDefaultContext()
                    ))
                )
            );
        $this->eventDispatcher->addListener(
            SalutationRouteCacheKeyEvent::class,
            fn (SalutationRouteCacheKeyEvent $event) => $event
        );

        $this->cachedRoute->load(new Request(), $this->context, new Criteria());
    }
}
