<?php
/**
 * This file is part of the agbaug package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\Select2Bundle\Form\DataTransformer;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class EntityToIdTransformer implements DataTransformerInterface
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    protected $rootAlias;
    protected $identifier;
    protected $throwExceptionIfValueNotFoundInReverse;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    protected $resultsCached = array();

    /**
     * Constructor
     *
     * @param QueryBuilder $queryBuilder
     * @param string $rootAlias     Doctrine Root Alias in Query Builder
     * @param sting $identifier  Identifier name
     */
    public function __construct(QueryBuilder $queryBuilder, $rootAlias, $identifier, $throwExceptionIfValueNotFoundInReverse = true)
    {
        $this->queryBuilder = $queryBuilder;
        $this->rootAlias = $rootAlias;
        $this->identifier = $identifier;
        $this->throwExceptionIfValueNotFoundInReverse = $throwExceptionIfValueNotFoundInReverse;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Transforms entity to id
     *
     * @param Object $entity
     * @return string
     */
    public function transform($entity)
    {
        if (null === $entity || '' === $entity) {
            return null;
        }

        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }

        //Here, do not put the result in the cache because we must check the value in
        //reverseTransform (by QueryBuilder)

        return $this->accessor->getValue($entity, $this->identifier);
    }

    /**
     * Tranforms id to entity
     *
     * @param string $value
     * @return Object
     */
    public function reverseTransform($value)
    {
        if ('' === $value || null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Value is not scalar');
        }

        try {
            $hash = $this->getCacheHash($value);
            if (array_key_exists($hash, $this->resultsCached)) {
                $entity = $this->resultsCached[$hash];
            }
            else {
                //Result not in cache

                $query = $this->queryBuilder->andWhere(sprintf('%s.%s = :key_transformer', $this->rootAlias, $this->identifier))
                    ->setParameter('key_transformer', $value)
                    ->getQuery();

                $entity = $query->getSingleResult();
                $this->resultsCached[$hash] = $entity; //Saves result in cache
            }
        }
        catch(\Exception $e) {
            if ($this->throwExceptionIfValueNotFoundInReverse) {
                throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found or is not unique', $value));
            } else {
                return null;
            }
        }

        return $entity;
    }

    /**
     * Returns cache key for found result
     * @param array $id
     * @return string
     */
    protected function getCacheHash($id)
    {
        return md5(json_encode(array(
            spl_object_hash($this->queryBuilder),
            $this->rootAlias,
            $this->identifier,
            (string)$id,
        )));
    }
} 