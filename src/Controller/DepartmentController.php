<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\Region;
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
use OpenApi\Annotations as OA;

#[Route(name:"department_", path:"/departments", requirements:["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class DepartmentController extends AbstractController
{
    /**
     * Retrieve department list.
     *
     * @OA\Get(
     *     description="Returns departments",
     *     operationId=" App\Controller\Department::list",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             @OA\AdditionalProperties(
     *                 type="integer",
     *                 format="int64"
     *             )
     *         )
     *     )
     * )
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="number of page",
     * required=true,)
     * @OA\Tag(name="department")
     **/
    #[Route(name:"list",methods:"GET",path:"/list&read")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function list(Request $request, PaginationManager $pm, string $version, string $protocol): Response
    {
        try {
            /** @var \Oka\PaginationBundle\Pagination\Page $page */
            $page = $pm->paginate('department', $request, [], ['createdAt' => 'DESC']);
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
     * Create a department.
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */
    public function create(EntityManagerInterface $em, string $version, string $protocol, array $requestContent): Response
    {
        $department = $this->edit(new Department(), $requestContent);


        $em->persist($department);
        $em->flush();

        return $this->json($department, 201);
    }

    /**
     * Read a department.
     *
     * @OA\Get(
     *     path="/{Id}",
     *     description="Return department",
     *     operationId="App\Controller\departmentController::read",
     *     @OA\Parameter(
     *         name="Id",
     *         in="path",
     *         description="ID of communal Sector",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Order"),
     *         @OA\MediaType(
     *             mediaType="application/xml",
     *             @OA\Schema(ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid ID supplied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     * @OA\Tag(name="department")
     **/
    #[Route(name:"read", methods:"GET", path:"/list&read/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function read(Department $department, string $version, string $protocol): Response
    {
        return $this->json($department);
    }

    /**
     * Update a department.
     */
    #[Route(name:"update", methods:["PUT", "PATCH"], path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    /**
     * @RequestContent(constraints="createConstraints")
     */ 
    public function update(EntityManagerInterface $em, Department $department, string $version, string $protocol, array $requestContent): Response
    {
        $this->edit($department, $requestContent);

        $em->flush();

        return $this->json($department);
    }

    /**
     * Delete a department.
     */
    #[Route(name:"delete", methods:"DELETE", path:"/{id}")]
    #[AccessControl(version:"v1", protocol:"rest", formats:"json")]
    public function delete(EntityManagerInterface $em, Department $department, string $version, string $protocol): Response
    {
        try {
            $em->remove($department);
            $em->flush();
        } catch (\Exception $e) {
            throw new ConflictHttpException($this->container->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $department->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }
 
    protected function edit(object $object, array $requestContent): object
    {
        /** @var \App\Entity\Department $department */
        $department = parent::edit($object, $requestContent);

        if (true === isset($requestContent['region'])) {
            $em = $this->container->get("doctrine.orm.entity_manager");
            $department->setRegion($em->find(Region::class, $requestContent['region']));
        }

        return $department;
    }

    protected static function getExcludedFields(): array
    {
        return ["region"];
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
            'region' => new $className(new Assert\NotBlank()),
        ]);
    }
}