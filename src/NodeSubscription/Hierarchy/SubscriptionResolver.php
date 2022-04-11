<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\NodeSubscription\Hierarchy;

use Generator;
use MateuszMesek\DocumentDataIndexMviewApi\NodeSubscriptionsResolverInterface;

class SubscriptionResolver implements NodeSubscriptionsResolverInterface
{
    public function resolve(): Generator
    {
        yield '*' => [
            'category_hierarchy' => [
                'id' => 'category_hierarchy',
                'type' => SubscriptionGenerator::class,
                'arguments' => []
            ]
        ];
    }
}
