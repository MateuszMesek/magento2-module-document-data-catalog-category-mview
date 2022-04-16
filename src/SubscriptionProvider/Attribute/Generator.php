<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider\Attribute;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private MetadataPool $metadataPool;
    private Config $config;
    private SubscriptionFactory $subscriptionFactory;

    public function __construct(
        MetadataPool $metadataPool,
        Config $config,
        SubscriptionFactory $subscriptionFactory
    )
    {
        $this->metadataPool = $metadataPool;
        $this->config = $config;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    public function generate(string $code): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);

        $attribute = $this->config->getAttribute($metadata->getEavEntityType(), $code);

        if (!$attribute) {
            throw new InvalidArgumentException("Attribute '$code' not found");
        }

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

            $condition = '';
            $dimensions = "JSON_SET('{}', '$.scope', 0)";

            if (!$attribute->isStatic()) {
                $condition = "$prefix.attribute_id = {$attribute->getAttributeId()}";
                $dimensions = "JSON_SET('{}', '$.scope', $prefix.store_id)";
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $attribute->getBackendTable(),
                'triggerEvent' => $event,
                'condition' => $condition,
                'documentId' => "(SELECT {$metadata->getIdentifierField()} FROM {$metadata->getEntityTable()} WHERE {$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()})",
                'dimensions' => $dimensions,
            ]);
        }
    }
}
