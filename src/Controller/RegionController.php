<?php

namespace App\Controller;

use App\Entity\District;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Oka\InputHandlerBundle\Annotation\AccessControl;
use Oka\InputHandlerBundle\Annotation\RequestContent;
use Oka\PaginationBundle\Pagination\PaginationManager;
use Symfony\Component\Validator\Constraints as assert;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


#[Route(name:"region_", path:"/regions", requirements:["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
class RegionController extends AbstractController
{
    
    /**
     * Retrieve region list.
     */
    #[Route(name:"list", methods:"GET")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function list(Request $request, PaginationManager $pm, string $version, string $protocol): Response
    {
        try {
            /** @var \Oka\PaginationBundle\Pagination\Page $page */
            $page = $pm->paginate('region', $request, [], ['createdAt' => 'DESC']);
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

    /*
     *create a region.
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */
    public function create(EntityManagerInterface $em, string $version, string $protocol, array $requestContent): Response
    {
        $region = $this->edit(new Region(), $requestContent);


        $em->persist($region);
        $em->flush();

        return $this->json($region, 201);
    }

    /**
     * Read a region.
     */
    #[Route(name:"read", methods:"GET", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function read(Region $region, string $version, string $protocol): Response
    {
        return $this->json($region);
    }

    /**
     * Update a region.
     */
    #[Route(name:"update", methods:["PUT", "PATCH"], path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */
    public function update(EntityManagerInterface $em, Region $region, string $version, string $protocol, array $requestContent): Response
    {
        $this->edit($region, $requestContent);

        $em->flush();

        return $this->json($region);
    }

    /**
     * Delete region.
     */
    #[Route(name:"delete", methods:"DELETE", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function delete(EntityManagerInterface $em, Region $region, string $version, string $protocol): Response
    {
        try {
            $em->remove($region);
            $em->flush();
        } catch (\Exception $e) {
            throw new ConflictHttpException($this->container->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $region->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }

    
    /**
     * @param \App\Entity\region
     */
    
    protected function edit(object $object, array $requestContent): object
    {
        /** @var \App\Entity\Region $region */
        $region = parent::edit($object, $requestContent);

        if (true === isset($requestContent['district'])) {
            $em = $this->container->get("doctrine.orm.entity_manager");
            $region->setDistrict($em->find(District::class, $requestContent['district']));
        }

        return $region;
    }

    protected static function getExcludedFields(): array
    {
        return ["district",];
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
            'capital' => new $className(new Assert\NotBlank()),
            'district' => new $className(new Assert\NotBlank())
        ]);
    }
}