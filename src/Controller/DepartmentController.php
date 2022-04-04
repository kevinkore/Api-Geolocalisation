<?php

namespace App\Controller;

use App\Entity\Department;
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

#[Route(name:"department_", path:"/departments", requirements:["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class DepartmentController extends AbstractController
{
    /**
     * Retrieve department list.
     */
    #[Route(name:"list",methods:"GET")]
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
    #[RequestContent(constraints:"createConstraints")]
    public function create(EntityManagerInterface $em, string $version, string $protocol, array $requestContent): Response
    {
        $department = $this->edit(new Department(), $requestContent);


        $em->persist($department);
        $em->flush();

        return $this->json($department, 201);
    }

    /**
     * Read a department.
     */
    #[Route(name:"read", methods:"GET", path:"/{id}")]
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
    #[RequestContent(constraints:"updateConstraints")] 
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
            throw new ConflictHttpException($this->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $department->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }
 

    /**
     * @param \App\Entity\Department
     */
    protected function edit(object $object, array $requestContent): object
    {
        /** @var \App\Entity\Department $department */
        $department = parent::edit($object, $requestContent);

        return $department;
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
        ]);
    }
}