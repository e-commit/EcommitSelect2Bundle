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

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class IdToIdTransformer extends EntityToIdTransformer
{
    /**
     * Transforms key to key (check)
     *
     * @param scalar $key
     * @return string
     */
    public function transform($key)
    {
        if (null === $key || '' === $key) {
            return null;
        }

        if (!is_scalar($key)) {
            throw new UnexpectedTypeException($key, 'scalar');
        }

        try {
            $hash = $this->getCacheHash($key);
            if (array_key_exists($hash, $this->resultsCached)) {
                //Result in cache
                //The cache is to avoid 3 SQL queries if reverseTransform is called (reverse - reverseTransform - reverse)
                $entity = $this->resultsCached[$hash];
            }
            else {
                //Result not in cache

                //Not use directly $this->query_builder otherwise transform and
                //reverse functions will use the same request
                $queryBuilder = clone $this->queryBuilder;
                $queryBuilder->setParameters($this->queryBuilder->getParameters());

                $query = $queryBuilder->andWhere(sprintf('%s.%s = :key_transformer', $this->rootAlias, $this->identifier))
                    ->setParameter('key_transformer', $key)
                    ->getQuery();

                $entity = $query->getSingleResult();
                $this->resultsCached[$hash] = $entity; //Saves result in cache
            }
        }
        catch (NoResultException $e) {
            return null;
        }
        catch (\Exception $e) {
            throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found', $key));
        }

        return $this->accessor->getValue($entity, $this->identifier);
    }

    /**
     * Tranforms key to key (check)
     *
     * @param string $value
     * @return String
     */
    public function reverseTransform($value)
    {
        if ('' === $value || null === $value)
        {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Value is not scalar');
        }

        try {
            $hash = $this->getCacheHash($value);
            if (array_key_exists($hash, $this->resultsCached)) {
                //Result in cache
                //The cache is to avoid 3 SQL queries if reverseTransform is called (reverse - reverseTransform - reverse)
                $entity = $this->resultsCached[$hash];
            }
            else {
                //Result not in cache

                //Not use directly $this->query_builder otherwise transform and
                //reverse functions will use the same request
                $queryBuilder = clone $this->queryBuilder;
                $queryBuilder->setParameters($this->queryBuilder->getParameters());

                $query = $queryBuilder->andWhere(sprintf('%s.%s = :key_transformer', $this->rootAlias, $this->identifier))
                    ->setParameter('key_transformer', $value)
                    ->getQuery();

                $entity = $query->getSingleResult();
                $this->resultsCached[$hash] = $entity; //Saves result in cache
            }
        }
        catch (\Exception $e) {
            throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found or is not unique', $key));
        }

        return $this->accessor->getValue($entity, $this->identifier);
    }
} 