<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Product\Review;

use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Shopware\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader;
use Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewLoader as CoreProductReviewLoader;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Page\StorefrontSearchResult;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use \Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewLoader instead
 */
#[Package('after-sales')]
class ProductReviewLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractProductReviewLoader $productReviewLoader,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $context): ReviewLoaderResult
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewLoader::class));

        $productId = $request->get('productId');
        if (!\is_string($productId)) {
            throw RoutingException::missingRequestParameter('productId');
        }

        $reviews = $this->productReviewLoader->load($request, $context, $productId, $request->get('parentId'));
        /** @var StorefrontSearchResult<ProductReviewCollection> $storefrontReviews */
        $storefrontReviews = new StorefrontSearchResult(
            $reviews->getEntity(),
            $reviews->getTotal(),
            $reviews->getEntities(),
            $reviews->getAggregations(),
            $reviews->getCriteria(),
            $reviews->getContext()
        );

        $this->eventDispatcher->dispatch(new ProductReviewsLoadedEvent($storefrontReviews, $context, $request));

        $reviewResult = ReviewLoaderResult::createFrom($storefrontReviews);
        $reviewResult->setMatrix($reviews->getMatrix());
        $reviewResult->setCustomerReview($reviews->getCustomerReview());
        $reviewResult->setTotalReviews($reviews->getTotal());
        $reviewResult->setTotalReviewsInCurrentLanguage($reviews->getTotalReviewsInCurrentLanguage());
        $reviewResult->setProductId($reviews->getProductId());
        $reviewResult->setParentId($reviews->getParentId());

        return $reviewResult;
    }
}
