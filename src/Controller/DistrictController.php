<?php

namespace App\Controller;

use App\Entity\District;
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


#[Route(name:"district_", path:"/districts", requirements:["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class DistrictController extends AbstractController
{
    
    /**
     * Retrieve district list.
     */
    #[Route(name:"list",methods:"GET")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function list(Request $request, PaginationManager $pm, string $version, string $protocol): Response
    {
        try {
            /** @var \Oka\PaginationBundle\Pagination\Page $page */
            $page = $pm->paginate('district', $request, [], ['createdAt' => 'DESC']);
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
    #[RequestContent(constraints:"createConstraints")]
    public function create(EntityManagerInterface $em, string $version, string $protocol, array $requestContent): Response
    {
        $district = $this->edit(new District(), $requestContent);


        $em->persist($district);
        $em->flush();

        return $this->json($district, 201);
    }

    /**
     * Read a district.
     */
    #[Route(name:"read", methods:"GET", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function read(District $district, string $version, string $protocol): Response
    {
        return $this->json($district);
    }

    /**
     * Update a district.
     */
    #[Route(name:"update", methods:["PUT", "PATCH"], path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    #[RequestContent(constraints:"updateConstraints")] 
    public function update(EntityManagerInterface $em, District $district, string $version, string $protocol, array $requestContent): Response
    {
        $this->edit($district, $requestContent);

        $em->flush();

        return $this->json($district);
    }

    /**
     * Delete a district.
     */
    #[Route(name:"delete", methods:"DELETE", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function delete(EntityManagerInterface $em, District $district, string $version, string $protocol): Response
    {
        try {
            $em->remove($district);
            $em->flush();
        } catch (\Exception $e) {
            throw new ConflictHttpException($this->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $district->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }
 

    /**
     * @param \App\Entity\District
     */
    protected function edit(object $object, array $requestContent): object
    {
        /** @var \App\Entity\District $district */
        $district = parent::edit($object, $requestContent);

        return $district;
    }

    private static function updateConstraints(): Assert\Collection
    {
        $constraints = self::itemConstraints(false);

        return $constraints;
    }

    

    private static function itemConstraints(bool $required): Assert\Collection
    {
        $className = true === $required ? Assert\Required::class : Assert\Optional::class;

        return new Assert\Collection([
            'name' => new $className(new Assert\NotBlank()),
            'capital' => new $className(new Assert\NotBlank()),
        ]);
    }
}