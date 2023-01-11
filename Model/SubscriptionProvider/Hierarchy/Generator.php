<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\Model\SubscriptionProvider\Hierarchy;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Model\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    public function __construct(
        private readonly MetadataPool        $metadataPool,
        private readonly StoreResource       $storeResource,
        private readonly SubscriptionFactory $subscriptionFactory
    )
    {
    }

    public function generate(): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);

        $storeTable = $this->storeResource->getMainTable();
        $storeDimensionName = StoreDimensionProvider::DIMENSION_NAME;

        foreach (Trigger::getListOfEvents() as $event) {
            switch ($event) {
                case Trigger::EVENT_INSERT:
                case Trigger::EVENT_UPDATE:
                    $prefix = 'NEW';
                    break;

                case Trigger::EVENT_DELETE:
                    $prefix = 'OLD';
                    break;

                default:
                    throw new InvalidArgumentException("Trigger event '$event' is unsupported");
            }

            $rows = <<<SQL
                WITH RECURSIVE category_hierarchy (`entity_id`, `parent_id`) AS (
                    SELECT `entity_id`, `parent_id`
                    FROM `{$metadata->getEntityTable()}`
                    WHERE `entity_id` = $prefix.entity_id

                    UNION ALL

                    SELECT `category`.entity_id, category.parent_id
                    FROM `category_hierarchy` AS hierarchy
                    JOIN `{$metadata->getEntityTable()}` AS category
                        ON `category`.`entity_id` = `hierarchy`.`parent_id`
                )

                SELECT
                    entity_id AS `document_id`,
                    NULL AS `node_path`,
                    JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS `dimensions`
                FROM category_hierarchy
                CROSS JOIN $storeTable AS store
                    ON store.store_id != 0
            SQL;


            yield $this->subscriptionFactory->create([
                'tableName' => $metadata->getEntityTable(),
                'triggerEvent' => $event,
                'rows' => $rows
            ]);
        }
    }
}
