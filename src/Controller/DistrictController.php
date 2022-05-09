<?php

namespace App\Controller;

use App\Entity\District;
use Doctrine\ORM\EntityManagerInterface;
use Oka\PaginationBundle\Exception\PaginationException;
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
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation as Nelmio;

#[Route(path: "/districts", name: "district_", requirements: ["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class DistrictController extends AbstractController
{
    /**
     * List the district .
     *
     * @OA\Get(
     *
     *     description="Returns districts",
     *     operationId=" App\Controller\distritController::list",
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
     * @Nelmio\Areas({"internal"})
     *  @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="number of page",
     *         required=true,)
     *@OA\Tag(name="district")
     **/
    #[Route(name:"list",methods:"GET")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function list(Request $request, PaginationManager $pm): Response
    {
        try {
            $page = $pm->paginate('district', $request, [], ['createdAt' => 'DESC']);
        } catch (PaginationException $e) {
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
     * create a district.
     * @RequestContent(constraints="createConstraints")
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function create(EntityManagerInterface $em, array $requestContent): Response
    {
        $district = $this->edit(new District(), $requestContent);

        $em->persist($district);
        $em->flush();

        return $this->json($district, 201);
    }

    /**
     * Read a district.
     *
     * @OA\Get(
     *     path="/{Id}",
     *     description="Return district",
     *     operationId="App\Controller\DistrictController::read",
     *     @OA\Parameter(
     *         name="Id",
     *         in="path",
     *         description="ID of common",
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
     * @Nelmio\Areas({"internal"})
     * @OA\Tag(name="district")
     **/
    #[Route(path: "/{id}", name: "read", methods: "GET")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function read(District $district): Response
    {
        return $this->json($district);
    }

    /**
     * Update a district.
     *
     * @RequestContent(constraints="createConstraints")
     */
    #[Route(path: "/{id}", name: "update", methods: ["PUT", "PATCH"])]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function update(EntityManagerInterface $em, District $district, array $requestContent): Response
    {
        $this->edit($district, $requestContent);

        $em->flush();

        return $this->json($district);
    }

    /**
     * Delete a district.
     */
    #[Route(path: "/{id}", name: "delete", methods: "DELETE")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function delete(EntityManagerInterface $em, District $district): Response
    {
        try {
            $em->remove($district);
            $em->flush();
        } catch (\Exception $e) {
            throw new ConflictHttpException($this->contenair->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $district->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
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
        ]);
    }
}