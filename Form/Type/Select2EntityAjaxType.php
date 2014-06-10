<?php
/**
 * This file is part of the agbaug package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\Select2Bundle\Form\Type;

use Ecommit\Select2Bundle\Form\DataTransformer\EntityToIdTransformer;
use Ecommit\Select2Bundle\Form\DataTransformer\IdToIdTransformer;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Select2EntityAjaxType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     *
     * @param ManagerRegistry $em
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['input'] == 'entity') {
            $builder->addViewTransformer(new EntityToIdTransformer($options['query_builder'], $options['root_alias'], $options['identifier']));
        } else {
            $builder->addViewTransformer(new IdToIdTransformer($options['query_builder'], $options['root_alias'], $options['identifier']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $dataSelected = '';
        if ($form->getData() && is_object($form->getData())) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $dataSelected = $accessor->getValue($form->getData(), $options['property']);
        }

        $view->vars['url'] = $options['url'];
        $view->vars['min_chars'] = $options['min_chars'];
        $view->vars['attr'] = array(
            'data-selected-data' => $dataSelected,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $registry = $this->registry;
        $emNormalizer = function (Options $options, $em) use ($registry) {
            if (null !== $em) {
                return $registry->getManager($em);
            }

            return $registry->getManagerForClass($options['class']);
        };

        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            $em = $options['em'];
            $class = $options['class'];
            
            if ($queryBuilder == null) {
                $queryBuilder= $em->createQueryBuilder()
                    ->from($class, 'c')
                    ->select('c');
            }

            if ($queryBuilder instanceof \Closure) {
                $queryBuilder = $queryBuilder($em->getRepository($class));
            }

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new InvalidConfigurationException('"query_builder" must be an instance of Doctrine\ORM\QueryBuilder');
            }

            return $queryBuilder;
        };

        $rootAliasNormalizer = function (Options $options, $rootAlias) {
            if (null !== $rootAlias) {
                return $rootAlias;
            }

            $queryBuilder = $options['query_builder'];

            return current($queryBuilder->getRootAliases());
        };

        $identifierNormalizer = function (Options $options, $identifier) {
            if (null !== $identifier) {
                return $identifier;
            }

            $em = $options['em'];
            $identifiers = $em->getClassMetadata($options['class'])->getIdentifierFieldNames();
            if (count($identifiers) != 1) {
                throw new InvalidConfigurationException('"alias" option is required');
            }

            return $identifiers[0];
        };

        $resolver->setDefaults(array(
            'input'             => 'entity',
            'em'                => null,
            'query_builder'     => null,
            'root_alias'        => null,
            'identifier'        => null,
            'property'          => '__toString',
            'min_chars'         => 1,

            'error_bubbling'    => false,
        ));

        $resolver->setRequired(array(
            'class',
            'url',
        ));

        $resolver->setAllowedValues(array(
            'input'     => array('entity', 'key'),
        ));

        $resolver->setNormalizers(array(
            'em' => $emNormalizer,
            'query_builder' => $queryBuilderNormalizer,
            'root_alias' => $rootAliasNormalizer,
            'identifier' => $identifierNormalizer,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ecommit_javascript_select2entityajax';
    }
} 