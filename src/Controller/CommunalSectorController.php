<?php

namespace App\Controller;

use App\Entity\Common;
use App\Entity\CommunalSector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Oka\InputHandlerBundle\Annotation\AccessControl;
use Oka\InputHandlerBundle\Annotation\RequestContent;
use Oka\PaginationBundle\Pagination\PaginationManager;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route(name:"communal_sector_", path:"/communalSectors", requirements:["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class CommunalSectorController extends AbstractController
{
    /**
     * Retrieve communalSector list.
     */
    #[Route(name:"list",methods:"GET")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function list(Request $request, PaginationManager $pm, string $version, string $protocol): Response
    {
        try {
            /** @var \Oka\PaginationBundle\Pagination\Page $page */
            $page = $pm->paginate('communalSector', $request, [], ['createdAt' => 'DESC']);
        } catch (\Oka\PaginationBundle\Exception\PaginationException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json(
            $page->toArray(),
            $page->getPageNumber() > 1 ? 206 : 200,
            [],
            ['groups' => $request->query->has('details') ? ['details'] : ['summary']]
        );
    }

    /**
     * Create a communalSector.
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */
    public function create(EntityManagerInterface $em, string $version, string $protocol, array $requestContent): Response
    {
        $communalSector = $this->edit(new CommunalSector(), $requestContent);


        $em->persist($communalSector);
        $em->flush();

        return $this->json($communalSector, 201);
    }

    /**
     * Read a communalSector.
     */
    #[Route(name:"read", methods:"GET", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function read(CommunalSector $communalSector, string $version, string $protocol): Response
    {
        return $this->json($communalSector);
    }

    /**
     * Update a communalSector.
     */
    #[Route(name:"update", methods:["PUT", "PATCH"], path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */ 
    public function update(EntityManagerInterface $em, CommunalSector $communalSector, string $version, string $protocol, array $requestContent): Response
    {
        $this->edit($communalSector, $requestContent);

        $em->flush();

        return $this->json($communalSector);
    }

    /**
     * Delete a communalSector.
     */
    #[Route(name:"delete", methods:"DELETE", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function delete(EntityManagerInterface $em, CommunalSector $communalSector, string $version, string $protocol): Response
    {
        try {
            $em->remove($communalSector);
            $em->flush();
        } catch (\Exception $e) {
            throw new ConflictHttpException($this->container->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $communalSector->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }

    protected function edit(object $object, array $requestContent): object
    {
        /** @var \App\Entity\CommunalSector $communalsector */
        $communalSector = parent::edit($object, $requestContent);

        if (true === isset($requestContent['common'])) {
            $em = $this->container->get("doctrine.orm.entity_manager");
            $communalSector->setCommon($em->find(Common::class, $requestContent['common']));
        }

        return $communalSector;
    }

    protected static function getExcludedFields(): array
    {
        return ["common"];
    }

    private static function updateConstraints(): Assert\Collection
    {
        $constraints = self::itemConstraints(false);

        return $constraints;
    }

    private static function createConstraints(): Assert\Collection
    {
        return self::itemConstraints(true);
    }

    private static function itemConstraints(bool $required): Assert\Collection
    {
        $className = true === $required ? Assert\Required::class : Assert\Optional::class;

        return new Assert\Collection([
            'name' => new $className(new Assert\NotBlank()),
            'common' => new $className(new Assert\NotBlank()),
        ]);
    }
}