<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider\Hierarchy;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private MetadataPool $metadataPool;
    private SubscriptionFactory $subscriptionFactory;

    public function __construct(
        MetadataPool $metadataPool,
        SubscriptionFactory $subscriptionFactory
    )
    {
        $this->metadataPool = $metadataPool;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    public function generate(): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);

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
                    FROM `mage_catalog_category_entity`
                    WHERE `entity_id` = $prefix.entity_id

                    UNION ALL

                    SELECT `category`.entity_id, category.parent_id
                    FROM `category_hierarchy` AS hierarchy
                    JOIN `{$metadata->getEntityTable()}` AS category
                        ON `category`.`entity_id` = `hierarchy`.`parent_id`
                )

                SELECT entity_id AS `document_id`, NULL AS `node_path`, NULL AS `dimensions` FROM category_hierarchy
            SQL;


            yield $this->subscriptionFactory->create([
                'tableName' => $metadata->getEntityTable(),
                'triggerEvent' => $event,
                #'condition' => "NEW.parent_id != OLD.parent_id",
                'dimensions' => "JSON_SET('{}', '$.scope', 0)",
                'rows' => $rows
            ]);
        }
    }
}
