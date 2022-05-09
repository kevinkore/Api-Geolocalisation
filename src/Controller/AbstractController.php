<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oka\PaginationBundle\Pagination\PaginationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Cedrick Oka Baidai <cedric.baidai@veone.net>
 */
class AbstractController extends BaseAbstractController
{
    public static function getSubscribedServices()
    {
        return [
            ...parent::getSubscribedServices(),
            ...[
                'validator' => '?'.ValidatorInterface::class,
                'translator' => '?'.TranslatorInterface::class,
                'oka_pagination.manager' => '?'.PaginationManager::class,
                'doctrine.orm.entity_manager' => '?'.EntityManagerInterface::class,
                'password.hashes' => '?'.UserPasswordHasherInterface::class,
            ],
        ];
    }

    protected static function getDateTimeFields(): array
    {
        return [];
    }

    protected static function getExcludedFields(): array
    {
        return [];
    }

    protected function edit(object $object, array $requestContent): object
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->enableMagicCall()
            ->getPropertyAccessor();

        foreach ($requestContent as $propertyPath => $content) {
            if (true === in_array($propertyPath, static::getExcludedFields())) {
                continue;
            }

            if (false === $propertyAccessor->isWritable($object, $propertyPath)) {
                continue;
            }

            if (true === in_array($propertyPath, static::getDateTimeFields())) {
                $content = \DateTime::createFromFormat(\DateTime::ISO8601, $content);
            }

            $propertyAccessor->setValue($object, $propertyPath, $content);
        }

        return $object;
    }

    protected function validate($data, Constraint $constraint = null, array $groups = []): ?Response
    {
        /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $errors */
        $errors = $this->get('validator')->validate($data, $constraint, $groups);

        return $errors->count() > 0 ?
            $this->json($errors, 400, [], ['title' => $this->get('translator')->trans('http_error.bad_request', [], 'errors')]) :
            null;
    }

    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $context = [
            AbstractObjectNormalizer::GROUPS => ['details'],
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ...$context,
        ];

        return parent::json($data, $status, $headers, $context);
    }
}